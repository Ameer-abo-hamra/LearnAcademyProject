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
        Schema::create('specilizations', function (Blueprint $table) {
            $table->id();
            $table->string("title");
            $table->foreignId("teacher_id")->references("id")->on("teachers")
            $table->boolean("is_completed");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specilizations');
    }
};
