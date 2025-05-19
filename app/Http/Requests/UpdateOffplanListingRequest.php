<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOffplanListingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "remove_images" => "array",
            "remove_images.*" => "integer|exists:offplan_listings_photos,id",

            "photo_urls" => "nullable|array",
            "photo_urls.*.file_url" => "required_with:photo_urls|string",
            "photo_urls.*.is_main" => "required_with:photo_urls|boolean",

            "main_image" => "nullable|string",
            "reference_no" => "nullable|string|max:255",
            "title" => "nullable|string|max:255",
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
            "no_of_cheques" => "nullable|in:1,2",
            "contract_charges" => "nullable|numeric",
            "financial_status_id" => "nullable|exists:users,id",
            "contract_expiry" => "nullable|string",
            "floor_plan" => "nullable|string",
            "qr_code" => "nullable|string",
            "brochure" => "nullable|string",
            "video_url" => "nullable|string",
            "360_view_url" => "nullable|string",
            "watermark" => "nullable|in:0,1",
            "pf_enable" => "nullable|boolean",
            "bayut_enable" => "nullable|boolean",
            "dubizzle_enable" => "nullable|boolean",
            "website_enable" => "nullable|boolean",
            "company_id" => "nullable|exists:companies,id",
            "agent_id" => "nullable|exists:users,id",
            "owner_id" => "nullable|exists:users,id",
            "status" => "nullable|in:draft,live,archived,published,unpublished,pocket",
            "dtcm_permit_number" => "nullable|string|max:255",
            "amenities" => "nullable|array",
            "amenities.*" => "integer|exists:amenities,id",
        ];
    }
}