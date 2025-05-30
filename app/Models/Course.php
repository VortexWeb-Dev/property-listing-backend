<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'offered_by', 'number_of_lectures', 'total_duration'];


    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'course_tags');
    }

        public function enrolledUsers()
    {
        return $this->belongsToMany(User::class)
                    ->withTimestamps()
                    ->withPivot('enrolled_at');
    }

        public function company()
    {
        return $this->belongsTo(Company::class, 'offered_by');
    }


}