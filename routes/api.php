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

// Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    
    // Amenities
    Route::apiResource('amenities', AmenityController::class);
    Route::delete('/amenities', [AmenityController::class, 'destroy']);
    
    // Locations
    Route::apiResource('locations', LocationController::class);
    Route::delete('/locations', [LocationController::class, 'destroy']);
    Route::post('/bulk-upload/locations', [LocationController::class, 'bulkUploadLocations']);
    
    // Companies
    Route::apiResource('companies', CompanyController::class);
    Route::delete('/companies', [CompanyController::class, 'destroy']);

    // Users
    Route::apiResource('users', UserController::class);
    
    // Developer
    Route::apiResource('developers', DeveloperController::class);
    Route::post('/bulk-upload/developers', [DeveloperController::class, 'bulkUploadDevelopers']);
    Route::delete('/developers', [DeveloperController::class, 'destroy']);
   
    // Listing
    Route::apiResource('listings', ListingController::class);
    Route::apiResource('photos',   PhotoController::class);
    Route::get('/listing/create-info', [ListingController::class, 'createInfo']); // for super_admin
    Route::get('/listing/agents', [ListingController::class, 'agentsList']); // for admin
    Route::get('/unassociatedadmins', [UserController::class, 'unassociatedadmins']);
    Route::get('/associatedadmins', [UserController::class, 'associatedadmins']);
    Route::post('/listing/action', [ListingActionController::class, 'handleAction']);
    Route::post('/listing/agentbulktransfer', [ListingActionController::class, 'agentbulktransfer']);
    Route::get('/listOwners', [ListingController::class, 'listOwners']);
    Route::get('/agents/list', [ListingController::class, 'agentsList_for_agent']); //agent list for agent transfer
    

    // for file upload 
     Route::get('/s3/presigned-url', [PresignedController::class, 'getPresignedUrl']);
     
    // xml
    Route::get('/xml/{slug}/propertyfinder', [XmlController::class, 'propertyFinder']);
    Route::get('/xml/{slug}/bayut-dubizzle', [XmlController::class, 'bayutDubizzle']);
    Route::get('/xml/{slug}/website', [XmlController::class, 'website']);
    
    // Recycle-bin
    Route::get('/recycle-bin/listings', [RecycleBinController::class, 'index']);
    Route::put('/recycle-bin/listings/{id}/restore', [RecycleBinController::class, 'restore']);
    Route::delete('/recycle-bin/listings/{id}', [RecycleBinController::class, 'destroy']);
    
});