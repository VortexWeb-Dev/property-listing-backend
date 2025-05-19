<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OffplanPhoto extends Model
{
    use HasFactory;

    protected $table='offplan_listings_photos';
    protected $fillable = ['offplan_listing_id', 'image_url', 'is_main', 'is_active'];

    public function offplanListing()
    {
        return $this->belongsTo(OffplanListing::class);
    }
}