<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideoUpload;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function store(Request $request)
    {
        try {
            // حفظ الفيديو أولًا في قاعدة البيانات قبل تمريره إلى Job
            $video = \App\Models\Video::create([
                'disk' => 'videos_disk',
                'original_name' => $request->file->getClientOriginalName(),
                'path' => fileupload($request, "videos_disk", "video"),
                'title' => $request->title,
            ]);

            // إرسال الـ Job وتمرير معرف الفيديو فقط
            dispatch(new ProcessVideoUpload($video->id));

            return response()->json(["message" => "Your video upload is processing :)"], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
