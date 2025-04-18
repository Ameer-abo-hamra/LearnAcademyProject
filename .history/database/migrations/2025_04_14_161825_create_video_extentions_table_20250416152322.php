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
        Schema::create('video_extentions', function (Blueprint $table) {
            $table->id();
            $table->string("file_path")->nullable();
            $table->text("text")->nullable();
            $table->foreignId("video_id")->references("id")->on("videos");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_extentions');
    }
};
