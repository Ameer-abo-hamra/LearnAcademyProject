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
        Schema::create('video_question_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId("question_id")->references("id")->on("video_questions");
            $table->text("choice");
            $table->boolean("is_correct");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_question_choices');
    }
};
