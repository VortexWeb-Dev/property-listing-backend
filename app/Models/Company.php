<?php

namespace App\Models;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory; 
    protected $table="companies";
    protected $fillable = [
        'name',
        'bitrix_api',
        'email',
        'phone',
        'website',
        'admins', 
        'logo_url',
        'watermark_url',
        'slug',
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





    protected static function booted()
    {
        static::creating(function ($company) {
            $company->slug = static::generateUniqueSlug($company->name);
        });

        static::updating(function ($company) {
            // Only regenerate slug if the name has changed
            if ($company->isDirty('name')) {
                $company->slug = static::generateUniqueSlug($company->name);
            }
        });
    }

    protected static function generateUniqueSlug($name)
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;

        // If slug already exists, append a random color
        $colors = ['red', 'blue', 'green', 'yellow', 'orange', 'purple', 'violet', 'teal', 'lime', 'cyan', 'gold'];

        $i = 0;
        while (Company::where('slug', $slug)->exists()) {
            $color = $colors[array_rand($colors)];
            $slug = $baseSlug . '-' . $color;
            $i++;

            // Fallback to random string if too many collisions
            if ($i > 5) {
                $slug = $baseSlug . '-' . Str::random(5);
            }
        }

        return $slug;
    }


}