<?php

namespace App\Policies;

use App\Models\OffplanListing;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OffplanListingPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasPermission('offplanlisting.view');
    }

    public function view(User $user,  OffplanListing $offplanListing)
    {
        
        return $user->hasPermission('offplanlisting.view');
    }

    public function create(User $user)
    {
        return $user->hasPermission('offplanlisting.create');
    }

    public function update(User $user,  OffplanListing $offplanListing)
    {
        return $user->hasPermission('offplanlisting.update');
    }

    public function delete(User $user,  OffplanListing $offplanListing)
    {
        return $user->hasPermission('offplanlisting.delete');
    }
}