<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuizeController extends Controller
{
   public function addQuize(Request $request) {

    public function storeQuiz(Request $request)
{
    DB::beginTransaction();

    try {
        // 1. أنشئ الكويز
        $quiz = Quiz::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        // 2. مرّ على الأسئلة
        foreach ($request->questions as $questionData) {
            $question = $quiz->questions()->create([
                'text' => $questionData['text'],
            ]);

            // 3. مرّ على الخيارات
            foreach ($questionData['choices'] as $choiceData) {
                $question->choices()->create([
                    'text' => $choiceData['text'],
                    'is_correct' => $choiceData['is_correct'],
                ]);
            }
        }

        DB::commit();

        return response()->json(['message' => 'Quiz created successfully'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

   
}
