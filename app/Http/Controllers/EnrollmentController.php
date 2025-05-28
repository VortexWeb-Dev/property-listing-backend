<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    public function enroll($courseId)
    {
        $user = Auth::user();

        $course = Course::findOrFail($courseId);
        $course->enrolledUsers()->syncWithoutDetaching([$user->id]);

        return response()->json(['message' => 'Enrolled successfully.']);
    }

    public function myCourses()
    {
        $user = Auth::user();
        $courses = $user->enrolledCourses()->with('lessons')->get();

        return response()->json($courses);
    }
}