<?php

namespace App\Http\Controllers;

use App\Events\TeacherEvent;
use App\Models\Course;
use App\Models\Quize;
use App\Models\Specilization;
use App\Models\StudentCourseContent;
use App\Models\StudentCourseVideo;
use App\Models\Teacher;
use App\Models\Video;
use DB;
use Illuminate\Http\Request;
use App\Http\Requests\createStudent;
use App\Models\Student;
use App\Traits\ResponseTrait;
use Hash;
use Illuminate\Support\Facades\Http;
use Validator;
use Auth;
class StudentController extends Controller
{
    use ResponseTrait;
    public function signUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:50|unique:Students',
            'password' => 'required|string|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/',
            'full_name' => 'required|string',
            'username' => 'required|string|min:4|max:50|unique:students',
            'age' => 'required|integer|min:16|max:100',
            'gender' => 'required|in:0,1', // 0 = Female, 1 = Male (مثلاً)
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4048',
        ]);


        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $code = sendEmail($request);
            if ($code) {
                DB::beginTransaction();
                $Student = Student::create([
                    'full_name' => $request->full_name,
                    'password' => Hash::make($request->password),
                    'email' => $request->email,
                    'username' => $request->username,
                    "activation_code" => $code,
                    "age" => $request->age,
                    "gender" => $request->gender,
                    "image" => ''
                ]);

                if ($request->hasFile("image")) {
                    $image = imageUpload($request, $Student->id, "student_image");
                    $path = assetFromDisk("student_image", $image);
                    $Student->image = $path;
                    $Student->save();
                }
                DB::commit();
                return $this->returnData('Your account has been created successfully please activate your account now .', $Student, 200);
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }

        return $this->returnError("your email does not exist :(");
    }

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
            'username' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $cre = $request->only('password', 'username');

            $token = Auth::guard('student')->attempt($cre);

            if ($token) {

                $Student = Auth::guard('student')->user();

                $Student->token = $token;

                return $this->returnData('', $Student, 200, );

            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());

        }
        return $this->returnError('your data is invalid');
    }
    public function logout()
    {

        Auth::guard('student')->logout();
        return $this->returnSuccess('your are logged-out successfully');
    }
    public function activate(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'activation_code' => 'required|string|min:6|max:6',
            'id' => 'required|exists:students,id',
        ]);


        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $Student = Student::find($request->id);
            if ($Student->activation_code === $request->activation_code) {
                $Student->is_active = true;
                $Student->save();
                return $this->returnSuccess('your account activated successfully :)');
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());

        }
        return $this->returnError("your code is not correct ");
    }

    public function resend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:50',
            'id' => 'required|exists:students,id',
        ]);
        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $Student = Student::find($request->id);
            if ($code = sendEmail($Student)) {
                $Student->activation_code = $code;
                $Student->save();
                return $this->returnSuccess("we sent your activation code  succesfully ");
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());

        }
        return $this->returnError(msgErorr: "this email does not exist");


    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string',
            'min_points' => 'nullable|integer|min:0',
            'max_points' => 'nullable|integer|min:0|gte:min_points',
            'min_points_e' => 'nullable|integer|min:0',
            'max_points_e' => 'nullable|integer|min:0|gte:min_points_e',
            'level' => 'nullable|integer',
            'is_completed' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1',
            'page_number' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        $q = $request->input('q');
        $minPoints = $request->input('min_points');
        $maxPoints = $request->input('max_points');
        $minPointsE = $request->input('min_points_e');
        $maxPointsE = $request->input('max_points_e');
        $level = $request->input('level');
        $isCompleted = $request->input('is_completed');
        $perPage = $request->input('per_page', 10);
        $pageNumber = $request->input('page_number', 1);

        $coursesQuery = Course::query()
            ->where("status", 2)
            ->when($minPoints, fn($qB) => $qB->where('point_to_enroll', '>=', $minPoints))
            ->when($maxPoints, fn($qB) => $qB->where('point_to_enroll', '<=', $maxPoints))
            ->when($minPointsE, fn($qB) => $qB->where('points_earned', '>=', $minPointsE))
            ->when($maxPointsE, fn($qB) => $qB->where('points_earned', '<=', $maxPointsE))
            ->when($level, fn($qB) => $qB->where('level', $level))
            ->when($q, function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhereHas(
                            'skills',
                            fn($q2) =>
                            $q2->where('title', 'like', "%{$q}%")
                        )
                        ->orWhereHas(
                            'category',
                            fn($q2) =>
                            $q2->where('title', 'like', "%{$q}%")
                        );
                });
            });

        $courses = $coursesQuery
            ->paginate($perPage, ['*'], 'page', $pageNumber);

        $specQuery = Specilization::query()
            ->when(
                !is_null($isCompleted),
                fn($qB) => $qB->where('is_completed', $isCompleted)
            )
            ->when($q, function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%{$q}%")
                        ->orWhereHas(
                            'skills',
                            fn($q2) =>
                            $q2->where('title', 'like', "%{$q}%")
                        )
                        ->orWhereHas(
                            'categories',
                            fn($q2) =>
                            $q2->where('title', 'like', "%{$q}%")
                        );
                });
            });

        $specializations = $specQuery
            ->paginate($perPage, ['*'], 'page', $pageNumber);

        // 5) تجهيز البيانات للإرسال
        $data = [
            'courses' => $courses->items(),
            'specializations' => $specializations->items(),
        ];

        // 6) إرجاع النتائج مع الميتا
        return $this->returnData(
            '',
            $data,
            200,
            [$courses, $specializations]
        );
    }

    public function courseEnroll(Request $request, $course_id)
    {
        $student = u("student");
        $course = Course::findOrFail($course_id);
        $teacher_id = $course->teacher_id;

        // تحقق إذا الطالب مشترك أصلاً
        if ($student->courses()->where('course_id', $course_id)->exists()) {
            return $this->returnError('You are already enrolled in this course.');
        }

        DB::beginTransaction();

        // خصم النقاط إن لزم
        if ($course->point_to_enroll > 0) {
            $totalPoints = $student->points;

            if ($totalPoints < $course->point_to_enroll) {
                return $this->returnError('You do not have enough points to enroll in this course.');
            }

            $pointsNeeded = $course->point_to_enroll;

            if ($student->free_points >= $pointsNeeded) {
                $student->free_points -= $pointsNeeded;
            } else {
                $remaining = $pointsNeeded - $student->free_points;
                $student->free_points = 0;
                $student->paid_points -= $remaining;
            }

            $student->save();
        }

        // ربط الطالب بالكورس والمدرس
        $student->courses()->syncWithoutDetaching($course_id);
        Teacher::find($teacher_id)?->students()->syncWithoutDetaching($student->id);

        // إدراج المحتوى المرتبط بالكورس (فيديوهات + كويزات)
        $this->insertStudentCourseContents($student->id, $course_id);

        DB::commit();

        broadcast(new TeacherEvent($course->teacher_id, "A new student has signed up for the course \"$course->name\" "));

        return $this->returnSuccess('You have been successfully enrolled in the course.');
    }

    private function insertStudentCourseContents($student_id, $course_id)
    {
        $videos = Video::where('course_id', $course_id)
            ->orderBy('sequential_order')
            ->get()
            ->keyBy('id'); // لتسهيل الوصول إلى video عبر ID لاحقًا

        $quizzes = Quize::where('course_id', $course_id)->get();

        // تحويل الفيديوهات إلى مصفوفة مرتبة حسب sequential_order
        $orderedItems = [];

        foreach ($videos as $video) {
            $orderedItems[] = [
                'type' => 'video',
                'model' => $video,
                'order_key' => $video->sequential_order,
            ];

            // إضافة الكويزات التي تأتي بعد هذا الفيديو
            foreach ($quizzes as $quiz) {
                // نحصل على sequential_order للفيديوين المرتبطين بالكويز
                $from_order = $videos[$quiz->from_video]->sequential_order ?? null;
                $to_order = $quiz->to_video;

                if ($to_order === $video->sequential_order) {
                    $orderedItems[] = [
                        'type' => 'quiz',
                        'model' => $quiz,
                        'order_key' => $to_order + 0.5, // بين الفيديوين
                    ];
                }
            }
        }

        // ترتيب العناصر بحسب order_key
        usort($orderedItems, fn($a, $b) => $a['order_key'] <=> $b['order_key']);

        // بناء الإدخالات
        $orderIndex = 1;
        $contentList = [];

        foreach ($orderedItems as $item) {
            $isFirst = $orderIndex === 1;

            $contentList[] = [
                'student_id' => $student_id,
                'course_id' => $course_id,
                'content_id' => $item['model']->id,
                'content_type' => $item['type'] === 'video' ? Video::class : Quize::class,
                'order_index' => $orderIndex++,
                'locked' => !$isFirst, // فقط أول عنصر غير مقفل
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        StudentCourseContent::insert($contentList);
    }


    public function saveCourse($course_id)
    {
        try {
            // جلب الكورس أو إرسال خطأ 404
            $course = Course::findOrFail($course_id);

            $student = u('student');

            // تأكد إذا الكورس محفوظ مسبقًا
            if ($student->savedCourse()->wherePivot('course_id', $course_id)->exists()) {
                return $this->returnError('You have already saved this course.', 409);
            }

            // حفظ الكورس بدون تكرار
            $student->savedCourse()->attach($course_id);

            return $this->returnSuccess('Course saved successfully :)');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnError('This course does not exist :(', 404);
        } catch (\Exception $e) {
            return $this->returnError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }

    public function getCoursesInProgress()
    {
        $student = u("student");

        $courses = $student->courses()
            ->wherePivot("status", 0)
            ->get();

        return $this->returnData("Courses in progress", $courses);
    }
    public function getCoursesCompleted()
    {
        $student = u("student");

        $courses = $student->courses()
            ->wherePivot("status", 1)
            ->get();

        return $this->returnData("Courses completed", $courses);
    }
    public function getCoursesSaved()
    {
        $student = u("student");

        $courses = $student->savedCourse;

        return $this->returnData("saved courses ", $courses);
    }

    public function getStudentNotifications()
    {
        $student = u('student');

        $notifications = $student->notifications()
            ->latest()
            ->paginate(10, ['title', 'body']);

        return $this->returnData('Notifications retrieved successfully', $notifications->getCollection());
    }

    public function getProfile()
    {
        $student = u('student'); // اعتمادًا على الـ Auth guard الخاص بك
        return $this->returnData('Profile retrieved successfully.', $student);
    }
    public function updateProfile(Request $request)
    {
        $student = u('student');

        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:100',
            'username' => 'nullable|string|min:4|max:50|unique:students,username,' . $student->id,
            'age' => 'nullable|integer|min:16|max:100',
            'gender' => 'nullable|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4048',
            'password' => 'nullable|string|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/'
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            if ($request->has('full_name')) {
                $student->full_name = $request->full_name;
            }

            if ($request->has('username')) {
                $student->username = $request->username;
            }

            if ($request->has('age')) {
                $student->age = $request->age;
            }

            if ($request->has('gender')) {
                $student->gender = $request->gender;
            }

            if ($request->has('password')) {
                $student->password = Hash::make($request->password);
            }

            if ($request->hasFile("image")) {
                $image = imageUpload($request, $student->id, "student_image");
                $path = assetFromDisk("student_image", $image);
                $student->image = $path;
            }

            $student->save();

            return $this->returnData('Profile updated successfully.', $student);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

    public function sendRecomendation(Request $re)


}






