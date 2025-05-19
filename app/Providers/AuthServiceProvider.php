<?php

namespace App\Providers;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        OffplanListing::class => OffplanListingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    
     public function boot()
     {
         $permissions = Config::get('permissions');
     
         $allPermissions = collect($permissions)->flatten()->unique();
     
         foreach ($allPermissions as $permission) {
             Gate::define($permission, function ($user) use ($permissions, $permission) {
                 $userPermissions = $permissions[$user->role] ?? [];
                 return in_array($permission, $userPermissions);
             });
         }
     }
     
}