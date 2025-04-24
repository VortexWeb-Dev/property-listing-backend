<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory; 
    protected $table="companies";
    protected $fillable = [
        'name',
        'email',
        'phone',
        'website',
        'admins', 
        'logo_url',
        'watermark_url',
    ];
    protected $casts = [
        'admins' => 'array',
    ];
    
    protected $appends = ['admin_users']; // this auto-includes admin_users in JSON
    
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    public function adminUsers()
    {
        return User::whereIn('id', $this->admins ?? [])->where('role', 'admin')->get();
    }

    public function agents()
   {
    return $this->hasMany(User::class, 'company_id')->where('role', 'agent');
   }

   public function owners()
{
    return $this->hasMany(User::class)->where('role', 'owner');
}

   public function getAdminUsersAttribute()
{
    $adminIds = $this->admins ?? []; // fallback in case it's null
    return User::whereIn('id', $adminIds)->get();
}

}