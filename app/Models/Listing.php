<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'title',
        'title_deed',
        'property_type',
        'offering_type',
        'size',
        'unit_no',
        'bedrooms',
        'bathrooms',
        'parking',
        'furnished',
        'total_plot_size',
        'plot_size',
        'built_up_area',
        'layout_type',
        'project_name',
        'project_status',
        'sale_type',
        'developer_id',
        'build_year',
        'customer',
        'rera_permit',
        'rera_number',
        'rera_permit_issue_date',
        'rera_expiration_date',
        'contract_expiry_date',
        'rental_period',
        'rprice_price',
        'payment_method',
        'financial_status',
        'sale_type_1',
        'title(english)',
        'title(arabic)',
        'description(english)',
        'description(arabic)',
        'geopoints',
        'listing_owner',
        'landlord_name',
        'landlord_contact',
        'location_id',
        'availability',
        'available_from',
        'emirate_amount',
        'payment_option',
        'no_of_cheques',
        'contract_charges',
        'financial_status_id',
        'contract_expiry',
        'floor_plan',
        'qr_code',
        'brochure',
        'video_url',
        '360_view_url',
        'photos_urls',
        'status',
        'property_finder',
        'dubizzle',
        'website',
        'watermark',
       'company_id',
       'agent_id'
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

  public function owner()
{
    return $this->belongsTo(User::class, 'owner_id');
}

}