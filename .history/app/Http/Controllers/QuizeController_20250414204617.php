<?php

namespace App\Http\Controllers;

use App\Models\Quize;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Http\Request;

class QuizeController extends Controller
{
    use ResponseTrait ; 
    public function addQuize(Request $request)
    {

        DB::beginTransaction();

        try {
            // 1. أنشئ الكويز
            $quiz = Quize::create([
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

            return $this->return;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


}
