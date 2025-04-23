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
            'course_id' => 'required|exists:courses,id',
            'teacher_id' => 'required|exists:teachers,id',
            "sequential_order" => "required|integer|min:0",
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

            $video = Video::create([
                'disk' => 'teachers',
                'original_name' => $file->getClientOriginalName(),
                'title' => $request->title,
                'description' => $request->description,
                'path' => '',
                'course_id' => $request->course_id,
                'teacher_id' => $request->teacher_id,
                "sequential_order" => $request->sequential_order,
            ]);

            $filePath = fileupload($request, $request->teacher_id, $request->course_id, $video->id);
            $video->path = $filePath;
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
                    $extFilePath = $extFile->store($folderPath, 'video_extension');
                }

                $video->extensions()->create([
                    'file_path' => $extFilePath,
                    'text' => $text,
                ]);
            }

            dispatch(new ProcessVideoUpload($video->id));

            DB::commit();
            return $this->returnSuccess("Your video is being processed now :)");

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError($e->getMessage());
        }


    }

    public function addExtension(Request $request, $videoId)
    {
        // 1) Validation
        $validator = Validator::make($request->all(), [
            'file' => 'nullable|file|mimes:pdf|max:10480|required_without:text',
            'text' => 'nullable|string|required_without:file',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        // 2) إيجاد الفيديو
        $video = Video::findOrFail($videoId);

        DB::beginTransaction();
        try {
            $extFilePath = null;
            if ($request->hasFile('file')) {
                // مثال لمسار التخزين، حسب إعداداتك
                $folderPath = "{$video->teacher_id}/{$video->course_id}/{$video->id}";
                $extFilePath = $request->file('file')->storeAs($folderPath, $videoId,'video_extension');
            }

            // 3) إنشاء سجل الامتداد
            $video->extensions()->create([
                'file_path' => $extFilePath,
                'text'      => $request->input('text'),
            ]);

            DB::commit();
            return $this->returnSuccess('done');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError($e->getMessage(), 500);
        }
    }

    /**
     * إضافة أسئلة إلى فيديو موجود.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $videoId
     */
    public function addQuestions(Request $request, $videoId)
    {
        // 1) Validation
        $validator = Validator::make($request->all(), [
            'questions'                   => 'required|array|min:1',
            'questions.*.time_to_appear'  => 'required|date_format:H:i:s',
            'questions.*.question'        => 'required|string',
            'questions.*.choices'         => 'required|array|min:1',
            'questions.*.choices.*.choice'=> 'required|string',
            'questions.*.choices.*.is_correct' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        // 2) إيجاد الفيديو
        $video = Video::findOrFail($videoId);

        DB::beginTransaction();
        try {
            foreach ($request->input('questions') as $q) {
                // إنشاء السؤال
                $question = new VideoQuestion([
                    'time_to_appear' => $q['time_to_appear'],
                    'question'       => $q['question'],
                ]);
                $video->questions()->save($question);

                // إنشاء الاختيارات
                foreach ($q['choices'] as $choice) {
                    $question->choices()->create([
                        'choice'     => $choice['choice'],
                        'is_correct' => $choice['is_correct'],
                    ]);
                }
            }

            DB::commit();
            return $this->returnSuccess('done');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError($e->getMessage(), 500);
        }
    }




}
