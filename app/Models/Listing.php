<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no', 'title', 'property_type', 'offering_type', 'size',
        'unit_no', 'bedrooms', 'bathrooms', 'parking', 'furnished',
        'total_plot_size', 'plot_size', 'built_up_area', 'layout_type',
        'project_name', 'project_status', 'sale_type', 'developer_id', 'build_year',
        'customer', 'rera_permit_number', 'rera_issue_date', 'rera_expiration_date',
        'contract_expiry_date', 'rental_period', 'price', 'payment_method',
        'financial_status', 'sale_type_1', 'title_en', 'title_ar', 'desc_en',
        'desc_ar', 'geopoints', 'listing_owner', 'landlord_name', 'landlord_contact',
        'pf_location', 'bayut_location', 'availability', 'available_from',
        'emirate_amount', 'payment_option', 'no_of_cheques', 'contract_charges',
        'financial_status_id', 'contract_expiry', 'floor_plan', 'qr_code', 'brochure',
        'video_url', '360_view_url', 'watermark', 'pf_enable', 'bayut_enable',
        'dubizzle_enable', 'website_enable', 'company_id', 'agent_id', 'owner_id',
        'status','dtcm_permit_number','landlord_email','comments','pf_agent_id',
        'website_agent_id', 'bayut_dubizzle_agent_id'

    ];
    
    

    public function photos()
    {
        return $this->hasMany(Photo::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'amenity_listing');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
    
        public function pfAgent()
    {
        return $this->belongsTo(User::class, 'pf_agent_id');
    }

    public function websiteAgent()
    {
        return $this->belongsTo(User::class, 'website_agent_id');
    }

    public function bayutDubizzleAgent()
    {
        return $this->belongsTo(User::class, 'bayut_dubizzle_agent_id');
    }


    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function developer()
    {
        return $this->belongsTo(User::class, 'developer_id');
    }

        public function location()
    {
        return $this->belongsTo(Location::class, 'pf_location');
    }

    

        public function pfLocation()
    {
        return $this->belongsTo(Location::class, 'pf_location');
    }

    public function bayutLocation()
    {
        return $this->belongsTo(Location::class, 'bayut_location');
    }


    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
    
    public function resolvedAgent($channel)
{
    return match ($channel) {
        'propertyfinder' => $this->pfAgent ?? $this->agent,
        'website' => $this->websiteAgent ?? $this->agent,
        'bayut_dubizzle' => $this->bayutDubizzleAgent ?? $this->agent,
        default => $this->agent,
    };
}

}