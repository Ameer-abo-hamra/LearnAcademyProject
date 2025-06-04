<?php

namespace App\Http\Controllers;
use App\Events\AdminEvent;
use App\Models\Admin;
use App\Models\Course;
use App\Models\CourseAttachments;
use App\Models\Notification;
use App\Models\StudentCourseContent;
use App\Models\StudentCourseVideo;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Support\Facades\Http;
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
            'attachments' => 'nullable|array',
            'attachments.*.file' => 'required_without:attachments.*.text|file|mimes:pdf',
            'attachments.*.text' => 'required_without:attachments.*.file|string',
        ]);

        // ⛔ تحقق إضافي
        $validator->after(function ($validator) use ($request) {
            $pointsToEnroll = (int) $request->point_to_enroll;
            $pointsEarned = (int) $request->points_earned;

            if ($pointsToEnroll >= 0 && $pointsToEnroll <= 10 && $pointsEarned > 10) {
                $validator->errors()->add('points_earned', 'The earned points cannot exceed 10 when the course requires 10 or fewer points to enroll.');
            }

            if ($request->has('attachments')) {
                foreach ($request->attachments as $index => $attachment) {
                    $file = $attachment['file'] ?? null;
                    $text = $attachment['text'] ?? null;

                    if (empty($file) && empty($text)) {
                        $validator->errors()->add(
                            "attachments.$index",
                            "Each attachment must have at least a file or a text."
                        );
                    }
                }
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
                'teacher_id' => u("teacher")->id,
                'category_id' => $request->category_id,
                'point_to_enroll' => $request->point_to_enroll,
                'points_earned' => $request->points_earned,
            ]);

            $path = imageUpload($request, $course->id, "course_image");
            $course->image = assetFromDisk("course_image", $path);
            $course->save();

            $course->skills()->sync($request->skills);
            $course->aquirements()->sync($request->aquirements);

            // ➡️ إضافة المرفقات إذا كانت موجودة
            if ($request->has('attachments')) {
                foreach ($request->attachments as $attachment) {
                    $filePath = null;

                    if (isset($attachment['file'])) {
                        $uploadedFile = $attachment['file'];
                        $folder_name = $course->teacher->id . '/' . $course->id;
                        $file_name = time() . '.' . $uploadedFile->getClientOriginalExtension();
                        $filePath = $uploadedFile->storeAs($folder_name, $file_name, 'course_attachments'); // أو غيّر 'public' لو عندك ديسك آخر
                        $filePath = assetFromDisk("course_attachments", $filePath);
                    }

                    CourseAttachments::create([
                        'file_path' => $filePath,
                        'text' => $attachment['text'] ?? null,
                        'course_id' => $course->id,
                    ]);
                }
            }

            DB::commit();

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("http://localhost:8005/api/v1/courses/value", [
                        'course_id' => (string) $course->id,
                        'value' => $request->skills, // أو أي قائمة أخرى مناسبة
                    ]);

            if ($response->successful()) {
                echo ("✅ External course data sent successfully" . $response->json());
            } else {
                echo ("❌ Failed to send external request" .
                    'status' . $response->status() .
                    'body' . $response->body()
                );
            }

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
            'attachments' => 'nullable|array',
            'attachments.*.file' => 'required_without:attachments.*.text|file|mimes:pdf',
            'attachments.*.text' => 'required_without:attachments.*.file|string',
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

            // 🆕 هنا نعالج الملحقات attachments
            if ($request->has('attachments')) {
                foreach ($request->attachments as $attachment) {
                    $filePath = null;

                    if (isset($attachment['file'])) {
                        $uploadedFile = $attachment['file'];
                        $folder_name = $course->teacher->id . '/' . $course->id;
                        $file_name = time() . '.' . $uploadedFile->getClientOriginalExtension();
                        $filePath = $uploadedFile->storeAs($folder_name, $file_name, 'course_attachments'); // أو غيّر 'public' لو عندك ديسك آخر
                        $filePath = assetFromDisk("course_attachments", $filePath);
                    }

                    $course->attachments()->create([
                        'file_path' => $filePath,
                        'text' => $attachment['text'] ?? null,
                    ]);
                }
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
        $course = Course::with('teacher')->find($course_id);

        if (!$course) {
            return $this->returnError('Course not found', 404);
        }

        // ✅ تحقق من وجود كويز نهائي لهذا الكورس
        $hasFinalQuiz = $course->quiezes()->where('is_final', true)->exists();

        if (!$hasFinalQuiz) {
            return $this->returnError('You must create a final quiz before publishing the course.');
        }

        // ✅ تحويل الحالة إلى 1 (بانتظار موافقة الأدمن)
        $course->status = 1;
        $course->save();

        // ✅ تجهيز الرسالة
        $message = [
            'title' => 'New course pending approval',
            'body' => "Teacher {$course->teacher->full_name} has submitted the course '{$course->name}' for review.",
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'level' => $course->level,
            ]
        ];

        // ✅ إرسال الإشعار إلى كل الأدمنز
        $admins = Admin::all();
        foreach ($admins as $admin) {
            // تخزين الإشعار في قاعدة البيانات
            Notification::create([
                'notifiable_id' => $admin->id,
                'notifiable_type' => Admin::class,
                'sender_id' => $course->teacher->id,
                'sender_type' => \App\Models\Teacher::class,
                'title' => $message['title'],
                'body' => $message['body'],
                'data' => json_encode($message['course']),
            ]);

            // بث الإشعار عبر القناة المخصصة للأدمن
            broadcast(new AdminEvent($message, $admin->id));
        }

        return $this->returnSuccess('Course published successfully, now wait for admin to accept 🙂');
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
        if (!$course) {
            return $this->returnError("this course does not exist");
        }

        $firstCourse = [
            "name" => $course->name,
            "status" => $course->status,
            "description" => $course->description,
            "image" => $course->image,
            "level" => $course->level,
            "point_to_enroll" => $course->point_to_enroll,
            "points_earned" => $course->points_earned
        ];

        // تحميل الفيديوهات المرتبة
        $videos = $course->videos()->orderBy("sequential_order")->get();
        $videoMap = $videos->keyBy("id");

        // تحميل الكويزات
        $quizes = $course->quiezes()
            ->select("title", "from_video", "to_video", "is_final", "id")
            ->get();

        // دمج الفيديوهات والكويزات بترتيب جاهز
        $videosAndQuiz = collect();

        foreach ($videos as $video) {
            // إضافة الفيديو
            $videosAndQuiz->push((object) [
                "type" => "video",
                "id" => $video->id,
                "title" => $video->title,
                "description" => $video->description,
                "path" => $video->path,
                "image" => $video->image,
                "sequential_order" => $video->sequential_order,
            ]);

            // إضافة أي كويز ينتهي عند هذا الفيديو
            foreach ($quizes as $quiz) {
                if ($quiz->to_video == $video->sequential_order) {
                    $videosAndQuiz->push((object) [
                        "type" => "quiz",
                        "id" => $quiz->id,
                        "title" => $quiz->title,
                        "from_video" => $quiz->from_video,
                        "to_video" => $quiz->to_video,
                        "is_final" => $quiz->is_final,
                        // نربط الكويز بالفيديو لكن لا نحتاج ترتيب إضافي هنا لأنه موضوع بعد الفيديو
                    ]);
                }
            }
        }

        // باقي البيانات
        $requirements = $course->skills->pluck("title");
        $aquirements = $course->aquirements->pluck("title");
        $attachments = $course->attachments;
        $category = $course->category->title;

        $data = [
            "course" => $firstCourse,
            "requirements" => $requirements,
            "aquirements" => $aquirements,
            "attachments" => $attachments,
            "category" => $category,
            "videosAndQuiz" => $videosAndQuiz->values(), // ترتيب نهائي
        ];

        return $this->returnData("", $data);
    }



    public function getCourseForEnrolledStudents($course_id)
    {
        $student = u("student");

        // التحقق من الاشتراك
        $isEnrolled = $student->courses()
            ->where('courses.id', $course_id)
            ->exists();

        if (!$isEnrolled) {
            return $this->returnError("You can't access this course. Please enroll first.");
        }

        // جلب بيانات الدورة
        $course = Course::find($course_id);
        if (!$course) {
            return $this->returnError("Course not found.");
        }

        // البيانات الأساسية للدورة
        $firstCourse = [
            "name" => $course->name,
            "status" => $course->status,
            "description" => $course->description,
            "image" => $course->image,
            "level" => $course->level,
            "point_to_enroll" => $course->point_to_enroll,
            "points_earned" => $course->points_earned
        ];

        // جلب المحتوى المرتبط بالطالب والكورس
        $contents = StudentCourseContent::where('student_id', $student->id)
            ->where('course_id', $course_id)
            ->orderBy('order_index')
            ->get();

        $videosAndQuiz = collect();

        foreach ($contents as $content) {
            $model = $content->content;

            if (!$model) {
                continue; // في حال تم حذف الفيديو أو الكويز
            }

            if ($content->content_type === \App\Models\Video::class) {
                $videosAndQuiz->push((object) [
                    "type" => "video",
                    "id" => $model->id,
                    "title" => $model->title,
                    "description" => $model->description,
                    "path" => $model->path,
                    "image" => $model->image,
                    "sequential_order" => $model->sequential_order,
                    "is_locked" => $content->locked,
                    "completed_at" => $content->completed_at,
                ]);
            } elseif ($content->content_type === \App\Models\Quize::class) {
                $videosAndQuiz->push((object) [
                    "type" => "quiz",
                    "id" => $model->id,
                    "title" => $model->title,
                    "from_video" => $model->from_video_id,
                    "to_video" => $model->to_video_id,
                    "is_final" => $model->is_final,
                    "is_locked" => $content->locked,
                    "completed_at" => $content->completed_at,
                ]);
            }
        }

        // جلب باقي البيانات
        $requirements = $course->skills->pluck("title");
        $aquirements = $course->aquirements->pluck("title");
        $attachments = $course->attachments;
        $category = optional($course->category)->title;

        // تجميع البيانات
        $data = [
            "course" => $firstCourse,
            "requirements" => $requirements,
            "aquirements" => $aquirements,
            "attachments" => $attachments,
            "category" => $category,
            "videosAndQuiz" => $videosAndQuiz->values(),
        ];

        return $this->returnData("", $data);
    }


    public function getCourseForStudent($course_id)
    {
        $student = u("student");

        // جلب الدورة
        $course = Course::find($course_id);
        if (!$course) {
            return $this->returnError("Course not found.");
        }

        $isEnrolled = $student->courses()->where('courses.id', $course_id)->exists();
        $isSaved = $student->savedCourse()->where('courses.id', $course_id)->exists();

        // بيانات الدورة الأساسية
        $firstCourse = [
            "name" => $course->name,
            "status" => $course->status,
            "description" => $course->description,
            "image" => $course->image,
            "level" => $course->level,
            "point_to_enroll" => $course->point_to_enroll,
            "points_earned" => $course->points_earned,
            "is_enrolled" => $isEnrolled,
            "is_saved" => $isSaved,
        ];

        // تحميل الفيديوهات المرتبة
        $videos = $course->videos()
            ->orderBy("sequential_order")
            ->get();

        // تحميل الكويزات
        $quizzes = $course->quiezes()
            ->select("title", "from_video", "to_video", "is_final", "id")
            ->get();

        // دمج الفيديوهات والكويزات حسب الترتيب
        $videosAndQuiz = collect();

        foreach ($videos as $video) {
            // إضافة الفيديو
            $videosAndQuiz->push((object) [
                "type" => "video",
                "id" => $video->id,
                "title" => $video->title,
                "description" => $video->description,
                "image" => $video->image,
                "sequential_order" => $video->sequential_order,
            ]);

            // إضافة الكويزات التي تنتهي عند هذا الفيديو
            foreach ($quizzes as $quiz) {
                if ($quiz->to_video == $video->sequential_order) {
                    $videosAndQuiz->push((object) [
                        "type" => "quiz",
                        "id" => $quiz->id,
                        "title" => $quiz->title,
                        "from_video" => $quiz->from_video,
                        "to_video" => $quiz->to_video,
                        "is_final" => $quiz->is_final,
                    ]);
                }
            }
        }

        // تحميل باقي البيانات
        $requirements = $course->skills->pluck("title");
        $aquirements = $course->aquirements->pluck("title");
        $attachments = $course->attachments;
        $category = optional($course->category)->title;

        // تجميع البيانات النهائية
        $data = [
            "course" => $firstCourse,
            "requirements" => $requirements,
            "aquirements" => $aquirements,
            "attachments" => $attachments,
            "category" => $category,
            "videosAndQuiz" => $videosAndQuiz->values(),
        ];

        return $this->returnData("", $data);
    }


    public function getInProgressCourses()
    {
        $teacher = u('teacher');
        $courses = $teacher->courses()
            ->where("status", 0)
            ->get();

        return $this->returnData("In-progress courses fetched successfully", $courses);
    }

    public function getPendingCourses()
    {
        $teacher = u('teacher');
        $courses = $teacher->courses()
            ->where("status", 1)
            ->get();

        return $this->returnData("Pending courses fetched successfully", $courses);
    }

    public function getPublishedCourses()
    {
        $teacher = u('teacher');
        $courses = $teacher->courses()
            ->where("status", 2)
            ->get();

        return $this->returnData("Published courses fetched successfully", $courses);
    }



}
