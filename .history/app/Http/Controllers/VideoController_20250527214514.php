<?php

namespace App\Http\Controllers;
use App\Models\Course;
use App\Models\StudentCourseVideo;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Support\Facades\Validator;

use App\Jobs\ProcessVideoUpload;
use Illuminate\Http\Request;

use App\Models\Video;
use App\Models\VideoQuestion;
use App\Models\VideoQuestionChoice;
class VideoController extends Controller
{
    use ResponseTrait;

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'file' => 'required|file|mimes:mp4,mov,avi,wmv|max:512000',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:4048',
            'course_id' => 'required|exists:courses,id',
            'teacher_id' => 'required|exists:teachers,id',

            // Optional questions
            'questions' => 'nullable|array',
            'questions.*.time_to_appear' => 'required_with:questions|date_format:H:i:s',
            'questions.*.question' => 'required_with:questions|string',
            'questions.*.choices' => 'required_with:questions|array|min:1',
            'questions.*.choices.*.choice' => 'required|string',
            'questions.*.choices.*.is_correct' => 'required|boolean',

            // Optional extension
            'extension.file' => 'nullable|file|mimes:pdf|max:10480',
            'extension.text' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors(), 422);
        }

        DB::beginTransaction();

        try {
            $file = $request->file('file');

            // ✅ حساب التسلسل تلقائيًا
            $maxOrder = Video::where('course_id', $request->course_id)->max('sequential_order') ?? 0;
            $newOrder = $maxOrder + 1;

            $video = Video::create([
                'disk' => 'teachers',
                'original_name' => $file->getClientOriginalName(),
                'title' => $request->title,
                'description' => $request->description,
                'path' => '',
                'image' => '',
                'course_id' => $request->course_id,
                'teacher_id' => $request->teacher_id,
                'sequential_order' => $newOrder,
            ]);

            $thumbail_path = imageUpload($request, $video->id, "video_thumbnail");
            $thumbail_path = assetFromDisk("video_thumbnail", $thumbail_path);

            $filePath = fileupload($request, $request->teacher_id, $request->course_id, $video->id);
            $filePath = assetFromDisk("teachers", $filePath);
            $video->path = $filePath;
            $video->image = $thumbail_path;
            $video->save();

            // ✅ الأسئلة إن وُجدت
            if ($request->has('questions')) {
                foreach ($request->questions as $q) {
                    $question = new VideoQuestion([
                        'time_to_appear' => $q['time_to_appear'],
                        'question' => $q['question'],
                    ]);
                    $video->questions()->save($question);

                    foreach ($q['choices'] as $choice) {
                        $question->choices()->create([
                            'choice' => $choice['choice'],
                            'is_correct' => $choice['is_correct'],
                        ]);
                    }
                }
            }

            // ✅ إضافة امتداد واحد (ملف أو نص أو كلاهما)
            $extFilePath = null;
            $hasFile = $request->hasFile("extension.file");
            $text = $request->input("extension.text");

            if ($hasFile || $text) {
                if ($hasFile) {
                    $extFile = $request->file("extension.file");
                    $folderPath = "{$video->teacher_id}/{$video->course_id}/{$video->id}";
                    $extFilePath = $extFile->storeAs($folderPath, $video->id . '.' . $request->file("extension.file")->getClientOriginalExtension(), 'video_extension');
                    $extFilePath = assetFromDisk("video_extension", $extFilePath);
                    // return $this->returnData("" , $extFilePath);
                }

                $video->extensions()->create([
                    'file_path' => $extFilePath,
                    'text' => $text,
                ]);
            }

            dispatch(new ProcessVideoUpload($video->id));

            DB::commit();
            return $this->returnSuccess("Your video is being processed now  ..Wait for the video processing confirmation notification :)");

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError($e->getMessage());
        }
    }

    public function updateVideoInfo(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:1000',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:4048',
            'sequential_order' => 'sometimes|required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        try {
            $video = Video::findOrFail($id);

            if ($request->has('title')) {
                $video->title = $request->title;
            }

            if ($request->has('description')) {
                $video->description = $request->description;
            }

            if ($request->has('sequential_order')) {
                $video->sequential_order = $request->sequential_order;
            }

            if ($request->hasFile('image')) {
                $thumbail_path = imageUpload($request, $video->id, "video_thumbnail");
                $thumbail_path = assetFromDisk("video_thumbnail", $thumbail_path);
                $video->image = $thumbail_path;
            }

            $video->save();

            return $this->returnSuccess("Video info updated successfully", 200);

        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), 500);
        }
    }

    public function updateExtension(Request $request, $videoId)
    {

        $validator = Validator::make($request->all(), [
            'file' => 'nullable|file|mimes:pdf|max:10480|required_without:text',
            'text' => 'nullable|string|required_without:file',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        $video = Video::findOrFail($videoId);

        DB::beginTransaction();
        try {
            $extFilePath = null;
            if ($request->hasFile('file')) {
                $folderPath = "{$video->teacher_id}/{$video->course_id}/{$video->id}";
                $extFilePath = $request->file('file')->storeAs($folderPath, $videoId, 'video_extension');
            }

            // تحديث أو إنشاء الامتداد
            $extensionData = [
                'file_path' => $extFilePath,
                'text' => $request->input('text'),
            ];

            $video->extensions()->exists()
                ? $video->extensions()->update($extensionData)
                : $video->extensions()->create($extensionData);

            DB::commit();
            return $this->returnSuccess('تم تحديث الامتداد بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError($e->getMessage(), 500);
        }
    }


    public function updateQuestions(Request $request, $videoId)
    {
        $validator = Validator::make($request->all(), [
            'questions' => 'required|array|min:1',
            'questions.*.time_to_appear' => 'required|date_format:H:i:s',
            'questions.*.question' => 'required|string',
            'questions.*.choices' => 'required|array|min:1',
            'questions.*.choices.*.choice' => 'required|string',
            'questions.*.choices.*.is_correct' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        $video = Video::findOrFail($videoId);

        DB::beginTransaction();
        try {
            // حذف الأسئلة القديمة (والاختيارات المرتبطة بها تلقائيًا إن كان هناك cascade)
            $video->questions()->delete();

            foreach ($request->input('questions') as $q) {
                $question = new VideoQuestion([
                    'time_to_appear' => $q['time_to_appear'],
                    'question' => $q['question'],
                ]);
                $video->questions()->save($question);

                foreach ($q['choices'] as $choice) {
                    $question->choices()->create([
                        'choice' => $choice['choice'],
                        'is_correct' => $choice['is_correct'],
                    ]);
                }
            }

            DB::commit();
            return $this->returnSuccess('تم تحديث الأسئلة بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError($e->getMessage(), 500);
        }
    }

    public function getCourseVideo(Request $request)
    {
        $course_id = $request->query("course_id");
        $video_id = $request->query("video_id");

        if (!$course_id || !$video_id) {
            return $this->returnError("course_id and video_id are required", 400);
        }

        // تحقق من أن المعلم يمتلك الكورس
        $course = u("teacher")->courses()->where("id", $course_id)->first();
        if (!$course) {
            return $this->returnError("Course not found or not authorized", 404);
        }

        // تحقق من وجود الفيديو داخل الكورس
        $video = $course->videos()->where("id", $video_id)->first();
        if (!$video) {
            return $this->returnError("Video not found in this course", 404);
        }

        // تحميل الأسئلة مع الخيارات
        $video->load('questions.choices', 'scripts', 'extensions', 'audios', "videoSubtitles");



        return $this->returnData("Video fetched successfully", $video, 200);
    }


    // public function showVideo(Request $request)
    // {
    //     $path = $request->query('path'); // استقبل المسار كـ GET parameter
    //     $x = $path;
    //     if (!$path) {
    //         return response()->json(['error' => 'Path is required'], 400);
    //     }
    //     $url = "http://127.0.0.1:8000/streamable_videos/1/1/2/master.m3u8";

    //     // استخدام parse_url للحصول على الـ path
    //     $path = parse_url($path, PHP_URL_PATH);

    //     // إزالة أول `/` لو موجود
    //     $path = ltrim($path, '/');
    //     // تأكد أن الملف موجود
    //     $fullPath = public_path($path);
    //     if (!file_exists($fullPath)) {
    //         return response()->json(['error' => 'File not found'], 404);
    //     }

    //     // إرجاع الملف مباشرة
    //     return response()->file($fullPath, [
    //         'path' => $x,
    //         'Content-Type' => 'application/vnd.apple.mpegurl', // أو النوع المناسب للفيديو مثل .m3u8
    //         'Access-Control-Allow-Origin' => '*', // السماح لأي مصدر (أو تحديد 'http://localhost:3000' فقط)
    //     ]);

    // }


    public function watchVideoForStudent($video_id)
    {
        $student = u("student"); // Authenticated student

        $video = Video::with(['course', 'questions.choices', 'extensions'])->find($video_id);

        if (!$video) {
            return $this->returnError("Video not found.");
        }

        $course = $video->course;

        // تحقق من التسجيل في الدورة
        $isEnrolled = $student->courses()->where('courses.id', $course->id)->exists();
        if (!$isEnrolled) {
            return $this->returnError("You must enroll in the course to watch this video.");
        }

        // الحصول على الفيديوهات السابقة حسب الترتيب
        $previousVideos = $course->videos()
            ->where('sequential_order', '<', $video->sequential_order)
            ->orderBy('sequential_order')
            ->pluck('id')
            ->toArray();

        // جلب الفيديوهات التي شاهدها الطالب
        $watchedVideos = $student->studentCourseVideos()
            ->whereIn('video_id', $previousVideos)
            ->pluck('video_id')
            ->toArray();

        // التأكد من أن كل الفيديوهات السابقة قد شاهدها الطالب
        $unwatched = array_diff($previousVideos, $watchedVideos);
        if (!empty($unwatched)) {
            return $this->returnError("You must watch all previous videos before accessing this one.");
        }

        // تسجيل المشاهدة إذا لم تسجل مسبقًا
        $alreadyWatched = $student->studentCourseVideos()->where('video_id', $video->id)->exists();
        if (!$alreadyWatched) {
            $student->studentCourseVideos()->attach($video->id, ['completed_at' => now()]);
        }

        // إعداد البيانات المسترجعة
        $data = [
            "id" => $video->id,
            "title" => $video->title,
            "description" => $video->description,
            "path" => $video->path,
            "image" => $video->image,
            "sequential_order" => $video->sequential_order,
            "questions" => $video->questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question_text' => $question->question,
                    "time_to_appear" => $question->time_to_appear,
                    'choices' => $question->choices->map(function ($choice) {
                        return [
                            'id' => $choice->id,
                            'text' => $choice->choice,
                            'is_correct' => $choice->is_correct, // إذا بدك تحذفها للطالب ممكن تستثنيها
                        ];
                    }),
                ];
            }),
            "attachments" => $video->extensions->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'path' => $attachment->file_path,
                    'text' => $attachment->text,
                ];
            }),
            "scripts" => $video->scripts,
            "subtitels" => $video->videoSubtitles,
            "audios" => $video->audios
        ];

        return $this->returnData("Video loaded successfully", $data);
    }



    public function completeVideo($video_id)
    {
        $student = u("student");

        $video = Video::find($video_id);
        if (!$video) {
            return $this->returnError("Video not found.");
        }

        // ✅ التحقق من الاشتراك في الكورس
        $isEnrolled = StudentCourseVideo::where('student_id', $student->id)
            ->where('course_id', $video->course_id)
            ->exists();

        if (!$isEnrolled) {
            return $this->returnError("You are not enrolled in this course.");
        }

        // ✅ جلب سجل الطالب والفيديو من جدول student_course_videos
        $entry = StudentCourseVideo::where('student_id', $student->id)
            ->where('video_id', $video->id)
            ->first();

        if (!$entry || $entry->locked) {
            return $this->returnError("This video is locked.");
        }

        // ✅ تعليم الفيديو كمكتمل
        $entry->update(['completed_at' => now()]);

        // ✅ محاولة فتح الفيديو التالي (إن وجد)
        $nextVideo = Video::where('course_id', $video->course_id)
            ->where('sequential_order', '=', $video->sequential_order + 1)
            ->first();

        if ($nextVideo) {
            $nextEntry = StudentCourseVideo::where('student_id', $student->id)
                ->where('video_id', $nextVideo->id)
                ->first();

            if ($nextEntry && $nextEntry->locked) {
                $nextEntry->update(['locked' => false]);
            } elseif ($nextEntry && !$nextEntry->locked) {
                return $this->returnError("The next video is already unlocked.");
            }
        }

        return $this->returnSuccess("Video completed successfully.");
    }


    public function getCoursePrecentage($course_id)
    {
        $student = u("student");

        // جلب عدد فيديوهات الكورس
        $totalVideos = Course::find($course_id)->videosCount();
        // return $this->returnData("", $totalVideos);
        if ($totalVideos === 0) {
            return $this->returnData("progress", 0); // تجنب القسمة على صفر
        }

        // عدد الفيديوهات التي شاهدها الطالب (completed_at غير فارغ)
        $completedVideos = StudentCourseVideo::where('student_id', $student->id)
            ->where('course_id', $course_id)
            ->whereNotNull('completed_at')
            ->count();

        // حساب النسبة المئوية
        $percentage = round(($completedVideos / $totalVideos) * 100, 2);

        return $this->returnData("progress", $percentage);
    }

    public function getSubTitles() {
return response()->download(public_path('video_subTitle/5/12/33_ar.vtt'));

    }
}
