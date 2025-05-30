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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string("full_name");
            $table->string("email");
            $table->string("password");
            $table->string("username");
            $table->string("activation_code");
            $table->integer("age");
            $table->string("image");
            $table->tinyInteger("gender");
            $table->unsignedTinyInteger('free_points')->default(0);
            $table->unsignedTinyInteger('paid_points')->default(1000); // <=== أضفناها

            $table->boolean("is_active")->default(false);
            $table->boolean("admin_activation")->default(false);

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
