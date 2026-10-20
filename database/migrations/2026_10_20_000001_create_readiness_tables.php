<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('readiness_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->uuid('attempt_id');
            $table->string('scenario', 20);
            $table->boolean('completed');
            $table->unsignedTinyInteger('gameplay_score');
            $table->unsignedTinyInteger('quiz_score');
            $table->unsignedTinyInteger('overall_score');
            $table->unsignedTinyInteger('stars');
            $table->json('evidence');
            $table->json('newly_unlocked');
            $table->timestamp('created_at');
            $table->unique(['user_id', 'attempt_id']);
            $table->index(['user_id', 'scenario']);
        });
        Schema::create('readiness_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('scenario', 20);
            $table->boolean('completed')->default(false);
            $table->uuid('best_attempt_id')->nullable();
            $table->unsignedTinyInteger('best_gameplay_score')->default(0);
            $table->unsignedTinyInteger('best_quiz_score')->default(0);
            $table->unsignedTinyInteger('best_overall_score')->default(0);
            $table->unsignedTinyInteger('stars')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->unique(['user_id', 'scenario']);
        });
        Schema::create('readiness_achievements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('achievement_id', 32);
            $table->timestamp('unlocked_at');
            $table->unique(['user_id', 'achievement_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('readiness_achievements');
        Schema::dropIfExists('readiness_progress');
        Schema::dropIfExists('readiness_attempts');
    }
};
