<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\XmlController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\AmenityController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\PresignedController;
use App\Http\Controllers\RecycleBinController;
use App\Http\Controllers\ListingActionController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('developers', DeveloperController::class);
    Route::apiResource('amenities', AmenityController::class);
    Route::apiResource('locations', LocationController::class);
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('users', UserController::class);
    Route::post('/bulk-upload/developers', [DeveloperController::class, 'bulkUploadDevelopers']);
    Route::post('/bulk-upload/locations', [LocationController::class, 'bulkUploadLocations']);
    Route::apiResource('listings', ListingController::class);
    Route::apiResource('photos',   PhotoController::class);
    Route::get('/listing/create-info', [ListingController::class, 'createInfo']); // for super_admin
    Route::get('/listing/agents', [ListingController::class, 'agentsList']); // for admin
    Route::get('/unassociatedadmins', [UserController::class, 'unassociatedadmins']);
    Route::get('/associatedadmins', [UserController::class, 'associatedadmins']);
    Route::post('/listing/action', [ListingActionController::class, 'handleAction']);
    Route::post('/listing/agentbulktransfer', [ListingActionController::class, 'agentbulktransfer']);
    
    // xml
    Route::get('/xml/{slug}/propertyfinder', [XmlController::class, 'propertyFinder']);
    Route::get('/xml/{slug}/bayut-dubizzle', [XmlController::class, 'bayutDubizzle']);
    Route::get('/xml/{slug}/website', [XmlController::class, 'website']);
    
    Route::get('/listOwners', [ListingController::class, 'listOwners']);
    
    // for file upload
    Route::get('/s3/presigned-url', [PresignedController::class, 'getPresignedUrl']);

    // Recycle-bin
    Route::get('/recycle-bin/listings', [RecycleBinController::class, 'index']);
    Route::put('/recycle-bin/listings/{id}/restore', [RecycleBinController::class, 'restore']);
    Route::delete('/recycle-bin/listings/{id}', [RecycleBinController::class, 'destroy']);
    
});