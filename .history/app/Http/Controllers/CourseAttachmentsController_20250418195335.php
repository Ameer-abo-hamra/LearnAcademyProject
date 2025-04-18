<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Validator;
class CourseAttachmentsController extends Controller
{
    use ResponseTrait;
    public function addAttachment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'attachments' => 'required|array|min:1',
            'attachments.*.text' => 'nullable|string',
            'attachments.*.file' => 'nullable|file|mimes:pdf|max:10240'
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        $course = Course::find($request->course_id);
        $teacherId = $course->teacher_id;

        foreach ($request->attachments as $index => $attachment) {
            $filePath = null;

            // تحقق من وجود الملف
            if ($request->hasFile("attachments.$index.file")) {
                $file = $request->file("attachments.$index.file");

                $folder = "$teacherId/{$course->id}";
                $filePath = $file->store($folder, 'course_attachments');
            }

            // أنشئ السطر في قاعدة البيانات
            $course->attachments()->create([
                'file_path' => $filePath,
                'text' => $attachment['text'] ?? null,
            ]);
        }

        return $this->returnSuccess("Attachments uploaded successfully");
    }

}
