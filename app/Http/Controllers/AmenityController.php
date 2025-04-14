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
            'amenity_code' => 'required|string|unique:amenities',
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
            'amenity_code' => [
                'sometimes',
                'string',
                Rule::unique('amenities', 'amenity_code')->ignore($amenity->id),
            ],
        ]);

        $amenity->update($validated);

        return response()->json([
            'message' => 'Amenity updated successfully.',
            'data' => $amenity,
        ]);
    }

    
    public function destroy($amenity_id)
    {
        $amenity = Amenity::find($amenity_id);

        if (!$amenity) 
        {
            return response()->json(['message' => 'Amenity not found'], 404);
        }

        $amenity->delete();

        return response()->json(['message' => 'Amenity deleted successfully']);
    }

}