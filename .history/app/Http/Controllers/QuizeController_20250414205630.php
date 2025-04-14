<?php

namespace App\Http\Controllers;

use App\Models\Quize;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Http\Request;

class QuizeController extends Controller
{
    use ResponseTrait;
    public function addQuize(Request $request)
    {
        // ✅ Validation أولًا
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'from_video' => ['required', 'integer'],
            'to_video' => ['required', 'integer', 'gte:from_video'],
            'course_id' => ['required', 'exists:courses,id'],
            'point' => ['required', 'integer', 'min:0'],
            'questions' => ['required', 'array', 'min:1'],

            'questions.*.text' => ['required', 'string'],
            'questions.*.choices' => ['required', 'array', 'min:2'],
            'questions.*.choices.*.text' => ['required', 'string'],
            'questions.*.choices.*.is_correct' => ['required', 'boolean'],
        ]);

        DB::beginTransaction();

        try {
            // 1. أنشئ الكويز
            $quiz = Quize::create([
                'title' => $validated['title'],
                'from_video' => $validated['from_video'],
                'to_video' => $validated['to_video'],
                'course_id' => $validated['course_id'],
                'point' => $validated['point'],
            ]);

            // 2. الأسئلة والخيارات
            foreach ($validated['questions'] as $questionData) {
                $question = $quiz->questions()->create([
                    'text' => $questionData['text'],
                ]);

                foreach ($questionData['choices'] as $choiceData) {
                    $question->choices()->create([
                        'text' => $choiceData['text'],
                        'is_correct' => $choiceData['is_correct'],
                    ]);
                }
            }

            DB::commit();
            return $this->returnSuccess("Quiz created successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



}
