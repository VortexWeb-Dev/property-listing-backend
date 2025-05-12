<?php

use App\Models\Company;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return [
        'message' => 'Testing Listings By Vortex Web....'
    ];
});

Route::get('/test', function () {
    return Company::with('users')->get();
});