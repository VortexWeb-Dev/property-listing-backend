<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'title', 'description', 'video_url', 'pdf_url', 'duration'];


    public function course()
    {
        return $this->belongsTo(Course::class);
    }

      // app/Models/Lesson.php
public function completedUsers()
{
    return $this->belongsToMany(User::class)->withPivot('completed_at')->withTimestamps();
}


}