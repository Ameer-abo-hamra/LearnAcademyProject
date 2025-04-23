<?php

namespace App\Http\Controllers;
use App\Models\Course;
use App\Traits\ResponseTrait;
use DB;
use Validator;
use Illuminate\Http\Request;


class CourseController extends Controller
{
    use ResponseTrait;
    public function makeCourse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:4048',
            'level' => 'required|in:0,1,2',
            'teacher_id' => 'required|exists:teachers,id',
            'point_to_enroll' => 'required|integer|min:0',
            'points_earned' => 'required|integer|min:0',
            'skills' => 'required|array',
            'skills.*' => 'exists:skills,id',
            'aquirements' => 'required|array',
            'aquirements.*' => 'exists:skills,id',
            'category_id' => 'required|exists:categories,id',
        ]);

        // ⛔ check conditional rule manually
        $validator->after(function ($validator) use ($request) {
            $pointsToEnroll = (int) $request->point_to_enroll;
            $pointsEarned = (int) $request->points_earned;

            if ($pointsToEnroll >= 0 && $pointsToEnroll <= 10 && $pointsEarned > 10) {
                $validator->errors()->add('points_earned', 'The earned points cannot exceed 10 when the course requires 10 or fewer points to enroll.');
            }
        });

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            $course = Course::create([
                'name' => $request->name,
                'description' => $request->description,
                'image' => "/",
                'level' => $request->level,
                'teacher_id' => $request->teacher_id,
                'category_id' => $request->category_id,
                'point_to_enroll' => $request->point_to_enroll,
                'points_earned' => $request->points_earned,
            ]);

            $course->image = imageUpload($request, $course->id, "course_image");
            $course->image = assetFromDisk("course_image","")
            $course->save();

            $course->skills()->sync($request->skills);
            $course->aquirements()->sync($request->aquirements);

            DB::commit();

            return $this->returnSuccess('Course created successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError('Something went wrong: ' . $e->getMessage());
        }

    }


    public function publishCourse($course_id)
    {
        // ✅ تحقق من وجود الكورس
        $course = Course::find($course_id);

        if (!$course) {
            return $this->returnError('Course not found', 404);
        }

        // ✅ تحقق من وجود كويز نهائي لهذا الكورس
        $hasFinalQuiz = $course->quiezes()->where('is_final', true)->exists();

        if (!$hasFinalQuiz) {
            return $this->returnError('You must create a final quiz before publishing the course.');
        }

        // ✅ تحويل الحالة إلى 1 (معلَن)
        $course->status = 1;
        $course->save();

        return $this->returnSuccess('Course published successfully , now wait admin to accept :) ');
    }

    public function getTeacherCourses(Request $request)
    {

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page_number', 1);

        $courses = u("teacher")->courses()->paginate($perPage, ['*'], 'page', $page);
        return $this->returnData("courses", $courses->items(), 200, $courses);

    }
    public function getTeacherCoursesTitleId(Request $request)
    {
        $courses = u("teacher")->courses()
            ->select("id", "name")
            // ->where("status" , 3)
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->name
                ];
            });

        return $this->returnData("courses", $courses);
    }

    public function getCourseDetails($courseId)
    {
        $teacher = u("teacher");
        $course = $teacher->courses()->where("id", "=", $courseId)->first();
        if (!$course) {
            return $this->returnError("you dont have permission to see course datails");
        }
        $requirements = $course->skills->select("title");
        $aquirements = $course->aquirements->select("title");
        $attachments = $course->attachments->map(function ($attachment) {
            $attachment->file_path = assetFromDisk("course_attachments", $attachment->file_path);
            return $attachment;
        });

        $category = $course->category->title;
        $videos = $course->videos->map(function ($video) {
            $video->path = assetFromDisk("streamable_videos", $video->path);
            $video->image = assetFromDisk("video_thumbnail", $video->image);
            return $video;
        });
        $quizes = $course->quiezes->select("from_video", "to_video", "title");
        $data = [
            "requirements" => $requirements,
            "aquirements" => $aquirements,
            "attachments" => $attachments,
            "category" => $category,
            "viedos" => $videos,
            "quizes" => $quizes
        ];

        return $this->returnData("", $data);

    }

}
