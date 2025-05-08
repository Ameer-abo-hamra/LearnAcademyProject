<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      // migration file:
Schema::create('student_course_videos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('student_id')->constrained()->onDelete('cascade');
    $table->foreignId('course_id')->constrained()->onDelete('cascade');
    $table->foreignId('video_id')->constrained()->onDelete('cascade');
    $table->boolean('locked')->default(true);
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    $table->unique(['student_id', 'video_id']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_course_videos');
    }
};
