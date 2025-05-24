<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CompanyController extends Controller
{
   
      public function index()
    {
        // below code insures admin and agent won't be able to see all other companies
        if (Gate::denies('company.view')) 
        {
            return response()->json(['message' => 'You are not allowed to perform this action!'], 403);
        }
        return Company::with('users')->get();
    }

        public function store(Request $request)
    {
        if (Gate::denies('company.create')) 
        {
            return response()->json(['message' => 'You are not allowed to perform this action!'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'bitrix_api'=>'nullable|string',
            'email' => 'required|email|unique:companies,email',
            'phone' => 'nullable|string',
            'website' => 'nullable|url',
            'admins' => 'nullable|array',
            'admins.*' => 'exists:users,id',
        ]);

        $requestedAdminIds = $validated['admins'] ?? [];

        // Fetch users with given IDs and role = admin
        $adminUsers = User::whereIn('id', $requestedAdminIds)
                        ->where('role', 'admin')
                        ->get();

        // Check for invalid admin IDs
        $validAdminIds = $adminUsers->pluck('id')->toArray();
        $invalidAdminIds = array_diff($requestedAdminIds, $validAdminIds);

        if (!empty($invalidAdminIds)) {
            return response()->json([
                'message' => 'Selected admin(s) are not valid'
            ], 422);
        }

        // Check if any of the admins already have a company_id assigned
        $alreadyAssignedAdmins = $adminUsers->filter(function ($admin) {
            return $admin->company_id !== null;
        });

        if ($alreadyAssignedAdmins->isNotEmpty()) {
            return response()->json([
                'message' => 'One or more selected admins already belong to a company.',
                'admins_with_company' => $alreadyAssignedAdmins->pluck('id')
            ], 422);
        }

        // Proceed to create company
        $company = Company::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'admins' => $validAdminIds, // assuming this is a JSON or array column
        ]);

        // Assign company_id to each admin user
        User::whereIn('id', $validAdminIds)->update(['company_id' => $company->id]);

        return response()->json($company->load('users'), 201); // load related users if needed
    }



    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'bitrix_api'=>'nullable|string',
            'email' => 'sometimes|email|unique:companies,email,' . $company->id,
            'phone' => 'nullable|string',
            'website' => 'nullable|url',
    
            'admins_to_add' => 'nullable|array',
            'admins_to_add.*' => 'exists:users,id',
    
            'admins_to_remove' => 'nullable|array',
            'admins_to_remove.*' => 'exists:users,id',
        ]);
    
        $adminsToAddIds = $validated['admins_to_add'] ?? [];
        $adminsToRemoveIds = $validated['admins_to_remove'] ?? [];
    
        // ✅ Prevent adding and removing the same admin
        $conflictIds = array_intersect($adminsToAddIds, $adminsToRemoveIds);
        if (!empty($conflictIds)) {
            return response()->json([
                'message' => 'A user cannot be added and removed at the same time.',
                'conflict_ids' => $conflictIds,
            ], 422);
        }
    
        // ✅ Fetch users who are valid: role = admin, company_id = null
        $adminUsersToAdd = User::whereIn('id', $adminsToAddIds)
            ->where('role', 'admin')
            ->whereNull('company_id')
            ->get();
    
        $validAddIds = $adminUsersToAdd->pluck('id')->toArray();
        $invalidAddIds = array_diff($adminsToAddIds, $validAddIds);
    
        if (!empty($invalidAddIds)) {
            return response()->json([
                'message' => 'Some users are not valid admins or already belong to a company.',
                'invalid_user_ids' => $invalidAddIds,
            ], 422);
        }
    
        // ✅ Assign company_id to valid new admins (use save to trigger model events)
        foreach ($adminUsersToAdd as $user) {
            $user->company_id = $company->id;
            $user->save();
        }
    
        // ✅ Remove company_id for users being removed (only if they belong to this company)
        User::whereIn('id', $adminsToRemoveIds)
            ->where('company_id', $company->id)
            ->update(['company_id' => null]);
    
        // ✅ Calculate new admins list
        $currentAdmins = $company->admins ?? [];
        $updatedAdmins = collect($currentAdmins)
            ->merge($validAddIds)          // Add new
            ->unique()
            ->diff($adminsToRemoveIds)     // Remove removed
            ->values()
            ->toArray();
    
        // ✅ Update company fields
        $updateData = collect($validated)
            ->except(['admins_to_add', 'admins_to_remove'])
            ->toArray();
    
        $updateData['admins'] = $updatedAdmins;
    
        $company->update($updateData);
    
        return response()->json($company->fresh('users'));
    }
    
    
    public function show($company_id)
    {
        $company = Company::with('users')->find($company_id);
    
        if (!$company) {
            return response()->json(['message' => 'No Company Found'], 404);
        }
    
        // No need to manually assign admin_users now
        return response()->json($company);
    }
    


    public function destroy(Request $request)
    {
        $ids = $request->input('ids');
    
        // Step 1: Validate input
        if (!is_array($ids) || empty($ids)) {
            return response()->json([
                "message" => "Please provide a non-empty array of company IDs."
            ], 400);
        }
    
        // Step 2: Validate ID format
        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                return response()->json([
                    "message" => "Invalid ID in array: $id"
                ], 400);
            }
        }
    
        // Step 3: Get existing companies
        $companies = Company::whereIn('id', $ids)->get();
        $existingIds = $companies->pluck('id')->toArray();
        $missingIds = array_diff($ids, $existingIds);
    
        if (!empty($missingIds)) {
            return response()->json([
                "message" => "Some company IDs were not found.",
                "missing_ids" => array_values($missingIds)
            ], 404);
        }
    
        // Step 4: Delete companies
        Company::whereIn('id', $existingIds)->delete();
    
        return response()->json([
            "message" => "Company(ies) deleted successfully.",
            "deleted_ids" => $existingIds
        ], 200);
    }

}