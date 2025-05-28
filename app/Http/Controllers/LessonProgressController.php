<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonProgressController extends Controller
{
    public function markComplete($lessonId)
    {
        
        
        $user = Auth::user();

        $lesson = Lesson::findOrFail($lessonId);
        $lesson->completedUsers()->syncWithoutDetaching([$user->id]);

        return response()->json(['message' => 'Lesson marked as completed.']);
    }

    public function myCompletedLessons()
    {
        $user = auth()->user();
    
        $lessons = $user->completedLessons()
            ->with(['course', 'completedUsers'])
            ->get();
    
        return response()->json([
            'lessons' => $lessons
        ]);
    }
    

}