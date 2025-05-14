<?php

namespace App\Http\Controllers\Admin;

use App\Events\TeacherEvent;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Specilization;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;
use Str;
use Validator;

class UserController extends Controller
{
    use ResponseTrait;
    // ======= STUDENTS =======

    public function createStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:50|unique:students',
            'password' => 'required|string|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/',
            'full_name' => 'required|string',
            'username' => 'required|string|min:4|max:50|unique:students',
            'age' => 'required|integer|min:16|max:100',
            'gender' => 'required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4048',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            $code = Str::random(6); // كود التفعيل من صنع النظام

            $student = Student::create([
                'full_name' => $request->full_name,
                'password' => Hash::make($request->password),
                'email' => $request->email,
                'username' => $request->username,
                'activation_code' => $code,
                'age' => $request->age,
                'gender' => $request->gender,
                'is_active' => true, // أدمن أنشأ الحساب فهو مفعل تلقائياً
                'admin_activation' => true,
                'image' => '', // نحدّثه إذا وجدت صورة
            ]);

            if ($request->hasFile("image")) {
                $image = imageUpload($request, $student->id, "student_image");
                $path = assetFromDisk("student_image", $image);
                $student->image = $path;
                $student->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Student account created successfully by admin.',
                'data' => $student
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateStudent(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|required|string|email|max:50|unique:students,email,' . $student->id,
            'password' => 'nullable|string|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/',
            'full_name' => 'sometimes|required|string',
            'username' => 'sometimes|required|string|min:4|max:50|unique:students,username,' . $student->id,
            'age' => 'sometimes|required|integer|min:16|max:100',
            'gender' => 'sometimes|required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4048',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            $student->fill($request->except(['password', 'image']));

            if ($request->filled('password')) {
                $student->password = Hash::make($request->password);
            }

            if ($request->hasFile('image')) {
                $image = imageUpload($request, $student->id, "student_image");
                $path = assetFromDisk("student_image", $image);
                $student->image = $path;
            }

            $student->save();

            DB::commit();

            return $this->returnData('Student updated successfully.', $student);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();
        return $this->returnSuccess("Student deleted");
    }

    // ======= TEACHERS =======

    public function createTeacher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:teachers',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:4048',
            'specialization' => 'required|string|max:255',
            'age' => 'required|integer|min:16|max:100',
            'gender' => 'required|in:0,1',
            'password' => 'required|string|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
            'username' => 'required|string|min:4|max:50|unique:teachers',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $code = Str::random(6);

            $teacher = Teacher::create([
                'full_name' => $request->full_name,
                'password' => Hash::make($request->password),
                'email' => $request->email,
                'username' => $request->username,
                'activation_code' => $code,
                'age' => $request->age,
                'specialization' => $request->specialization,
                'gender' => $request->gender,
                'image' => '/',
                'is_active' => true,
                'admin_activation' => true,
            ]);

            $teacher->image = imageUpload($request, $teacher->id, "teacher_image");
            $teacher->save();

            DB::commit();

            return $this->returnData("Teacher created successfully by admin.", $teacher);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError($e->getMessage());
        }
    }

    public function updateTeacher(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:teachers,email,' . $teacher->id,
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:4048',
            'specialization' => 'sometimes|required|string|max:255',
            'age' => 'sometimes|required|integer|min:16|max:100',
            'gender' => 'sometimes|required|in:0,1',
            'username' => 'sometimes|required|string|min:4|max:50|unique:teachers,username,' . $teacher->id,
            'password' => 'nullable|string|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            if ($request->has('full_name'))
                $teacher->full_name = $request->full_name;
            if ($request->has('email'))
                $teacher->email = $request->email;
            if ($request->has('username'))
                $teacher->username = $request->username;
            if ($request->has('specialization'))
                $teacher->specialization = $request->specialization;
            if ($request->has('age'))
                $teacher->age = $request->age;
            if ($request->has('gender'))
                $teacher->gender = $request->gender;

            if ($request->filled('password')) {
                $teacher->password = Hash::make($request->password);
            }

            if ($request->hasFile('image')) {
                $teacher->image = imageUpload($request, $teacher->id, "teacher_image");
            }

            $teacher->save();

            DB::commit();

            return $this->returnData("Teacher updated successfully.", $teacher);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError($e->getMessage());
        }
    }


    public function deleteTeacher($id)
    {
        $teacher = Teacher::findOrFail($id);

        try {
            $teacher->delete();
            return $this->returnSuccess("Teacher deleted successfully.");
        } catch (\Exception $e) {
            return $this->returnError("Failed to delete teacher: " . $e->getMessage());
        }
    }


    public function getCourseSubscriptions()
    {
        try {
            $subscriptions = DB::table('course_student')
                ->join('students', 'course_student.student_id', '=', 'students.id')
                ->join('courses', 'course_student.course_id', '=', 'courses.id')
                ->join('teachers', 'courses.teacher_id', '=', 'teachers.id')
                ->select(
                    'course_student.id',
                    'students.full_name as student_name',
                    'courses.name as course_name',
                    'courses.description as course_description',
                    'courses.level as course_level',
                    'courses.point_to_enroll as course_cost',
                    'courses.points_earned as course_reward',
                    'teachers.full_name as teacher_name',
                    'course_student.status',
                    'course_student.created_at'
                )
                ->orderBy('course_student.created_at', 'desc')
                ->get();

            return $this->returnData("Course subscriptions retrieved successfully", $subscriptions);
        } catch (\Exception $e) {
            return $this->returnError("Failed to retrieve subscriptions: " . $e->getMessage());
        }
    }


    public function getCompletedQuizzes()
    {
        try {
            $studentsWithCompletedQuizzes = Student::whereHas('quizes', function ($query) {
                $query->whereNotNull('student__quiz.completed_at');
            })
                ->with([
                    'quizes' => function ($query) {
                        $query->whereNotNull('student__quiz.completed_at')
                            ->with('course');
                    }
                ])
                ->get();

            $result = [];

            foreach ($studentsWithCompletedQuizzes as $student) {
                foreach ($student->quizes as $quiz) {
                    $result[] = [
                        'student_name' => $student->full_name,
                        'quiz_title' => $quiz->title,
                        'course_name' => optional($quiz->course)->name,
                        'completed_at' => $quiz->pivot->completed_at,
                        'is_rewarded' => $quiz->pivot->is_rewarded,
                    ];
                }
            }

            return $this->returnData("Completed quizzes retrieved successfully", $result);

        } catch (\Exception $e) {
            return $this->returnError("Failed to retrieve data: " . $e->getMessage());
        }
    }
    public function getStudents(Request $request)
    {
        try {
            $students = Student::select([
                'id',
                'full_name',
                'email',
                'username',
                'activation_code',
                'age',
                'image',
                'gender',
                'free_points',
                'paid_points',
                'is_active',
                'admin_activation',
                'created_at',
                'updated_at'
            ])
                ->orderBy('created_at', 'desc')
                ->paginate(10); // يمكنك تغيير العدد حسب الحاجة

            return $this->returnData('Students retrieved successfully', $students->getCollection());
        } catch (\Exception $e) {
            return $this->returnError('Failed to retrieve students: ' . $e->getMessage());
        }
    }

    public function getTeachers(Request $request)
    {
        try {
            $teachers = Teacher::select([
                'id',
                'full_name',
                'email',
                'username',
                'activation_code',
                'age',
                'image',
                'gender',
                'specialization',
                'is_active',
                'admin_activation',
                'created_at',
                'updated_at'
            ])
                ->orderBy('created_at', 'desc')
                ->paginate(10); // يمكنك تعديل العدد حسب الطلب

            return $this->returnData('Teachers retrieved successfully', $teachers->getCollection());
        } catch (\Exception $e) {
            return $this->returnError('Failed to retrieve teachers: ' . $e->getMessage());
        }
    }

    public function getReadyCourses(Request $request)
    {
        try {
            $courses = Course::where('status', 1)
                ->orderBy('created_at', 'desc')
                ->paginate(10); // يمكنك تغيير العدد أو استخدام ->get() إن لم ترد pagination

            return $this->returnData('Active courses retrieved successfully', $courses->getCollection());
        } catch (\Exception $e) {
            return $this->returnError('Failed to retrieve active courses: ' . $e->getMessage());
        }
    }

    public function publishCourse($course_id)
    {
        try {
            $course = Course::find($course_id);

            if (!$course) {
                return $this->returnError("Course not found.");
            }

            if ($course->status != 1) {
                return $this->returnError("Course is not ready yet, so it cannot be moved to status 2.");
            }

            $course->status = 2;
            $course->save();

            return $this->returnSuccess("Course status updated to 2 successfully.");
        } catch (\Exception $e) {
            return $this->returnError("Failed to update course status: " . $e->getMessage());
        }
    }

    public function getPendingCourseDetails($courseId)
    {
        try {
            // جلب الكورس مع العلاقات المطلوبة
            $course = Course::with(['skills', 'aquirements', 'attachments', 'category', 'videos', 'quiezes'])
                ->where('id', $courseId)
                ->where('status', 1)
                ->first();

            if (!$course) {
                return $this->returnError("Course not found or not in pending status.");
            }

            // بيانات الدورة الأساسية
            $firstCourse = [
                "name" => $course->name,
                "status" => $course->status,
                "description" => $course->description,
                "image" => $course->image,
                "level" => $course->level,
                "point_to_enroll" => $course->point_to_enroll,
                "points_earned" => $course->points_earned,
            ];

            // تحميل الفيديوهات المرتبة
            $videos = $course->videos
                ->sortBy("sequential_order")
                ->map(function ($video) {
                    return (object) [
                        "type" => "video",
                        "id" => $video->id,
                        "title" => $video->title,
                        "description" => $video->description,
                        "path" => $video->path,
                        "image" => $video->image,
                        "sequential_order" => $video->sequential_order,
                    ];
                });

            // تحميل الكويزات
            $quizzes = $course->quiezes
                ->map(function ($quiz) {
                    return (object) [
                        "type" => "quiz",
                        "title" => $quiz->title,
                        "from_video" => $quiz->from_video,
                        "to_video" => $quiz->to_video,
                        "is_final" => $quiz->is_final,
                        "id" => $quiz->id,
                    ];
                });

            // دمج الفيديوهات والكويزات
            $videosAndQuiz = [];
            foreach ($videos as $video) {
                $videosAndQuiz[] = $video;

                foreach ($quizzes as $quiz) {
                    if ($quiz->to_video == $video->id) {
                        $videosAndQuiz[] = $quiz;
                    }
                }
            }

            // تجميع باقي البيانات
            $requirements = $course->skills->pluck("title");
            $aquirements = $course->aquirements->pluck("title");
            $attachments = $course->attachments;
            $category = optional($course->category)->title;

            $data = [
                "course" => $firstCourse,
                "requirements" => $requirements,
                "aquirements" => $aquirements,
                "attachments" => $attachments,
                "category" => $category,
                "videosAndQuiz" => $videosAndQuiz,
            ];

            return $this->returnData("Course details loaded successfully", $data);

        } catch (\Exception $e) {
            return $this->returnError("Failed to load course details: " . $e->getMessage());
        }
    }



    public function rejectCourse(Request $request, $course_id)
    {

        $request->validate([
            'reason' => 'required|string|min:5'
        ]);

        try {
            // جلب الكورس مع المدرس
            $course = Course::with('teacher')->find($course_id);

            if (!$course) {
                return $this->returnError("Course not found.");
            }

            if ($course->status != 1) {
                return $this->returnError("Course is not pending and cannot be rejected.");
            }

            // تغيير الحالة إلى مرفوض (نفترض أن 3 = مرفوض)
            $course->status = 3;
            $course->save();

            // إعداد رسالة الإشعار
            $message = [
                "title" => "Course Rejected",
                "course" => [
                    "id" => $course->id,
                    "name" => $course->name,
                    "description" => $course->description,
                    "level" => $course->level,
                    "point_to_enroll" => $course->point_to_enroll,
                    "points_earned" => $course->points_earned,
                ],
                "reason" => $request->reason
            ];

            // بث الحدث باستخدام TeacherEvent
            broadcast(new TeacherEvent($course->teacher->id, $message))->toOthers();

            return $this->returnSuccess("Course rejected and notification sent to the teacher.");
        } catch (\Exception $e) {
            return $this->returnError("An error occurred: " . $e->getMessage());
        }


    }


    public function createSpecializationByAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'is_completed' => 'required|boolean',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:4048',
            'courses' => 'required|array|min:2',
            'courses.*' => 'exists:courses,id',
            'teacher_id' => 'required|exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        DB::beginTransaction();

        try {
            $spec = Specilization::create([
                'title' => $request->title,
                'is_completed' => $request->is_completed,
                'teacher_id' => $request->teacher_id,
                'image' => '',
            ]);

            $path = imageUpload($request, $spec->id, "specialization_image");
            $spec->image = assetFromDisk("specialization_image", $path);
            $spec->save();

            $spec->courses()->attach($request->courses);

            $categoryIds = Course::whereIn('id', $request->courses)->pluck('category_id')->unique();
            $spec->categories()->syncWithoutDetaching($categoryIds);

            $skillIds = DB::table('course_skill')
                ->whereIn('course_id', $request->courses)
                ->pluck('skill_id')
                ->unique();

            $spec->skills()->syncWithoutDetaching($skillIds);

            DB::commit();
            return $this->returnSuccess('Specialization created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError($e->getMessage(), 500);
        }
    }

    public function updateSpecializationByAdmin(Request $request, $id)
    {
        $spec = Specilization::find($id);
        if (!$spec) {
            return $this->returnError('Specialization not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'is_completed' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4048',
            'courses' => 'nullable|array|min:2',
            'courses.*' => 'exists:courses,id',
            'teacher_id' => 'sometimes|exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        DB::beginTransaction();

        try {
            if ($request->has('title'))
                $spec->title = $request->title;
            if ($request->has('is_completed'))
                $spec->is_completed = $request->is_completed;
            if ($request->has('teacher_id'))
                $spec->teacher_id = $request->teacher_id;

            if ($request->hasFile('image')) {
                $path = imageUpload($request, $spec->id, "specialization_image");
                $spec->image = assetFromDisk("specialization_image", $path);
            }

            $spec->save();

            if ($request->has('courses')) {
                $spec->courses()->sync($request->courses);

                $categoryIds = Course::whereIn('id', $request->courses)->pluck('category_id')->unique();
                $spec->categories()->syncWithoutDetaching($categoryIds);

                $skillIds = DB::table('course_skill')
                    ->whereIn('course_id', $request->courses)
                    ->pluck('skill_id')
                    ->unique();

                $spec->skills()->syncWithoutDetaching($skillIds);
            }

            DB::commit();
            return $this->returnSuccess('Specialization updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError($e->getMessage(), 500);
        }
    }


    public function deleteSpecializationByAdmin($id)
    {
        $spec = Specilization::find($id);
        if (!$spec) {
            return $this->returnError('Specialization not found.', 404);
        }

        try {
            $spec->courses()->detach();
            $spec->skills()->detach();
            $spec->categories()->detach();
            $spec->delete();

            return $this->returnSuccess('Specialization deleted successfully');
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), 500);
        }
    }

    public function getSpecializations(Request $request)
    {
        try {
            $specializations = Specilization::with('teacher')
                ->withCount('courses') // لجلب عدد الكورسات المرتبطة
                ->select('id', 'title', 'image', 'is_completed', 'teacher_id', 'created_at')
                ->orderBy('created_at', 'desc')
                ->paginate(10); // يمكنك تغيير العدد أو جعله ديناميكياً

            // تحويل النتيجة مع إخفاء التفاصيل الحساسة
            $result = $specializations->through(function ($spec) {
                return [
                    'id' => $spec->id,
                    'title' => $spec->title,
                    'image' => $spec->image,
                    'is_completed' => $spec->is_completed,
                    'courses_count' => $spec->courses()->count(),
                    'teacher_full_name' => $spec->teacher->full_name,
                    'created_at' => $spec->created_at->toDateTimeString(),
                ];
            });

            return $this->returnData('Specializations retrieved successfully', $result->getCollection());

        } catch (\Exception $e) {
            return $this->returnError('Failed to retrieve specializations: ' . $e->getMessage());
        }
    }


}
