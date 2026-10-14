<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('player_progress')) {
            return;
        }

        Schema::create('player_progress', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('scenario_slug', 50);
            $table->string('status', 20)->default('not_started');
            $table->integer('score')->default(0);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['user_id', 'scenario_slug'], 'unique_progress');
            $table->index('user_id', 'idx_progress_user');
            $table->index('scenario_slug', 'idx_progress_scenario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_progress');
    }
};
