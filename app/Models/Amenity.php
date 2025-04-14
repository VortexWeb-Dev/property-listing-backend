<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    use HasFactory;

    protected $fillable = ['amenity_name', 'amenity_code'];

    public function listings()
    {
    return $this->belongsToMany(Listing::class, 'amenity_listing');
    }

}