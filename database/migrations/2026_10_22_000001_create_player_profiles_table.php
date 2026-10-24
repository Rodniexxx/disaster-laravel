<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('player_profiles', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->primary();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('display_name', 50);
            $table->string('avatar_path')->nullable();
            $table->string('avatar_disk', 32)->nullable();
            $table->uuid('avatar_version')->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('player_profiles'); }
};
