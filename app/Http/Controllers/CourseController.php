<?php

namespace App\Http\Controllers;


use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    // GET /api/courses
    public function index(Request $request)
{
    $query = Course::query();

    //  Search
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    //  Filter by tag
    if ($request->filled('tag')) {
        $tag = $request->tag;
        $query->whereHas('tags', function ($q) use ($tag) {
            $q->where('name', $tag);
        });
    }

    //  Pagination
    $perPage = $request->get('per_page', 10);
    $courses = $query->with('tags')->paginate($perPage);

    // ➕ Preserve query strings in pagination URLs
    $courses->appends($request->query());

    return response()->json($courses);
}


    
    public function show($id)
    {
        $course = Course::findOrFail($id);
        return response()->json($course);
    }

    
        public function store(Request $request)
    {
        if (Gate::denies('course.create')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        $course = Course::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['tag_ids'])) {
            $course->tags()->sync($validated['tag_ids']); // attach tags
        }

        return response()->json($course->load('tags'), 201);
    }


   
        public function update(Request $request, $id)
    {
        if (Gate::denies('course.edit')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        $course->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['tag_ids'])) {
            $course->tags()->sync($validated['tag_ids']); // Update tag associations
        }

        return response()->json($course->load('tags'));
    }


   
    public function destroy($id)
    {
        if (Gate::denies('course.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json(['message' => 'Deleted']);
    }
}