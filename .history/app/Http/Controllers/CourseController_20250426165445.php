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

            $path = imageUpload($request, $course->id, "course_image");
            $course->image = assetFromDisk("course_image", $path);
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

    public function updateCourse(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:4048',
            'level' => 'sometimes|required|in:0,1,2',
            'point_to_enroll' => 'sometimes|required|integer|min:0',
            'points_earned' => 'sometimes|required|integer|min:0',
            'skills' => 'sometimes|required|array',
            'skills.*' => 'exists:skills,id',
            'aquirements' => 'sometimes|required|array',
            'aquirements.*' => 'exists:skills,id',
            'category_id' => 'sometimes|required|exists:categories,id',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->has('point_to_enroll') && $request->has('points_earned')) {
                $pointsToEnroll = (int) $request->point_to_enroll;
                $pointsEarned = (int) $request->points_earned;

                if ($pointsToEnroll <= 10 && $pointsEarned > 10) {
                    $validator->errors()->add('points_earned', 'The earned points cannot exceed 10 when the course requires 10 or fewer points to enroll.');
                }
            }
        });

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            $course = Course::findOrFail($id);

            $course->fill($request->only([
                'name',
                'description',
                'level',
                'point_to_enroll',
                'points_earned',
                'category_id'
            ]));

            if ($request->hasFile('image')) {
                $path = imageUpload($request, $course->id, "course_image");
                $course->image = assetFromDisk("course_image", $path);
            }

            $course->save();

            if ($request->has('skills')) {
                $course->skills()->sync($request->skills);
            }

            if ($request->has('aquirements')) {
                $course->aquirements()->sync($request->aquirements);
            }

            DB::commit();

            return $this->returnSuccess('Course updated successfully', 200);
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
        $course = $teacher->courses()->where("id", $courseId)->first();
        $firstCourse = [
            "name" => $course->name,
            "status" => $course->status,
            "description" => $course->description,
            "image" => $course->image,
            "level" => $course->level,
            "point_to_enroll" => $course->point_to_enroll,
            "points_earned" => $course->points_earned
        ];
        if (!$course) {
            return $this->returnError("You don't have permission to see course details");
        }

        // تحميل الفيديوهات المرتبة
        $videos = $course->videos()
            ->orderBy("sequential_order")
            ->get()
            ->map(function ($video) {
                return (object) [
                    "type" => "video",
                    "id" => $video->id,
                    "title" => $video->title,
                    "description" => $video->description,
                    "path" =>$video->path,
                    "image" => $video->image,
                    "sequential_order" => $video->sequential_order,
                ];
            });

        // تحميل الكويزات
        $quizes = $course->quiezes()
            ->select("title", "from_video", "to_video", "is_final" , "id")
            ->get()
            ->map(function ($quiz) {
                return (object) [
                    "type" => "quiz",
                    "title" => $quiz->title,
                    "from_video" => $quiz->from_video,
                    "to_video" => $quiz->to_video,
                    "is_final" => $quiz->is_final,
                    "id" => $quiz->id
                ];
            });

        // تركيب الفيديوهات والكويزات معًا
        $videosAndQuiz = [];
        $videoMap = $videos->keyBy("id");

        foreach ($videos as $video) {
            $videosAndQuiz[] = $video;

            // بعد كل فيديو، نبحث إذا فيه كويز ينتهي عند هذا الفيديو
            foreach ($quizes as $quiz) {
                if ($quiz->to_video == $video->id) {
                    $videosAndQuiz[] = $quiz;
                }
            }
        }

        // باقي البيانات
        $requirements = $course->skills->pluck("title");
        $aquirements = $course->aquirements->pluck("title");
        $attachments = $course->attachments->map(function ($attachment) {
            $attachment->file_path = assetFromDisk("course_attachments", $attachment->file_path);
            return $attachment;
        });
        $category = $course->category->title;

        $data = [
            "course" =>  $firstCourse,
            "requirements" => $requirements,
            "aquirements" => $aquirements,
            "attachments" => $attachments,
            "category" => $category,
            "videosAndQuiz" => $videosAndQuiz
        ];

        return $this->returnData("", $data);
    }



}
