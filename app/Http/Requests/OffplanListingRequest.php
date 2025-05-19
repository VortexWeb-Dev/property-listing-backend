<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OffplanListingRequest extends FormRequest
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
    public function rules()
    {
        return [
            "reference_no" => "nullable|string|max:255",
            "title" => "required|string|max:255",
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
            "project_status" => ["nullable", Rule::in(['1','2','3','4','5'])],
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
            "watermark" => ["nullable", Rule::in(['0','1'])],
            "pf_enable" => "nullable|boolean",
            "bayut_enable" => "nullable|boolean",
            "dubizzle_enable" => "nullable|boolean",
            "website_enable" => "nullable|boolean",
            "company_id" => "required|exists:companies,id",
            "agent_id" => "nullable|exists:users,id",
            "owner_id" => "nullable|exists:users,id",
            "status" => ["nullable", Rule::in(['draft','live','archived','published','unpublished','pocket'])],
            "photo_urls" => "required|array",
            "photo_urls.*.file_url" => "required|url",
            "photo_urls.*.is_main" => "required|boolean",
            "amenities" => "nullable|array",
            "amenities.*" => "exists:amenities,id",
 
        ];
    }
}