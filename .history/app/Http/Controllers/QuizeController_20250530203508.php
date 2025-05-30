<?php

namespace App\Http\Controllers;

use App\Models\Quize;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use DB;
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
            // ✅ تحقق من وجود كويز نهائي سابق لهذا الكورس
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
                "is_final" => $request->is_final,
                "is_auto_generated" => $request->is_auto_generated,
            ]);

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
            return $this->returnSuccess("Quiz created successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
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

        // ✅ استدعاء completeContent بعد حل الكويز حتى لو لم يكن نهائيًا
        $requestForCompletion = new \Illuminate\Http\Request();
        $requestForCompletion->merge([
            'id' => $quiz->id,
            'type' => 'quiz'
        ]);
        app()->call([\App\Http\Controllers\VideoController::class, 'completeContent'], ['request' => $requestForCompletion]);

        return $this->returnData('Quiz Result', [
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctCount,
            'score_percentage' => $percentage
        ]);

    }

}
