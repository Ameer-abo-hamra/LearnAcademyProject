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
        Schema::create('_category__specilization', function (Blueprint $table) {
            $table->id();
            $table->foreignId("category_id")->references("id")->on("categories")->cascadeOnDelete();
            $table->foreignId("specialization_id")->references("id")->on("specilizations");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_category__specilization');
    }
};
