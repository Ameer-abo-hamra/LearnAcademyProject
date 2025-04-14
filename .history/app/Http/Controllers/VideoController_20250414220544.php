<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideoUpload;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function store(Request $request)
    {
        try {
            $video = \App\Models\Video::create([
                'disk' => 'teachers',
                'original_name' => $request->file->getClientOriginalName(),
                'title' => $request->title,
                'description' => $request->description,
                'path' => '', // مؤقتًا
            ]);

            $filePath = fileupload($request, $request->teacher_id, $request->course_id, $video->id);

            // 3. تحديث المسار في السجل
            $video->path$filePath]);

            // 4. إرسال الـ Job (بعد التأكد إن الفيديو محفوظ)
            dispatch(new ProcessVideoUpload($video->id));

            return response()->json(["message" => "Your video upload is processing :)"], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
