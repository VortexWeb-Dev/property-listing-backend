<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LessonController extends Controller
{
    
        public function index(Request $request)
    {

        if (Gate::denies('lesson.show')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $query = Lesson::query();

        //  Search by title or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by course_id
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        //  Pagination (default 10 per page)
        $perPage = $request->get('per_page', 10);
        $lessons = $query->with('course')->paginate($perPage);

        //  Append query params to pagination URLs
        $lessons->appends($request->query());

        return response()->json($lessons);
    }

    
        public function store(Request $request)
    {
        

        if (Gate::denies('lesson.create')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url',
            'pdf_url' => 'nullable|url',
            'course_id' => 'required|exists:courses,id',
        ]);

        $lesson = Lesson::create($validated);

        return response()->json($lesson, 201);
    }


  
    public function update(Request $request, $id)
    {
        if (Gate::denies('lesson.edit')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $lesson = Lesson::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url',
            'pdf_url' => 'nullable|url',
        ]);

        $lesson->update($validated);
        return response()->json($lesson);
    }

    public function show($id)
    {
        if (Gate::denies('lesson.show')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        $lesson = Lesson::find($id);
    
        if (!$lesson) {
            return response()->json(['message' => 'Lesson not found'], 404);
        }
    
        return response()->json($lesson);
    }
    
   
    public function destroy($id)
    {
        if (Gate::denies('lesson.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $lesson = Lesson::findOrFail($id);
        $lesson->delete();
        return response()->json(['message' => 'Deleted']);
    }
}