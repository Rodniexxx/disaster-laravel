<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('game_versions')) {
            return;
        }

        Schema::create('game_versions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('version_number', 50);
            $table->string('platform', 50)->default('Windows');
            $table->string('status', 20)->default('active');
            $table->text('release_notes')->nullable();
            $table->timestamp('release_date')->nullable();
            $table->string('download_url', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_versions');
    }
};
