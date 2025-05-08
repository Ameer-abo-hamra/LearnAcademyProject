<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student__quiz', function (Blueprint $table) {
            $table->id();
            $table->foreignId("student_id")->references("id")->on("students");
            $table->foreignId("quiz_id")->references("id")->on("quizes");
            $table->time("completed_at")->nullable();
            $table->boolean("is_rewarded");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student__quiz');
    }
};
