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
        Schema::create('daily_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('activity_date');
            $table->unsignedSmallInteger('lessons_practiced')->default(0);
            $table->unsignedSmallInteger('quizzes_taken')->default(0);
            $table->unsignedSmallInteger('quiz_correct')->default(0);
            $table->unsignedSmallInteger('quiz_total')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'activity_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_activities');
    }
};
