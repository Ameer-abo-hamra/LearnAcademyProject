<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseAttachments;
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
            'attachments.*.text' => 'required|string|required_without:attachments.*.file',
            'attachments.*.file' => 'required|file|mimes:pdf|max:10240|required_without:attachments.*.text'
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
                $filePath = $file->storeAs($folder, $course->id . "." . $file->getClientOriginalExtension(), 'course_attachments');
            }

            // أنشئ السطر في قاعدة البيانات
            $course->attachments()->create([
                'file_path' => $filePath,
                'text' => $attachment['text'] ?? null,
            ]);
        }

        return $this->returnSuccess("Attachments uploaded successfully");
    }

    public function updateAttachment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'nullable|string|required_without:file',
            'file' => 'nullable|file|mimes:pdf|max:10240|required_without:text',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        $attachment = CourseAttachments::find($id);

        if (!$attachment) {
            return $this->returnError("Attachment not found", 404);
        }

        $course = $attachment->course;
        $teacherId = $course->teacher_id;

        // تحديث الملف إذا وُجد
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $folder = "$teacherId/{$course->id}";
            $filePath = $file->storeAs($folder, $course->id . "." . $file->getClientOriginalExtension(), 'course_attachments');
            $filePath = assetFromDisk('course_attachments', $filePath);
            $attachment->file_path = $filePath;
        }

        // تحديث النص إذا وُجد
        if ($request->has('text')) {
            $attachment->text = $request->input('text');
        }

        $attachment->save();

        return $this->returnSuccess("Attachment updated successfully");
    }


}
