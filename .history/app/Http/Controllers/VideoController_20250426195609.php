<?php

namespace App\Http\Controllers;
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
            return $this->returnError($validator->errors()->first(), 422);
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
        $video->load('questions.choices', 'scripts', 'extensions', 'audios');



        return $this->returnData("Video fetched successfully", $video, 200);
    }



}
