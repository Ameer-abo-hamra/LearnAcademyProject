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
            $table->boolean("is_quizes_auto_generated");
            $table->string("name");
            $table->text("description");
            $table->string("image");
            $table->tinyInteger("level")
            $table->foreignId("teacher_id")->references("id")->on("teachers");
            $table->foreignId("specilization_id")->nullable()->references("id")->on("specilizations");
            $table->integer("point_to_enroll");


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
