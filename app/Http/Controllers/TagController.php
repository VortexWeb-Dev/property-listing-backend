<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    
        public function index(Request $request)
    {
        $query = Tag::query();

        // Search by tag name (optional)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by multiple tag names
        if ($request->has('tag_name')) {
            $tagNames = (array) $request->input('tag_name');
            $query->whereIn('name', $tagNames);
        }

        // Pagination - default 10 per page
        $tags = $query->paginate($request->input('per_page', 10))->appends($request->query());

        return response()->json($tags);
    }



    
    public function store(Request $request)
    {
        if (Gate::denies('tag.create')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|unique:tags,name|max:100',
        ]);

        $tag = Tag::create($validated);
        return response()->json($tag, 201);
    }

    
    public function update(Request $request, $id)
    {
        if (Gate::denies('tag.edit')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tag = Tag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:tags,name,' . $id . '|max:100',
        ]);

        $tag->update($validated);
        return response()->json($tag);
    }

    public function show($id)
    {
        $tag = Tag::find($id);

        if (!$tag) {
            return response()->json(['message' => 'Tag not found'], 404);
        }

        return response()->json($tag);
    }

    
    public function destroy($id)
    {
        if (Gate::denies('tag.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tag = Tag::findOrFail($id);
        $tag->delete();
        return response()->json(['message' => 'Deleted']);
    }
}