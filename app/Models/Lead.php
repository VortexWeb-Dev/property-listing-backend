<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'title', 'client_name', 'client_email', 'client_phone',
        'property_reference', 'property_link', 'tracking_link',
        'comment', 'responsible_person', 'company_id', 'source', 'stage',
    ];

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_person');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}