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
       // below code insures admin and agent won't be able to create a company
       if (Gate::denies('company.create')) 
        {
            return response()->json(['message' => 'You are not allowed to perform this action!'], 403);
        }
   
        
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:companies,email',
            'phone' => 'nullable|string',
            'website' => 'nullable|url',
            'admins' => 'nullable|array',
            'admins.*' => 'exists:users,id',
        ]);
    
        // Get all provided admin IDs
        $requestedAdminIds = $validated['admins'] ?? [];
    
        // Fetch actual admin IDs from DB that match requested IDs
        $validAdminIds = User::whereIn('id', $requestedAdminIds)
                             ->where('role', 'admin')
                             ->pluck('id')
                             ->toArray();
    
        // If any provided admin ID is not a valid admin, throw error
        $invalidAdminIds = array_diff($requestedAdminIds, $validAdminIds);
    
        if (!empty($invalidAdminIds)) 
        {
            return response()->json([
                'message' => 'Selected admin(s) are not valid'
              
            ], 422);
        }
    
        // Proceed to create company
        $company = Company::create([
            ...$validated,
            'admins' => $validAdminIds,
        ]);
    
        return response()->json($company, 201);
    }

    public function update(Request $request, Company $company)
    {
   
        $validated = $request->validate([
        'name' => 'sometimes|string',
        'email' => 'sometimes|email|unique:companies,email,' . $company->id,
        'phone' => 'nullable|string',
        'website' => 'nullable|url',

        // NEW
        'admins_to_add' => 'nullable|array',
        'admins_to_add.*' => 'exists:users,id',

        'admins_to_remove' => 'nullable|array',
        'admins_to_remove.*' => 'exists:users,id',
    ]);
   
    // Get current admin IDs from company
    $currentAdmins = $company->admins ?? [];
  
    // Get only valid admin IDs to add
    $add = User::whereIn('id', $validated['admins_to_add'] ?? [])
        ->where('role', 'admin')
        ->pluck('id')
        ->toArray();
      
    //  Validation check: if some provided IDs are not admins, return error
    if (count($add) !== count($validated['admins_to_add'] ?? [])) 
    {
        return response()->json([
            'message' => 'One or more users to add are not admins.'
        ], 422);
    }
   
    // Admins to remove
    $remove = $validated['admins_to_remove'] ?? [];
   
    // Final admins = (current + add) - remove
    $updatedAdmins = collect($currentAdmins)
        ->merge($add)
        ->unique()
        ->diff($remove)
        ->values()
        ->toArray();
       
        $updateData = collect($validated)->except(['admins_to_add', 'admins_to_remove'])->toArray();

        // Add the actual admins update
        $updateData['admins'] = $updatedAdmins;
        
        $company->update($updateData);
        
        return response()->json($company);
    
    
    }
    
    public function show($company_id)
    {
        $company=Company::find($company_id);
        if(!$company)
        {
            return response()->json("No Company Found",404);
        }
        
        return response()->json($company);
    }
    public function destroy(Company $company)
    {
    $company->delete();
    return response()->json('Company Deleted', 204);
    }

}