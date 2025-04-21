<?php

namespace App\Http\Controllers;
use Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        if (Gate::denies("user.view")) {
            abort(403, "YOU ARE NOT ALLOWED TO VIEW USERS.");
        }

        $authUser = auth()->user();

        // If the logged-in user is admin, only show users from their company
        if ($authUser->role === "admin") {
            return User::with("company")
                ->whereCompany_id($authUser->company_id)
                ->whereNot("role", "super_admin")
                ->get();
        }

        // Otherwise (e.g., super_admin), return all users
        return User::with("company")->get();
    }

    public function store(Request $request)
{
    if (Gate::denies("user.create")) {
        abort(403, "YOU ARE NOT ALLOWED TO CREATE USERS.");
    }

    $authUser = auth()->user(); // get the logged-in user

    // Basic rules for all
    $rules = [
        "name" => "required|string",
        "email" => "required|email|unique:users,email",
        "password" => "required|string|min:6",
        "phone" => "nullable|string",
        "profile_url" => "nullable|url",
    ];

    if ($authUser->role === "admin") {
        // Admins can only assign 'agent' or 'owner'
        $rules["role"] = "required|in:agent,owner";
    } else {
        // Super admin can assign any role
        $rules["role"] = "required|in:super_admin,admin,agent,owner";
        $rules["company_id"] = "nullable|exists:companies,id";
    }

    // Temporarily validate 'role' only to decide about 'rera_number'
    $tempValidation = $request->validate([
        'role' => $rules['role']
    ]);

    // If role is agent, rera_number is required
    if ($request->role === 'agent') {
        $rules['rera_number'] = 'required|string';
    } else {
        $rules['rera_number'] = 'nullable|string';
    }

    // Now perform full validation
    $validated = $request->validate($rules);

    // Force company_id if user is admin
    if ($authUser->role === "admin") {
        if (is_null($authUser->company_id)) {
            return response()->json(
                [
                    "message" => "You are not assigned to any company.",
                ],
                403
            );
        }

        $validated["company_id"] = $authUser->company_id;
    }

    $user = User::create($validated);

    // If role is admin, add to company admins
    if (
        $validated["role"] === "admin" &&
        !empty($validated["company_id"])
    ) {
        $company = Company::find($validated["company_id"]);
        $currentAdmins = $company->admins ?? [];
        $updatedAdmins = collect($currentAdmins)
            ->push($user->id)
            ->unique()
            ->values()
            ->toArray();
        $company->admins = $updatedAdmins;
        $company->save();
    }

    return response()->json($user, 201);
}


    public function show(User $user)
    {
        if (Gate::denies("user.view")) {
            abort(403, "YOU ARE NOT ALLOWED TO CREATE USERS.");
        }

        $logged_in_user = Auth::user();
        $logged_in_user_role = $logged_in_user->role;
        $company_id = $logged_in_user->company_id;

        if ($logged_in_user_role === "admin") {
            return User::with("company")
                ->whereCompany_id($company_id)
                ->whereNot("role", "super_admin")
                ->whereId($user->id)
                ->get();
        }

        return $user->load("company");
    }

    public function update(Request $request, User $user)
{
    // Only admin and super_admin are allowed
    if (Gate::denies("user.edit")) {
        abort(403, "YOU ARE NOT ALLOWED TO EDIT USERS.");
    }

    $currentUser = Auth::user();

    $cleanRole = strtolower(trim(preg_replace("/\s+/", "", $user->role)));

    if ($currentUser->role === "admin" && !in_array($cleanRole, ["agent", "owner"])) {
        return response()->json(["message" => "ADMINS CAN ONLY EDIT AGENTS OR OWNERS."], 403);
    }

    if ($currentUser->role === "admin" && $currentUser->company_id != $user->company_id) {
        return response()->json(["message" => "U CAN NOT UPDATE THIS USER."], 403);
    }

    $validated = $request->validate([
        "name" => "sometimes|string",
        "email" => "sometimes|email|unique:users,email," . $user->id,
        "password" => "nullable|string|min:6",
        "company_id" => "nullable|integer|min:0",
        "role" => "in:super_admin,admin,agent",
        "phone" => "nullable|string",
        "rera_number" => "nullable|string",
        "profile_url" => "nullable|url",
    ]);

    if ($currentUser->role === "admin" && isset($validated["role"])) {
        $attemptedRole = strtolower($validated["role"]);
        if (in_array($attemptedRole, ["admin", "super_admin"])) {
            return response()->json([
                "message" => "You are not allowed to promote agents to admin or super admin.",
            ], 403);
        }
    }

    $newRole = $validated["role"] ?? $user->role;
    $wasAgent = strtolower($user->role) === "agent";
    $becomesAgent = strtolower($newRole) === "agent";

    // Check for RERA number requirement
    if ($becomesAgent && empty($request->rera_number)) {
        return response()->json([
            "message" => "The RERA number is required for users with the agent role."
        ], 422);
    }

    if (isset($validated["password"])) {
        $validated["password"] = bcrypt($validated["password"]);
    }

    $originalRole = $user->role;
    $oldCompanyId = $user->company_id;
    $newCompanyId = array_key_exists("company_id", $validated)
        ? ($validated["company_id"] !== 0 ? $validated["company_id"] : null)
        : $user->company_id;

    // Remove admin from old company admin list if demoted
    if ($originalRole === "admin" && $newRole !== "admin" && $oldCompanyId) {
        $oldCompany = Company::find($oldCompanyId);
        if ($oldCompany) {
            $oldCompany->admins = collect($oldCompany->admins ?? [])
                ->reject(fn($id) => $id == $user->id)
                ->values()
                ->toArray();
            $oldCompany->save();
        }
    }

    // Handle admin assignment to company
    if ($newRole === "admin") {
        if ($oldCompanyId && $oldCompanyId != $newCompanyId) {
            $oldCompany = Company::find($oldCompanyId);
            if ($oldCompany) {
                $oldCompany->admins = collect($oldCompany->admins ?? [])
                    ->reject(fn($id) => $id == $user->id)
                    ->values()
                    ->toArray();
                $oldCompany->save();
            }
        }

        if ($newCompanyId) {
            $newCompany = Company::find($newCompanyId);
            if ($newCompany) {
                $newCompany->admins = collect($newCompany->admins ?? [])
                    ->push($user->id)
                    ->unique()
                    ->values()
                    ->toArray();
                $newCompany->save();
            }
        }
    }

    // Set company_id = null if 0 was passed
    if (isset($validated["company_id"]) && $validated["company_id"] == 0) {
        $validated["company_id"] = null;
    }

    $user->update($validated);

    return response()->json($user);
}


    public function destroy(User $user)
    {
        if (Gate::denies("user.delete")) {
            return response()->json(["message" => "Unauthorized"], 403);
        }

        $user->delete();
        return response()->json(null, 204);
    }

    public function unassociatedadmins()
    {
        $adminsList = User::whereRole("admin")
            ->whereNull("company_id")
            ->get();
        return response()->json($adminsList);
    }

    public function associatedadmins()
    {
        $admins = User::with("company") 
            ->where("role", "admin")
            ->whereNotNull("company_id")
            ->get();

        return response()->json($admins);
    }
}