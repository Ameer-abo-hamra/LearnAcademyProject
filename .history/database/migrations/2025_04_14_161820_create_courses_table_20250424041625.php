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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->tinyInteger("status")->default(0);
            $table->text("description");
            $table->string("image");
            $table->tinyInteger("level");
            $table->foreignId("teacher_id")->references("id")->on("teachers")->cascadeOnDelete();
            $table->foreignId("category_id")->nullable()->references("id")->on("categories");
            $table->integer("point_to_enroll")->default(0);
            $table->integer("points_earned")->default(0);
            $table->timestamps();
        });
    }8

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
