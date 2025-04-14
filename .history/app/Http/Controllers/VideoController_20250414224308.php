<?php

namespace App\Http\Controllers;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Validator;

use App\Jobs\ProcessVideoUpload;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    use ResponseTrait;
    public function store(Request $request)
    {
        // ✅ التحقق من البيانات
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'file' => 'required|file|mimes:mp4,mov,avi,wmv|max:512000', // 500MB
            'course_id' => 'required|exists:courses,id',
            'teacher_id' => 'required|exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        // ✅ تحقق من وصول الملف وصحته
        if (!$request->hasFile('file')) {
            return $this->returnError("لم يتم إرسال الملف", 400);
        }

        if (!$request->file('file')->isValid()) {
            return $this->returnError("الملف غير صالح", 400);
        }

        try {
            // ⏳ إنشاء السجل مؤقتًا
            $video = \App\Models\Video::create([
                'disk' => 'teachers',
                'original_name' => $request->file('file')->getClientOriginalName(),
                'title' => $request->title,
                'description' => $request->description,
                'path' => '', // سيتم ملؤه لاحقًا
                'course_id' => $request->course_id,
            ]);

            // ⬆️ رفع الملف إلى المسار المخصص
            $filePath = fileupload($request, $request->teacher_id, $request->course_id, $video->id);

            // ✅ تحديث المسار
            $video->path = $filePath;
            $video->save();

            // 🧠 إرسال Job لخدمة التحويل
            dispatch(new ProcessVideoUpload($video->id));

            return response()->json(["message" => "جاري معالجة الفيديو"], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


}
