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

        // send request to ai
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'file' => 'required|file|mimes:mp4,mov,avi,wmv|max:512000',
            'course_id' => 'required|exists:courses,id',
            'teacher_id' => 'required|exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }



        try {
            $file = $request->file('file');

            $video = \App\Models\Video::create([
                'disk' => 'teachers',
                'original_name' => $file->getClientOriginalName(),
                'title' => $request->title,
                'description' => $request->description,
                'path' => '', // مؤقت
                'course_id' => $request->course_id,
                "teacher_id" => $request->teacher_id
            ]);

            $filePath = fileupload($request, $request->teacher_id, $request->course_id, $video->id);

            $video->path = $filePath;
            $video->save();

            // dispatch(new ProcessVideoUpload($video->id));

            return $this->returnSuccess("Your video is being processed now :)");
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }



}
