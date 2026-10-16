<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the existing game_versions table so the admin Game Versions page can
 * manage the public /download page: release titles, uploaded build files and
 * their metadata, per-platform requirements and the "latest" flag.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('game_versions')) {
            return;
        }

        Schema::table('game_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('game_versions', 'release_title')) {
                $table->string('release_title', 150)->nullable();
            }

            if (! Schema::hasColumn('game_versions', 'file_path')) {
                $table->string('file_path', 255)->nullable();
            }

            if (! Schema::hasColumn('game_versions', 'file_name')) {
                $table->string('file_name', 255)->nullable();
            }

            if (! Schema::hasColumn('game_versions', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable();
            }

            if (! Schema::hasColumn('game_versions', 'requirements')) {
                $table->string('requirements', 255)->nullable();
            }

            if (! Schema::hasColumn('game_versions', 'is_latest')) {
                $table->boolean('is_latest')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('game_versions')) {
            return;
        }

        Schema::table('game_versions', function (Blueprint $table) {
            foreach (['is_latest', 'requirements', 'file_size', 'file_name', 'file_path', 'release_title'] as $column) {
                if (Schema::hasColumn('game_versions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
