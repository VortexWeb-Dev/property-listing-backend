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
    public function index()
    {
        if (Gate::denies("property.create")) {
            abort(403, "YOU ARE NOT ALLOWED TO EDIT USERS.");
        }

        $user = Auth::user();
        $role = $user->role;
        $userId = $user->id;
        $companyId = $user->company_id;

        // Super Admin: see all listings and all owners
        if ($role === "super_admin") {
            return response()->json([
                "listings" => Listing::with(["photos", "amenities"])->get(),
                "owners_for_all_companies" => User::where(
                    "role",
                    "owner"
                )->get(),
            ]);
        }

        // Non-super_admin must be assigned to a company
        if ($companyId === null) {
            return response()->json(
                [
                    "error" =>
                        "You are not assigned to any company or do not have permission.",
                ],
                403
            );
        }

        // Owners for this company
        $owners = User::where("role", "owner")
            ->where("company_id", $companyId)
            ->get();

        // Listings within the company
        $listingsQuery = Listing::with(["photos", "amenities"])->where(
            "company_id",
            $companyId
        );

        // Agent: can only view their own listings
        if ($role === "agent") {
            $listingsQuery->where("agent_id", $userId);
        }

        if ($role == "owner") {
            $listingsQuery->where("owner_id", $userId);
        }
        $listings = $listingsQuery->get();

        return response()->json([
            "listings" => $listings,
            "owners_for_this_company" => $owners,
        ]);
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
            "reference_no" => "nullable|string|max:255",
            "title" => "required|string|max:255",
            "property_type" => "required|string|max:255",
            "offering_type" => "required|string|max:255",
            "size" => "nullable|numeric",
            "bedrooms" => "nullable|integer",
            "bathrooms" => "nullable|integer",
            "furnished" => ["nullable", Rule::in(["0", "1", "2"])],
            "developer_id" => "nullable|exists:developers,id",
            "location_id" => "nullable|exists:locations,id",
            "company_id" => "required|exists:companies,id",
            "agent_id" => "required|exists:users,id",
            "financial_status_id" => "nullable|exists:users,id",
            "rera_permit_issue_date" => "nullable|date",
            "rera_expiration_date" => "nullable|date",
            "contract_expiry_date" => "nullable|date",
            "available_from" => "nullable|date",
            "status" => ["nullable", Rule::in(["0", "1", "2"])],
            "amenities" => "nullable|array",
            "amenities.*" => "exists:amenities,id",
            "photo_urls" => "required|array",
            "photo_urls.*.file_url" => "required|url",
            "photo_urls.*.is_main" => "required|boolean",
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
        $listing->update([
            "photos_urls" => json_encode(array_column($photo_urls, "file_url")),
        ]);

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
            "furnished" => "nullable|in:0,1,2",
            "total_plot_size" => "nullable|numeric",
            "plot_size" => "nullable|string",
            "built_up_area" => "nullable|string",
            "layout_type" => "nullable|string",
            "project_name" => "nullable|string",
            "project_status" => "nullable|in:1,2,3,4,5",
            "sale_type" => "nullable|in:0,1,2",
            "developer_id" => "nullable|exists:developers,id",
            "build_year" => "nullable|string",
            "customer" => "nullable|string",
            "rera_permit" => "nullable|string",
            "rera_number" => "nullable|string",
            "rera_permit_issue_date" => "nullable|date",
            "rera_expiration_date" => "nullable|date",
            "contract_expiry_date" => "nullable|date",
            "rental_period" => "nullable|string",
            "rprice_price" => "nullable|in:0,1,2",
            "payment_method" => "nullable|string",
            "financial_status" => "nullable|string",
            "sale_type_1" => "nullable|string",
            "title(english)" => "nullable|string",
            "title(arabic)" => "nullable|string",
            "description(english)" => "nullable|string",
            "description(arabic)" => "nullable|string",
            "geopoints" => "nullable|string",
            "listing_owner" => "nullable|string",
            "landlord_name" => "nullable|string",
            "landlord_contact" => "nullable|string",
            "location_id" => "nullable|exists:locations,id",
            "availability" => "nullable|in:available,under_offer,reserved,sold",
            "available_from" => "nullable|date",
            "emirate_amount" => "nullable|numeric",
            "payment_option" => "nullable|string",
            "no_of_cheques" => "nullable|in:1,2",
            "contract_charges" => "nullable|numeric",
            "financial_status_id" => "nullable|exists:users,id",
            "contract_expiry" => "nullable|string",
            "floor_plan" => "nullable|string",
            "qr_code" => "nullable|string",
            "brochure" => "nullable|string",
            "video_url" => "nullable|string",
            "360_view_url" => "nullable|string",
            "photos_urls" => "nullable|string",
            "status" => "nullable|in:0,1,2",
            "property_finder" => "nullable|in:0,1",
            "dubizzle" => "nullable|in:0,1",
            "website" => "nullable|in:0,1",
            "watermark" => "nullable|in:0,1",
            "company_id" => "nullable|exists:companies,id",
            "agent_id" => "nullable|exists:users,id",
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

        // Step 5: Update listing fields
        $listing->update($validatedData);

        return response()->json([
            "message" => "Listing updated successfully",
            "listing" => $listing->load("photos"),
        ]);
    }

    public function destroy($id)
    {
        if (Gate::denies("property.create")) {
            abort(403, "YOU ARE NOT ALLOWED TO EDIT USERS.");
        }

        Listing::destroy($id);
        return response()->json(["message" => "Deleted"]);
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

        if ($user->role === "super_admin") {
            $owners = User::with("company")
                ->where("role", "owner")
                ->get();

            return response()->json(["owners" => $owners]);
        }

        if ($user->role === "admin") {
            $owners = User::where("role", "owner")
                ->where("company_id", $user->company_id)
                ->get();

            return response()->json([
                "company_id" => $user->company_id,
                "owners" => $owners,
            ]);
        }

        return response()->json(["message" => "Unauthorized"], 403);
    }
}