<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;

use App\Jobs\ProcessVideoUpload;
use Illuminate\Http\Request;

class VideoController extends Controller
{

    public function store(Request $request)
    {
        // ✅ التحقق من البيانات
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000', // حسب الحد اللي بتحدده
            'file' => 'required|file|mimes:mp4,mov,avi,wmv|max:512000', // مثال: 500MB
            'course_id' => 'required|exists:courses,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }
        dd($request->hasFile('file'), $request->file('file'));

        try {
            $video = \App\Models\Video::create([
                'disk' => 'teachers',
                'original_name' => $request->file('file')->getClientOriginalName(),
                'title' => $request->title,
                'description' => $request->description,
                'path' => '', // مؤقتًا
                'course_id' => $request->course_id
            ]);

            $filePath = fileupload($request, $request->teacher_id, $request->course_id, $video->id);

            $video->path = $filePath;
            $video->save();

            dispatch(new ProcessVideoUpload($video->id));

            return response()->json(["message" => "Your video upload is processing :)"], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
