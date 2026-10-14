<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors disaster_game.leaderboard exactly as shipped in database.sql.
 * NOTE: the original schema has no unique key, which is preserved here so the
 * migrated app behaves identically to the legacy site.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leaderboard')) {
            return;
        }

        Schema::create('leaderboard', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('username', 50);
            $table->string('category', 30);
            $table->integer('score');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard');
    }
};
