<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
class Student extends Authenticatable implements JWTSubject
{
    protected $fillable = ["image", "age", "gender", "full_name", "admin_activation", "username", "password", "email", "id", "activation_code", "is_active", "free_points"];
    protected $appends = ['points'];

    public function getPointsAttribute()
    {
        return $this->free_points + $this->paid_points;
    }

public function notifications()
{
    return $this->morphMany(Notification::class, 'notifiable');
}

    public function studentCourseVideos()
    {
        return $this->hasMany(StudentCourseVideo::class);
    }

    public function savedCourse()
    {
        return $this->belongsToMany(Course::class, "student_saved_course", "student_id", "course_id");
    }

    public function addFreePoints($points = 1)
    {
        if ($this->free_points >= 10) {
            return; // لا شيء يحدث
        }

        $newPoints = $this->free_points + $points;
        $this->update([
            'free_points' => min(10, $newPoints)
        ]);
    }

    public function teachers()
    {
        return $this->belongsToMany(Student::class, "student_teacher", "student_id", "teacher_id");
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, "course_student", "student_id", "course_id")
            ->withPivot("status")
        ;
    }

    public function quizes()
    {
        return $this->belongsToMany(Quize::class, "student__quiz", "student_id", "quiz_id")->withPivot('is_rewarded',"completed_at");
    }
    public function notifications()
{
    return $this->morphMany(Notification::class, 'notifiable');
}

}
