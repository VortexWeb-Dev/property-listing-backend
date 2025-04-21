<?php

use App\Models\Company;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return [
        'message' => 'Hello World'
    ];
});

Route::get('/test', function () {
    return Company::with('users')->get();
});