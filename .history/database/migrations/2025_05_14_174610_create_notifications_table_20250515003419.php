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
    Schema::create('notifications', function (Blueprint $table) {
        $table->id();

        // الطرف المستقبل (طالب، أستاذ، أدمن)
        $table->morphs('notifiable'); // => notifiable_id, notifiable_type

        // الطرف المرسل
        $table->unsignedBigInteger('sender_id')->nullable();
        $table->string('sender_type')->nullable(); // Admin, Teacher, Student...

        $table->string('title');
        $table->text('body')->nullable();
        $table->json('data')->nullable(); // بيانات إضافية إن وجدت

        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
