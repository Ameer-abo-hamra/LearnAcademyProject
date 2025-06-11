<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use Carbon\Carbon;

class CleanExpiredQuestions extends Command
{
   protected $signature = 'questions:clean-expired {--seconds=3600}';
    protected $description = 'Delete questions (and choices) older than 1 hour linked to auto-generated quizzes.';

    public function handle()
    {
        $seconds = (int) $this->option('seconds');
        $cutoff  = now()->subSeconds($seconds);
        $cutoff = Carbon::now()->subHour();

        // جلب الأسئلة المرتبطة بكويز auto_generated ومضى أكثر من ساعة
        $questions = Question::with(['choices', 'quize'])
            ->whereHas('quize', fn($q) => $q->where('is_auto_generated', true))
            ->where('created_at', '<', $cutoff)
            ->get();

        $deleted = 0;

        foreach ($questions as $question) {
            $question->choices()->delete();
            $question->delete();
            $deleted++;
        }

        $this->info("Deleted $deleted expired auto-generated questions.");
    }
}
