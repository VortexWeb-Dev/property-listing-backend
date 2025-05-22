<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Photo;
use App\Models\Company;
use App\Models\Listing;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Aws\S3\S3Client;
class ListingController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies("property.view")) {
            abort(403, "YOU ARE NOT ALLOWED TO VIEW PROPERTIES.");
        }
    
        $user = Auth::user();
        $role = $user->role;
        $userId = $user->id;
        $companyId = $user->company_id;
    
        // Base query with eager-loaded relationships
        $query = Listing::with([
            "photos", "amenities", "developer", "pfLocation", "bayutLocation", "company", "agent", "owner",  'pfAgent',
            'websiteAgent',
            'bayutDubizzleAgent'
        ])->where("status", "!=", "deleted");
    
        // Super Admin: see everything
        if ($role === "super_admin") {
            // Apply filters for super_admin
            $this->applyFilters($query, $request);
            
           
            $listings = $query->paginate(50);
            $listings->appends($request->query());

    
            return response()->json([
                "listings" => $listings,
                "owners_for_all_companies" => User::where("role", "owner")->get(),
            ]);
        }
    
        // Others must be tied to a company
        if ($companyId === null) {
            return response()->json([
                "error" => "You are not assigned to any company or do not have permission.",
            ], 403);
        }
    
        // Add company filter
        $query->where("company_id", $companyId);
    
        // Role-specific filtering
        if ($role === "agent") {
            $query->where("agent_id", $userId);
        }
    
        if ($role === "owner") {
            $query->where("owner_id", $userId);
        }
    
        // Apply filters for non-super-admins
        $this->applyFilters($query, $request);
    
        $listings = $query->paginate(50);
        $listings->appends($request->query());

        $owners = User::where("role", "owner")->where("company_id", $companyId)->get();
    
        return response()->json([
            "listings" => $listings,
            "owners_for_this_company" => $owners,
        ]);
    }
    
// For filteration

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('pf_location')) {
            $query->where('pf_location', $request->pf_location);
        }

        if ($request->filled('bayut_location')) {
            $query->where('bayut_location', $request->bayut_location);
        }

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }

        if ($request->filled('developer')) {
            $query->where('developer_id', $request->developer);
        }

        if ($request->filled('property_type')) {
            $query->where('property_type', $request->property_type);
        }

        if ($request->filled('offering_type')) {
            $query->where('offering_type', $request->offering_type);
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        if ($request->filled('bedrooms')) {
            $query->where('bedrooms', $request->bedrooms);
        }

        if ($request->filled('bathrooms')) {
            $query->where('bathrooms', $request->bathrooms);
        }

        if ($request->filled('size_min')) {
            $query->where('size', '>=', $request->size_min);
        }

        if ($request->filled('size_max')) {
            $query->where('size', '<=', $request->size_max);
        }

        // 🔍 Search by title
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('title', 'like', '%' . $searchTerm . '%');
        }
    }



    public function store(Request $request)
    {
        if (Gate::denies("property.create")) {
            abort(403, "YOU ARE NOT ALLOWED TO CREATE LISTINGS.");
        }

        $input = $request->all();
        $currentLoggedInUser = Auth::user();
        $currentLoggedInUser_role = $currentLoggedInUser->role;
        $currentLoggedInUser_id = $currentLoggedInUser->id;
        $currentLoggedInUser_companyId = $currentLoggedInUser->company_id;

        // Handle the roles and assign company_id based on role (admin, agent, owner)
        if ($currentLoggedInUser_role === "admin") {
            if ($currentLoggedInUser_companyId != null) {
                $input["company_id"] = $currentLoggedInUser_companyId;
            } else {
                return response()->json(
                    "You are not assigned with any company"
                );
            }
        }

        if ($currentLoggedInUser_role === "agent") {
            if ($currentLoggedInUser_companyId != null) {
                $input["agent_id"] = $currentLoggedInUser_id;
                $input["company_id"] = $currentLoggedInUser_companyId;
            } else {
                return response()->json(
                    "You are not assigned with any company"
                );
            }
        }

        if ($currentLoggedInUser_role === "owner") {
            if ($currentLoggedInUser_companyId != null) {
                $input["owner_id"] = $currentLoggedInUser_id;
                $input["company_id"] = $currentLoggedInUser_companyId;
            } else {
                return response()->json(
                    "You are not assigned with any company"
                );
            }
        }

        // Validation
        $validatedData = Validator::make($input, [
            // Include all new fields for validation
            "reference_no" => "nullable|string|max:255",
            "title" => "string|max:255",
            "title_deed" => "nullable|string|max:255",
            "property_type" => "required|string|max:255",
            "offering_type" => "required|string|max:255",
            "size" => "nullable|numeric",
            "unit_no" => "nullable|string|max:255",
            "bedrooms" => "nullable|integer",
            "bathrooms" => "nullable|integer",
            "parking" => "nullable|integer",
            "furnished" => ["nullable", Rule::in(["0", "1", "2"])],
            "total_plot_size" => "nullable|numeric",
            "plot_size" => "nullable|string|max:255",
            "built_up_area" => "nullable|string|max:255",
            "layout_type" => "nullable|string|max:255",
            "project_name" => "nullable|string|max:255",
            "project_status" => [
                "nullable",
                Rule::in([
                    'Off Plan',
                    'Off-Plan Primary',
                    'Off-Plan Secondary',
                    'Ready Primary',
                    'Ready Secondary',
                    'Completed'
                ]),
            ],
            "sale_type" => ["nullable", Rule::in(['0','1','2'])],
            "developer_id" => "nullable|exists:developers,id",
            "build_year" => "nullable|string|max:255",
            "customer" => "nullable|string|max:255",
            "rera_permit_number" => "nullable|string|max:255",
            "rera_issue_date" => "nullable|date",
            "rera_expiration_date" => "nullable|date",
            "contract_expiry_date" => "nullable|date",
            "rental_period" => "nullable|string|max:255",
            "price" => "nullable|numeric",
            "payment_method" => "nullable|string|max:255",
            "financial_status" => "nullable|string|max:255",
            "sale_type_1" => "nullable|string|max:255",
            "title_en" => "nullable|string|max:255",
            "title_ar" => "nullable|string|max:255",
            "desc_en" => "nullable|string",
            "desc_ar" => "nullable|string",
            "geopoints" => "nullable|string|max:255",
            "listing_owner" => "nullable|string|max:255",
            "landlord_name" => "nullable|string|max:255",
            "landlord_contact" => "nullable|string|max:255",
            "pf_location" => "nullable|exists:locations,id",
            "bayut_location" => "nullable|exists:locations,id",
            "availability" => ["nullable", Rule::in(['available','under_offer','reserved','sold'])],
            "available_from" => "nullable|date",
            "emirate_amount" => "nullable|numeric",
            "payment_option" => "nullable|string|max:255",
            "no_of_cheques" => ["nullable", Rule::in(['1','2'])],
            "contract_charges" => "nullable|numeric",
            "financial_status_id" => "nullable|exists:users,id",
            "contract_expiry" => "nullable|string|max:255",
            "floor_plan" => "nullable|string|max:255",
            "qr_code" => "nullable|string|max:255",
            "brochure" => "nullable|string|max:255",
            "video_url" => "nullable|url",
            "360_view_url" => "nullable|url",
            "photo_urls" => "required|array",
            "photo_urls.*.file_url" => "required|url",
            "photo_urls.*.is_main" => "required|boolean",
            "dtcm_permit_number"=>"nullable|string|max:255",
            "watermark" => ["nullable", Rule::in(['0','1'])],
            "pf_enable" => "nullable|boolean",
            "bayut_enable" => "nullable|boolean",
            "dubizzle_enable" => "nullable|boolean",
            "website_enable" => "nullable|boolean",
            "company_id" => "required|exists:companies,id",
            "agent_id" => "nullable|exists:users,id",
            "owner_id" => "nullable|exists:users,id",
            "status" => "nullable|in:draft,live,archived,published,unpublished,pocket",
            "amenities" => "nullable|array",
            "amenities.*" => "exists:amenities,id",
            'landlord_email' => 'nullable|email|max:255',
            'comments' => 'nullable|string|max:1000',
            "pf_agent_id" => "nullable|exists:users,id",
            "website_agent_id" => "nullable|exists:users,id",
            "bayut_dubizzle_agent_id" => "nullable|exists:users,id",

        ])->validate();
        

        // Create the listing
        $listing = Listing::create(
            collect($validatedData)
                ->except(["photo_urls", "amenities"])
                ->toArray()
        );

        // Attach amenities if any
        if ($request->has("amenities")) {
            $listing->amenities()->sync($request->amenities);
        }

        // Save each image from frontend
        $photo_urls = $validatedData["photo_urls"];
        foreach ($photo_urls as $photo) {
            $listing->photos()->create([
                "image_url" => $photo["file_url"],
                "is_main" => $photo["is_main"],
                "is_active" => true,
            ]);
        }

        // Save all photo URLs in a field if needed
      

        return response()->json($listing->load(["amenities", "photos"]), 201);
    }

    public function update(Request $request, Listing $listing)
    {
        $validatedData = $request->validate([
            "remove_images" => "array",
            "remove_images.*" => "integer|exists:photos,id",
        
            "photo_urls" => "nullable|array",
            "photo_urls.*.file_url" => "required_with:photo_urls|string",
            "photo_urls.*.is_main" => "required_with:photo_urls|boolean",
        
            "main_image" => "nullable|string",
            "reference_no" => "nullable|string|max:255",
            "title" => "nullable|string|max:255",
            "title_deed" => "nullable|string|max:255",
            "property_type" => "nullable|string|max:255",
            "offering_type" => "nullable|string|max:255",
            "size" => "nullable|numeric",
            "unit_no" => "nullable|string|max:255",
            "bedrooms" => "nullable|integer",
            "bathrooms" => "nullable|integer",
            "parking" => "nullable|integer",
            "furnished" => "nullable|in:0,1,2", // 0=unfurnished, 1=semi, 2=furnished
            "total_plot_size" => "nullable|numeric",
            "plot_size" => "nullable|string",
            "built_up_area" => "nullable|string",
            "layout_type" => "nullable|string",
            "project_name" => "nullable|string",
            "project_status" => "nullable|in:Off Plan,Off-Plan Primary,Off-Plan Secondary,Ready Primary,Ready Secondary,Completed", // Enum values for project status
            "sale_type" => "nullable|in:0,1,2", // Enum values for sale type
            "developer_id" => "nullable|exists:developers,id",
            "build_year" => "nullable|string",
            "customer" => "nullable|string",
            "rera_permit_number" => "nullable|string",
            "rera_issue_date" => "nullable|date",
            "rera_expiration_date" => "nullable|date",
            "contract_expiry_date" => "nullable|date",
            "rental_period" => "nullable|string",
            "price" => "nullable|integer",
            "payment_method" => "nullable|string",
            "financial_status" => "nullable|string",
            "sale_type_1" => "nullable|string",
            "title_en" => "nullable|string|max:255",
            "title_ar" => "nullable|string|max:255",
            "desc_en" => "nullable|string",
            "desc_ar" => "nullable|string",
            "geopoints" => "nullable|string",
            "listing_owner" => "nullable|string",
            "landlord_name" => "nullable|string",
            "landlord_contact" => "nullable|string",
            "pf_location" => "nullable|exists:locations,id",
            "bayut_location" => "nullable|exists:locations,id",
            "availability" => "nullable|in:available,under_offer,reserved,sold",
            "available_from" => "nullable|date",
            "emirate_amount" => "nullable|numeric",
            "payment_option" => "nullable|string",
            "no_of_cheques" => "nullable|in:1,2", // Enum values for cheques number
            "contract_charges" => "nullable|numeric",
            "financial_status_id" => "nullable|exists:users,id",
            "contract_expiry" => "nullable|string",
            "floor_plan" => "nullable|string",
            "qr_code" => "nullable|string",
            "brochure" => "nullable|string",
            "video_url" => "nullable|string",
            "360_view_url" => "nullable|string",
            "photos_urls" => "nullable|string",
            "dtcm_permit_number"=>"nullable|string|max:255",
            "status" => "nullable|in:draft,live,archived,published,unpublished,pocket", // Enum values for status
            "watermark" => "nullable|in:0,1",
        
            "pf_enable" => "nullable|boolean",
            "bayut_enable" => "nullable|boolean",
            "dubizzle_enable" => "nullable|boolean",
            "website_enable" => "nullable|boolean",
        
            "company_id" => "nullable|exists:companies,id",
            "agent_id" => "nullable|exists:users,id",
            "owner_id" => "nullable|exists:users,id",
        
            "amenities" => "nullable|array",
            "amenities.*" => "integer|exists:amenities,id", // Ensures amenities exist in the amenities table
            'landlord_email' => 'nullable|email|max:255',
            'comments' => 'nullable|string|max:1000',
            "pf_agent_id" => "nullable|exists:users,id",
            "website_agent_id" => "nullable|exists:users,id",
            "bayut_dubizzle_agent_id" => "nullable|exists:users,id",
    

        ]);
        
       

        // Restrict agent users from updating company_id and agent_id
        if (auth()->user()->role === "agent") {
            unset($validatedData["agent_id"]);
            unset($validatedData["company_id"]);
        }

        $removedMain = false;

        // Step 1: Remove selected images
        if ($request->filled("remove_images")) {
            foreach ($request->remove_images as $photoId) {
                $photo = Photo::find($photoId);
                if ($photo && $photo->listing_id === $listing->id) {
                    if ($photo->is_main) {
                        $removedMain = true;
                    }
                    $photo->delete(); // ✅ No Storage delete — S3 handles lifecycle
                }
            }
        }

        // Step 2: Add new S3 images from photo_urls[]
        if ($request->filled("photo_urls")) {
            // First, if any is_main is true, reset all current to false
            if (collect($request->photo_urls)->contains("is_main", true)) {
                $listing->photos()->update(["is_main" => false]);
            }

            foreach ($request->photo_urls as $imageData) {
                $listing->photos()->create([
                    "image_url" => $imageData["file_url"],
                    "is_main" => $imageData["is_main"],
                    "is_active" => true,
                ]);

                if ($imageData["is_main"]) {
                    $removedMain = false;
                }
            }
        }

        // Step 3: Set existing image as main (if given and not in new uploads)
        if ($request->filled("main_image")) {
            $main = $listing
                ->photos()
                ->where("id", $request->main_image)
                ->first();
            if ($main) {
                $listing->photos()->update(["is_main" => false]);
                $main->update(["is_main" => true]);
                $removedMain = false;
            }
        }

        // Step 4: Fallback if main image was removed
        if ($removedMain) {
            $fallback = $listing->photos()->first();
            if ($fallback) {
                $fallback->update(["is_main" => true]);
            }
        }
        if ($request->filled('amenities')) {
            $listing->amenities()->sync($request->amenities);
        }
        
        // Step 5: Update listing fields
        $listing->update($validatedData);

        return response()->json([
            "message" => "Listing updated successfully",
            "listing" => $listing->load("photos"),
        ]);
    }

        public function show($id)
    {
       
        if (Gate::denies("property.view")) {
            abort(403, "YOU ARE NOT ALLOWED TO VIEW THIS PROPERTY.");
        }

        $user = Auth::user();
        $role = $user->role;
        $userId = $user->id;
        $companyId = $user->company_id;

        // Check if the listing exists
             $listing = Listing::with([
                "photos",
                "amenities",
                "developer",
                "company",
                "agent",
                "pfLocation",
                "bayutLocation",
                "owner",
                'pfAgent',
                'websiteAgent',
                'bayutDubizzleAgent',
            ])->find($id);
            
        // If listing not found, return 404 response
        if (!$listing) {
            return response()->json([
                "error" => "Listing not found."
            ], 404);
        }

        // Super Admin can view all listings and all owners
        if ($role === "super_admin") {
            return response()->json([
                "listing" => $listing,
                "owners_for_all_companies" => User::where("role", "owner")->get(),
            ]);
        }

        // Non-super_admin must be assigned to a company
        if ($companyId === null) {
            return response()->json(
                [
                    "error" => "You are not assigned to any company or do not have permission.",
                ],
                403
            );
        }

        // Owners for this company
        $owners = User::where("role", "owner")
            ->where("company_id", $companyId)
            ->get();

        // Agent: can only view their own listings
        if ($role === "agent" && $listing->agent_id !== $userId) {
            return response()->json([
                "error" => "You do not have permission to view this listing."
            ], 403);
        }

        // Owner: can only view their own listings
        if ($role === "owner" && $listing->owner_id !== $userId) {
            return response()->json([
                "error" => "You do not have permission to view this listing."
            ], 403);
        }

        return response()->json([
            "listing" => $listing,
            "owners_for_this_company" => $owners,
        ]);
    }


        public function destroy($id)
    {
        if (Gate::denies("property.create")) {
            abort(403, "YOU ARE NOT ALLOWED TO EDIT USERS.");
        }

        $listing = Listing::findOrFail($id);
        $listing->status = 'deleted';
        $listing->save();

        return response()->json(["message" => "Listing marked as deleted"]);
    }


    // For super admin get all company and their agents list
    public function createInfo()
    {
        $user = Auth::user();

        if ($user->role !== "super_admin") {
            return response()->json(["message" => "Unauthorized"], 403);
        }

        $companies = Company::with([
            "agents" => function ($query) {
                $query->where("role", "agent");
            },
        ])->get();

        return response()->json(["companies" => $companies]);
    }

    // For ADMIN: Get agents of that admin's company
    public function agentsList()
    {
        $user = Auth::user();

        if ($user->role !== "admin") {
            return response()->json(["message" => "Unauthorized"], 403);
        }

        $companyId = $user->company_id;

        $agents = User::where("role", "agent")
            ->where("company_id", $companyId)
            ->get();

        return response()->json([
            "company_id" => $companyId,
            "agents" => $agents,
        ]);
    }

        public function listOwners()
    {
        $user = Auth::user();

        if ($user->role === 'super_admin') {
            $owners = User::with('company')
                ->where('role', 'owner')
                ->get();

            return response()->json(['owners' => $owners]);
        }

        if ($user->role === 'admin') {
            if (is_null($user->company_id)) {
                return response()->json(['message' => 'Admin user does not have a company_id.'], 400);
            }

            $owners = User::where('role', 'owner')
                ->where('company_id', $user->company_id)
                ->get();

            return response()->json([
                'company_id' => $user->company_id,
                'owners' => $owners,
            ]);
        }

        if ($user->role === 'owner') {
            if (is_null($user->company_id)) {
                return response()->json(['message' => 'Owner user does not have a company_id.'], 400);
            }

            $owners = User::where('role', 'owner')
                ->where('company_id', $user->company_id)
                ->where('id', '!=', $user->id) // exclude self
                ->get();

            return response()->json([
                'company_id' => $user->company_id,
                'owners' => $owners,
            ]);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    //agent list for agent transfer when agent is logged in 
    
        public function agentsList_for_agent()
    {
        $user = Auth::user();

        // Only agents are allowed
        if ($user->role !== 'agent') {
            return response()->json(["message" => "Unauthorized"], 403);
        }

        $companyId = $user->company_id;

        // Fetch all other agents in the same company except the logged-in user
        $agents = User::where('role', 'agent')
            ->where('company_id', $companyId)
            ->where('id', '!=', $user->id)
            ->get();

        return response()->json([
            "company_id" => $companyId,
            "agents" => $agents
        ]);
    }

        public function agentsList_for_owner()
    {
        $user = Auth::user();

        // Only owners are allowed
        if ($user->role !== 'owner') {
            return response()->json(["message" => "Unauthorized"], 403);
        }

        if (is_null($user->company_id)) {
            return response()->json(["message" => "Owner does not belong to any company."], 400);
        }

        $companyId = $user->company_id;

        // Fetch all agents in the same company
        $agents = User::where('role', 'agent')
            ->where('company_id', $companyId)
            ->get();

        return response()->json([
            "company_id" => $companyId,
            "agents" => $agents
        ]);
    }

    
}