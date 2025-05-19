<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\OffplanPhoto;
use Illuminate\Http\Request;
use App\Models\OffplanListing;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\OffplanListingRequest;
use App\Http\Requests\UpdateOffplanListingRequest;

class OffplanListingController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(OffplanListing::class, 'offplanListing');
    }

        public function index(Request $request)
    {
        // Authorization is automatically handled by authorizeResource()

        $user = auth()->user();
        $role = $user->role;
        $userId = $user->id;
        $companyId = $user->company_id;

        $query = OffplanListing::with([
            'photos', 'amenities', 'developer', 'pfLocation', 'bayutLocation', 'company', 'agent', 'owner'
        ])->where('status', '!=', 'deleted');

        if ($role === 'super_admin') {
            // Super Admin sees all listings
            $this->applyFilters($query, $request);

            $listings = $query->paginate(50);
            $listings->appends($request->query());

            return response()->json([
                'listings' => $listings,
                'owners_for_all_companies' => User::where('role', 'owner')->get(),
            ]);
        }

        // All other roles must be linked to a company
        if (is_null($companyId)) {
            return response()->json([
                'error' => 'You are not assigned to any company or do not have permission.',
            ], 403);
        }

        // Filter by company
        $query->where('company_id', $companyId);

        // Role-specific ownership filtering
        if ($role === 'agent') {
            $query->where('agent_id', $userId);
        }

        if ($role === 'owner') {
            $query->where('owner_id', $userId);
        }

        // Apply common filters
        $this->applyFilters($query, $request);

        $listings = $query->paginate(50);
        $listings->appends($request->query());

        $owners = User::where('role', 'owner')
            ->where('company_id', $companyId)
            ->get();

        return response()->json([
            'listings' => $listings,
            'owners_for_this_company' => $owners,
        ]);
    }

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

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
    }

        public function store(OffplanListingRequest $request)
    {
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

        $validatedData = $request->validated();


        // Create the listing
        $listing = OffplanListing::create(
            collect($validatedData)
                ->except(["photo_urls", "amenities"])
                ->toArray()
        );

        // Attach amenities if any
        if ($request->has("amenities")) {
            $listing->amenities()->sync($validatedData['amenities']);
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

        public function update(UpdateOffplanListingRequest $request, OffplanListing $offplanListing)
    {
        $validatedData = $request->validated();

        // Prevent agents from changing agent_id or company_id
        if (auth()->user()->role === "agent") {
            unset($validatedData["agent_id"]);
            unset($validatedData["company_id"]);
        }

        $removedMain = false;

        // Step 1: Remove selected images
        if ($request->filled("remove_images")) {
            foreach ($request->remove_images as $photoId) {
                $photo = OffplanPhoto::find($photoId);
                if ($photo && $photo->offplan_listing_id === $offplanListing->id) {
                    if ($photo->is_main) {
                        $removedMain = true;
                    }
                    $photo->delete(); // S3 deletion handled by lifecycle
                }
            }
        }

        // Step 2: Add new images from photo_urls[]
        if ($request->filled("photo_urls")) {
            if (collect($request->photo_urls)->contains("is_main", true)) {
                $offplanListing->photos()->update(["is_main" => false]);
            }

            foreach ($request->photo_urls as $imageData) {
                $offplanListing->photos()->create([
                    "image_url" => $imageData["file_url"],
                    "is_main" => $imageData["is_main"],
                    "is_active" => true,
                ]);

                if ($imageData["is_main"]) {
                    $removedMain = false;
                }
            }
        }

        // Step 3: Mark existing image as main
        if ($request->filled("main_image")) {
            $main = $offplanListing->photos()->where("id", $request->main_image)->first();
            if ($main) {
                $offplanListing->photos()->update(["is_main" => false]);
                $main->update(["is_main" => true]);
                $removedMain = false;
            }
        }

        // Step 4: Fallback to first image if main removed
        if ($removedMain) {
            $fallback = $offplanListing->photos()->first();
            if ($fallback) {
                $fallback->update(["is_main" => true]);
            }
        }

        // Step 5: Sync amenities
        if ($request->filled('amenities')) {
            $offplanListing->amenities()->sync($request->amenities);
        }

        // Step 6: Update the listing
        $offplanListing->update($validatedData);

        return response()->json([
            "message" => "Offplan listing updated successfully",
            "listing" => $offplanListing->load([
                'photos',
                'developer',
                'pfLocation',
                'bayutLocation',
                'company',
                'agent',
                'owner',
                'amenities'
            ]),
        ]);
    }

    public function show(OffplanListing $offplanListing)
    {
        
    
        $user = Auth::user();
        $role = $user->role;
        $userId = $user->id;
        $companyId = $user->company_id;
    
        // Eager load relationships
        $offplanListing->load([
            "photos",
            "amenities",
            "developer",
            "company",
            "agent",
            "pfLocation",
            "bayutLocation",
            "owner"
        ]);
    
        // Super Admin can view all listings and all owners
        if ($role === "super_admin") {
            return response()->json([
                "listing" => $offplanListing,
                "owners_for_all_companies" => User::where("role", "owner")->get(),
            ]);
        }
    
        // Non-super_admin must belong to a company
        if (is_null($companyId)) {
            return response()->json([
                "error" => "You are not assigned to any company or do not have permission.",
            ], 403);
        }
    
        // Agent: can only view their own listings
        if ($role === "agent" && $offplanListing->agent_id !== $userId) {
            return response()->json([
                "error" => "You do not have permission to view this listing."
            ], 403);
        }
    
        // Owner: can only view their own listings
        if ($role === "owner" && $offplanListing->owner_id !== $userId) {
            return response()->json([
                "error" => "You do not have permission to view this listing."
            ], 403);
        }
    
        // Owners for this company
        $owners = User::where("role", "owner")
            ->where("company_id", $companyId)
            ->get();
    
        return response()->json([
            "listing" => $offplanListing,
            "owners_for_this_company" => $owners,
        ]);
    }
    
        public function destroy(OffplanListing $offplanListing)
    {
        

        // $offplanListing = OffplanListing::findOrFail($id);
        $offplanListing->status = 'deleted';
        $offplanListing->save();

        return response()->json([
            "message" => "Offplan listing marked as deleted."
        ]);
    }




}