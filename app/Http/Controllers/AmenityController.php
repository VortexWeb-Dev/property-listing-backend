<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AmenityController extends Controller
{
    public function index()
    {
        return response()->json(Amenity::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amenity_name' => 'required|string',
            'amenity_code' => 'required|string',
            'amenity_type' => 'nullable|in:commercial,private',
        ]);

        return response()->json(Amenity::create($validated), 201);
    }

    public function show($id)
    {

        $amenity = Amenity::find($id);


        if (!$amenity) 
        {
            return response()->json(['message' => 'Amenity not found'], 404);
        }


        return response()->json($amenity);
    }


    public function update(Request $request, $amenity_id)
    {
        $amenity = Amenity::find($amenity_id);

        if (!$amenity) 
        {
            return response()->json(['message' => 'Amenity not found with this ID'], 404);
        }

        $validated = $request->validate([
            'amenity_name' => 'sometimes|string',
            'amenity_code' => 'sometimes|string',
            'amenity_type' => 'nullable|in:commercial,private',
        ]);

        $amenity->update($validated);

        return response()->json([
            'message' => 'Amenity updated successfully.',
            'data' => $amenity,
        ]);
    }

     public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        if (!is_array($ids) || empty($ids)) {
            return response()->json([
                "message" => "Please provide a non-empty array of amenity IDs."
            ], 400);
        }

        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                return response()->json([
                    "message" => "Invalid ID in array: $id"
                ], 400);
            }
        }

        $amenities = Amenity::whereIn('id', $ids)->get();

        $existingIds = $amenities->pluck('id')->toArray();
        $missingIds = array_diff($ids, $existingIds);

        if (!empty($missingIds)) {
            return response()->json([
                "message" => "Some amenity IDs were not found.",
                "missing_ids" => array_values($missingIds)
            ], 404);
        }

        // Step 1: Detach related listings
        foreach ($amenities as $amenity) {
            $amenity->listings()->detach(); // This removes records from pivot table
        }

        // Step 2: Delete amenities
        Amenity::whereIn('id', $existingIds)->delete();

        return response()->json([
            "message" => "Amenity(ies) deleted successfully.",
            "deleted_ids" => $existingIds
        ]);
    }


}