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
        Schema::create('student_saved_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId("course_id")->references("id")->on("courses")->cascadeOnDelete();

            $table->foreignId("student_id")->references("id")->on("students")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_saved_course');
    }
};
