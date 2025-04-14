<?php

namespace App\Http\Controllers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    public function index()
    {
        return Photo::with('listing')->get();
    }

    public function store(Request $request)
    {
        $photo = Photo::create($request->all());
        return response()->json($photo, 201);
    }

    public function show($id)
    {
        return Photo::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $photo = Photo::findOrFail($id);
        $photo->update($request->all());
        return response()->json($photo);
    }

    public function destroy($id)
    {
        Photo::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}