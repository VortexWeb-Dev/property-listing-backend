<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table="locations";
    protected $fillable = ['city', 'community', 'sub_community', 'building', 'location', 'type'];
}