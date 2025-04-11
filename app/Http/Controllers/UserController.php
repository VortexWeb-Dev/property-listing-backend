<?php

namespace App\Http\Controllers;
use Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    if (Gate::denies('user.view')) 
    {
        abort(403, 'YOU ARE NOT ALLOWED TO VIEW USERS.');
    }

    $authUser = auth()->user();

    // If the logged-in user is admin, only show users from their company
    if ($authUser->role === 'admin') 
    {
        return User::with('company')
            ->whereCompany_id($authUser->company_id)
            ->whereNot('role' , "super_admin")
            ->get();
    }

    // Otherwise (e.g., super_admin), return all users
    return User::with('company')->get();
}


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    if (Gate::denies('user.create')) {
        abort(403, 'YOU ARE NOT ALLOWED TO CREATE USERS.');
    }

    $authUser = auth()->user(); // get the logged-in user

    // Adjust validation based on who is creating the user
    $rules = [
        'name' => 'required|string',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:6',
        'phone' => 'nullable|string',
        'rera_number' => 'nullable|string',
        'profile_url' => 'nullable|url',
    ];

    if ($authUser->role === 'admin') {
        // Admins can only assign the role 'agent' and can't set company_id
        $rules['role'] = 'required|in:agent';
    } else {
        // Super admin or others can assign any role and company
        $rules['role'] = 'required|in:super_admin,admin,agent';
        $rules['company_id'] = 'nullable|exists:companies,id';
    }

    $validated = $request->validate($rules);

    // If admin, force company_id from the logged-in user
    if ($authUser->role === 'admin') {
        if (is_null($authUser->company_id)) {
            return response()->json([
                'message' => 'You are not assigned to any company.'
            ], 403);
        }
    
        // force the same company_id for the new agent
        $validated['company_id'] = $authUser->company_id;
    }
    

    $user = User::create($validated);

    // If role is admin (and allowed), update company admins list
    if ($validated['role'] === 'admin' && !empty($validated['company_id'])) {
        $company = Company::find($validated['company_id']);
        $currentAdmins = $company->admins ?? [];
        $updatedAdmins = collect($currentAdmins)->push($user->id)->unique()->values()->toArray();
        $company->admins = $updatedAdmins;
        $company->save();
    }

    return response()->json($user, 201);
}


    
    public function show(User $user)
    {

        if (Gate::denies('user.view')) 
        {
         abort(403, 'YOU ARE NOT ALLOWED TO CREATE USERS.');
        }

        
        $logged_in_user=Auth::user();
        $logged_in_user_role=$logged_in_user->role;
        $company_id=$logged_in_user->company_id;

        if($logged_in_user_role==="admin")
        {
            return User::with('company')
            ->whereCompany_id($company_id)
            ->whereNot('role' , "super_admin")
            ->whereId($user->id)
            ->get();
        }
        
        
       
         
        return $user->load('company');
    }

   
    public function update(Request $request, User $user)
    
    {
     //only admin and super_admin are allowed to perform this action  
       
    if (Gate::denies('user.edit')) {
        abort(403, 'YOU ARE NOT ALLOWED TO EDIT USERS.');
    }

    // Check role-based update permission
    $currentUser = Auth::user();

    $cleanRole = strtolower(trim(preg_replace('/\s+/', '', $user->role)));

if ($currentUser->role === 'admin' && !in_array($cleanRole, ['agent', 'user'])) {          //to prevent admin from editing other admins/superadmins data
    abort(403, 'ADMINS CAN ONLY EDIT AGENTS OR USERS.');
    }
    
    $validated = $request->validate([
        'name' => 'sometimes|string',
        'email' => 'sometimes|email|unique:users,email,' . $user->id,
        'password' => 'nullable|string|min:6',
        'company_id' => 'nullable|integer|min:0',
        'role' => 'in:super_admin,admin,agent,developer,user',
        'phone' => 'nullable|string',
        'rera_number' => 'nullable|string',
        'profile_url' => 'nullable|url'
    ]);

    // Hash password if present
    if (isset($validated['password'])) {
        $validated['password'] = bcrypt($validated['password']);
    }

    // Track original role and company_id before updating,check if non zero value is passed to update company_id if not store old company_id.
    $originalRole = $user->role;
    $oldCompanyId = $user->company_id;
    $newCompanyId = array_key_exists('company_id', $validated)
    ? ($validated['company_id'] !== 0 ? $validated['company_id'] : null)
    : $user->company_id; // fallback to old if not passed


    $newRole = $validated['role'] ?? $originalRole;

    // When an admin user is demoted (for example, changed to agent or user), they should no longer be listed in the companies admins field. This code ensures that.
    if ($originalRole === 'admin' && $newRole !== 'admin' && $oldCompanyId) {
        $oldCompany = Company::find($oldCompanyId);
        if ($oldCompany) {
            $oldAdmins = collect($oldCompany->admins ?? [])
                ->reject(fn($id) => $id == $user->id)
                ->values()
                ->toArray();

            $oldCompany->admins = $oldAdmins;
            $oldCompany->save();
        }
    }

    // Handle admin assigning to company
    if ($newRole === 'admin') 
    {
        // If company_id changed, remove from old
        if ($oldCompanyId && $oldCompanyId != $newCompanyId) 
        {
            $oldCompany = Company::find($oldCompanyId);
            if ($oldCompany) 
            {
                $oldAdmins = collect($oldCompany->admins ?? [])
                    ->reject(fn($id) => $id == $user->id)
                    ->values()
                    ->toArray();

                $oldCompany->admins = $oldAdmins;
                $oldCompany->save();
            }
        }

       
        
        // if role has changed to admin and it has comapnay_id either previously or in this request add his user id in companies admins column.
        if ($newCompanyId) {
            $newCompany = Company::find($newCompanyId);
            if ($newCompany) 
            {
                $currentAdmins = $newCompany->admins ?? [];
                $updatedAdmins = collect($currentAdmins)
                    ->push($user->id)
                    ->unique()
                    ->values()
                    ->toArray();

                $newCompany->admins = $updatedAdmins;
                $newCompany->save();
            }
        }
    }

    // Set company_id = null if 0 was passed
    if (isset($validated['company_id']) && $validated['company_id'] == 0) {
        $validated['company_id'] = null;
    }

    $user->update($validated);

    return response()->json($user);
    }

    public function destroy(User $user)
    {
        if (Gate::denies('user.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        
        $user->delete();
        return response()->json(null, 204);
    }

}