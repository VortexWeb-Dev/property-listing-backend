<?php

use App\Models\Company;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return [
        'message' => 'Listings'
    ];
});

Route::get('/test', function () {
    return Company::with('users')->get();
});