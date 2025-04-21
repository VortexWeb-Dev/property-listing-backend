<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = ['listing_id', 'image_url', 'is_main', 'is_active'];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}