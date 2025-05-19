<?php

use App\Models\Company;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return [
        'message' => 'Testing Listings By Vortex Web....'
    ];
});

Route::get('/check', function () {
    return [
        'message' => 'New Feature Analytics and Offplan Listing'
    ];
});

Route::get('/test', function () {
    return Company::with('users')->get();
});