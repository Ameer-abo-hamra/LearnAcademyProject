<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Storage;

class Course extends Model
{
    use HasFactory;
    public function videosCount()
    {
        return $this->videos()->count();
    }

    public function savedCourse()
    {
        return $this->belongsToMany(Student::class, "student_saved_course", "course_id", "student_id");
    }
    protected $fillable = ["points_earned", "category_id", "point_to_enroll", "name", "description", "specilization_id", "teacher_id", "status", "image", "level"];
    public function getImageUrlAttribute()
    {
        return $this->image
            ? Storage::disk('course_image')->url($this->image)
            : null;
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, "teacher_id");
    }

    public function quiezes()
    {
        return $this->hasMany(Quize::class, "course_id");
    }
    public function studentCourseVideos()
    {
        return $this->hasMany(StudentCourseVideo::class);
    }
    public function videos()
    {
        return $this->hasMany(Video::class, "course_id");
    }
    public function attachments()
    {
        return $this->hasMany(CourseAttachments::class, "course_id");
    }

    public function category()
    {
        return $this->belongsTo(Category::class, "category_id");
    }
    public function skills()
    {
        return $this->belongsToMany(Skill::class, "course_skill", "course_id", "skill_id");
    }

    public function aquirements()
    {
        return $this->belongsToMany(Skill::class, "course_aquirement", "course_id", "skill_id");
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, "course_student", "course_id", "student_id")
        with;
    }

    public function specilizations()
    {
        return $this->belongsToMany(Course::class, 'course_specialization', "course_id", "specialization_id");
    }
}
