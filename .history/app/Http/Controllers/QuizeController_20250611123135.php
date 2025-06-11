<?php

namespace App\Http\Controllers;

use App\Models\Quize;
use App\Models\Video;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Http;
use Validator;
use Illuminate\Http\Request;

class QuizeController extends Controller
{
    use ResponseTrait;
    public function addQuize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'from_video' => ['required', 'integer'],
            'to_video' => ['required', 'integer', 'gte:from_video'],
            'course_id' => ['required', 'exists:courses,id'],
            'is_auto_generated' => ['required', 'boolean'],
            'is_final' => ['required', 'boolean'],
        ]);

        // ⛔ تحقق شرطياً من الأسئلة فقط إذا لم يكن Auto Generated
        if (!$request->is_auto_generated) {
            $validator->after(function ($validator) use ($request) {
                if (!is_array($request->questions) || count($request->questions) < 1) {
                    $validator->errors()->add('questions', 'Questions are required when not auto-generated.');
                    return;
                }

                foreach ($request->questions as $index => $question) {
                    if (empty($question['text'])) {
                        $validator->errors()->add("questions.$index.text", 'Question text is required.');
                    }

                    if (!isset($question['choices']) || !is_array($question['choices']) || count($question['choices']) < 2) {
                        $validator->errors()->add("questions.$index.choices", 'At least two choices are required.');
                    } else {
                        foreach ($question['choices'] as $choiceIndex => $choice) {
                            if (!isset($choice['choice']) || $choice['choice'] === '') {
                                $validator->errors()->add("questions.$index.choices.$choiceIndex.choice", 'Choice text is required.');
                            }
                            if (!array_key_exists('is_correct', $choice)) {
                                $validator->errors()->add("questions.$index.choices.$choiceIndex.is_correct", 'is_correct is required.');
                            }
                        }
                    }
                }
            });
        }

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            // ✅ تحقق من وجود كويز نهائي سابق
            if ($request->is_final) {
                $existingFinalQuiz = Quize::where('course_id', $request->course_id)
                    ->where('is_final', true)
                    ->first();

                if ($existingFinalQuiz) {
                    return $this->returnError('A final quiz already exists for this course.');
                }
            }

            $quiz = Quize::create([
                'title' => $request->title,
                'from_video' => $request->from_video,
                'to_video' => $request->to_video,
                'course_id' => $request->course_id,
                'is_final' => $request->is_final,
                'is_auto_generated' => $request->is_auto_generated,
            ]);

            // ✅ أنشئ الأسئلة فقط إذا لم يكن Auto-Generated
            if (!$request->is_auto_generated) {
                foreach ($request->questions as $questionData) {
                    $question = $quiz->questions()->create([
                        'text' => $questionData['text'],
                    ]);

                    foreach ($questionData['choices'] as $choiceData) {
                        $question->choices()->create([
                            'choice' => $choiceData['choice'],
                            'is_correct' => $choiceData['is_correct'],
                        ]);
                    }
                }
            }

            DB::commit();
            return $this->returnSuccess("Quiz created successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function deleteQuize($quiz_id)
    {
        try {
            $quiz = Quize::with('questions.choices')->find($quiz_id);

            if (!$quiz) {
                return $this->returnError("Quiz not found.");
            }

            DB::beginTransaction();

            // حذف الأسئلة والاختيارات
            foreach ($quiz->questions as $question) {
                $question->choices()->delete();
                $question->delete();
            }

            // حذف الكويز
            $quiz->delete();

            DB::commit();
            return $this->returnSuccess("Quiz deleted successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError("Failed to delete quiz: " . $e->getMessage());
        }
    }


    public function updateQuize(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'from_video' => ['required', 'integer'],
            'to_video' => ['required', 'integer', 'gte:from_video'],
            'course_id' => ['required', 'exists:courses,id'],
            'questions' => ['required', 'array', 'min:1'],
            'is_auto_generated' => ['required'],
            'is_final' => ['required'],
            'questions.*.text' => ['required', 'string'],
            'questions.*.choices' => ['required', 'array', 'min:2'],
            'questions.*.choices.*.choice' => ['required', 'string'],
            'questions.*.choices.*.is_correct' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            $quiz = Quize::find($id);
            if (!$quiz) {
                return $this->returnError('Quiz not found');
            }

            // ✅ تأكد من عدم وجود كويز نهائي آخر للكورس إن كان is_final مفعّل
            if ($request->is_final && !$quiz->is_final) {
                $existingFinalQuiz = Quize::where('course_id', $request->course_id)
                    ->where('is_final', true)
                    ->where('id', '!=', $quiz->id)
                    ->first();

                if ($existingFinalQuiz) {
                    return $this->returnError('Another final quiz already exists for this course.');
                }
            }

            // ✅ تحديث بيانات الكويز
            $quiz->update([
                'title' => $request->title,
                'from_video' => $request->from_video,
                'to_video' => $request->to_video,
                'course_id' => $request->course_id,
                'is_final' => $request->is_final,
                'is_auto_generated' => $request->is_auto_generated,
            ]);

            // ✅ حذف الأسئلة القديمة (مع خياراتها)
            $quiz->questions()->each(function ($question) {
                $question->choices()->delete();
                $question->delete();
            });

            // ✅ إضافة الأسئلة الجديدة
            foreach ($request->questions as $questionData) {
                $question = $quiz->questions()->create([
                    'text' => $questionData['text'],
                ]);

                foreach ($questionData['choices'] as $choiceData) {
                    $question->choices()->create([
                        'choice' => $choiceData['choice'],
                        'is_correct' => $choiceData['is_correct'],
                    ]);
                }
            }

            DB::commit();
            return $this->returnSuccess("Quiz updated successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError("Something went wrong: " . $e->getMessage());
        }
    }

    public function getQuizeForTeacher(Request $request)
    {
        $course_id = $request->query("course_id");
        $quiz_id = $request->query("quiz_id");

        // الخطوة 1: نتأكد أن المدرّس يملك هذا الكورس
        $course = u("teacher")->courses()->where("id", $course_id)->first();

        if ($course) {
            // الخطوة 2: نبحث عن الكويز بشكل مباشر كـ object
            $quiz = $course->quiezes()->where("id", $quiz_id)->first();

            if (!$quiz) {
                // لو ما وجدنا الكويز
                return $this->returnError("Quiz not found.");
            }

            // الخطوة 3: تحميل الأسئلة والاختيارات المرتبطة
            $quiz->load("questions.choices");

            // الخطوة 4: نعيد الكويز كـ object
            return $this->returnData("quiz", $quiz);
        } else {
            // لو المدرّس لا يملك هذا الكورس
            return $this->returnError("You cannot show this quiz :(");
        }
    }

    public function getQuizForStudent($quiz_id)
    {

        $quiz = Quize::with([
            'questions.choices' => function ($query) {
                $query->select('id', 'choice', "question_id"); // حدد الأعمدة هنا
            }
        ])->find($quiz_id);

        return $this->returnData("", $quiz);
    }
    public function submitQuizAnswers(Request $request)
    {
        $student = u('student');
        $quizId = $request->input('quiz_id');
        $answers = $request->input('answers');

        $quiz = Quize::with('questions.choices', 'course')->findOrFail($quizId);
        $totalQuestions = $quiz->questions->count();
        $correctCount = 0;

        foreach ($answers as $answer) {
            $question = $quiz->questions->firstWhere('id', $answer['question_id']);
            if (!$question)
                continue;

            $choice = $question->choices->firstWhere('id', $answer['choice_id']);
            if ($choice && $choice->is_correct) {
                $correctCount++;
            }
        }

        $percentage = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : 0;

        // ...

        if ($quiz->is_final) {
            if ($percentage >= 70) {
                $alreadyRewarded = $student->quizes()
                    ->where('quiz_id', $quiz->id)
                    ->wherePivot('is_rewarded', true)
                    ->exists();

                if (!$alreadyRewarded) {
                    if ($quiz->course->point_to_enroll > 0 && $quiz->course->point_to_enroll < 10) {
                        $student->free_points += $quiz->course->points_earned;
                    } else {
                        $student->paid_points += $quiz->course->points_earned;
                    }

                    $student->save();

                    $student->quizes()->updateExistingPivot($quiz->id, [
                        'is_rewarded' => true,
                        "completed_at" => Carbon::now()
                    ]);

                    // تعليم الكويز كمكتمل وفتح التالي
                    $requestForCompletion = new \Illuminate\Http\Request();
                    $requestForCompletion->merge([
                        'id' => $quiz->id,
                        'type' => 'quiz'
                    ]);
                    app()->call([self::class, 'completeContent'], ['request' => $requestForCompletion]);

                    return $this->returnData("", "hi");
                }

                $student->courses()->updateExistingPivot($quiz->course->id, [
                    'status' => 1
                ]);
            }

            $student->quizes()->syncWithoutDetaching([
                $quiz->id => ['is_rewarded' => false]
            ]);
        }

        if ($percentage <= 70) {
            return $this->returnData('please try again ... :(', [
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctCount,
                'score_percentage' => $percentage
            ]);
        } else {
            $requestForCompletion = new \Illuminate\Http\Request();
            $requestForCompletion->merge([
                'id' => $quiz->id,
                'type' => 'quiz'
            ]);

            app()->make(\App\Http\Controllers\VideoController::class)->completeContent($requestForCompletion);

        }
        return $this->returnData('Quiz Result', [
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctCount,
            'score_percentage' => $percentage
        ]);

    }

    public function generateAutoQuiz(Request $request, $quiz_id)
    {
        $student = u('student');

        $quiz = Quize::where('id', $quiz_id)
            ->where('is_auto_generated', true)
            ->first();

        if (!$quiz) {
            return $this->returnError("Quiz not found or not auto generated.");
        }

        $videoIds = Video::where('course_id', $quiz->course_id)
            ->whereBetween('sequential_order', [$quiz->from_video, $quiz->to_video])
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        if (count($videoIds) == 0) {
            return $this->returnError("No videos found in the specified range.");
        }

        $validated = Validator::make($request->all(), [
            'difficulty' => 'required|in:easy,medium,hard',
            'language' => 'required|in:en,ar,fr',
        ]);

        if ($validated->fails()) {
            return $this->returnError($validated->errors()->first());
        }

        $payload = [
            'video_ids' => $videoIds,
            'difficulty' => $request->difficulty,
            'language' => $request->language,
        ];

        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('http://localhost:8083/generate-quiz', $payload);

            if (!$response->successful()) {
                return $this->returnError("Failed to generate quiz", 500, [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

            $data = $response->json();

            // 🟢 تخزين الأسئلة والاختيارات
            DB::beginTransaction();

            foreach ($quiz->questions as $q) {
                $q->choices()->delete();
                $q->delete();
            }

            foreach ($data['questions'] as $item) {
                $question = $quiz->questions()->create([
                    'text' => $item['question'],
                    'creates_at' => Carbon::now()
                ]);

                foreach ($item['options'] as $index => $option) {
                    $question->choices()->create([
                        'choice' => $option['text'],
                        'is_correct' => $index === $item['correct_option_index'],
                    ]);
                }
            }

            DB::commit();

            // 🟢 جلب الكويز مع الأسئلة والاختيارات
            $quiz->load(['questions.choices']);

            // 🟢 تجهيز البيانات للعرض (إخفاء الحقول)
            $formattedQuiz = [
                'id' => $quiz->id,
                'from_video' => $quiz->from_video,
                'to_video' => $quiz->to_video,
                'title' => $quiz->title,
                'is_auto_generated' => $quiz->is_auto_generated,
                'is_final' => $quiz->is_final,
                'course_id' => $quiz->course_id,
                'questions' => $quiz->questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'text' => $question->text,
                        'quize_id' => $question->quize_id,

                        'choices' => $question->choices->map(function ($choice) {
                            return [
                                'id' => $choice->id,
                                'choice' => $choice->choice,
                                'question_id' => $choice->question_id,
                            ];
                        })->values()
                    ];
                })->values()
            ];

            return $this->returnData('', $formattedQuiz);


        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError("Error while generating or saving quiz", 500, ['error' => $e->getMessage()]);
        }
    }



}
