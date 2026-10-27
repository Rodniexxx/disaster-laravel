<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Existing installations may have a signed users.id, while fresh
        // installations use Laravel's unsigned increments() column.
        $userIdType = collect(Schema::getColumns('users'))->firstWhere('name', 'id')['type'] ?? '';
        $unsigned = str_contains(strtolower($userIdType), 'unsigned');

        // MySQL can leave this empty table behind when creating its foreign
        // key fails; resume the migration without dropping profile data.
        if (! Schema::hasTable('player_profiles')) {
            Schema::create('player_profiles', function (Blueprint $table) use ($unsigned) {
                if ($unsigned) {
                    $table->unsignedInteger('user_id')->primary();
                } else {
                    $table->integer('user_id')->primary();
                }
                $table->string('display_name', 50);
                $table->string('avatar_path')->nullable();
                $table->string('avatar_disk', 32)->nullable();
                $table->uuid('avatar_version')->nullable();
                $table->unsignedInteger('revision')->default(0);
                $table->timestamps();
            });
        } else {
            $profileIdType = collect(Schema::getColumns('player_profiles'))->firstWhere('name', 'user_id')['type'] ?? '';
            if (str_contains(strtolower($profileIdType), 'unsigned') !== $unsigned) {
                Schema::table('player_profiles', function (Blueprint $table) use ($unsigned) {
                    if ($unsigned) {
                        $table->unsignedInteger('user_id')->change();
                    } else {
                        $table->integer('user_id')->change();
                    }
                });
            }
        }

        $hasUserForeignKey = collect(Schema::getForeignKeys('player_profiles'))
            ->contains(fn (array $key) => in_array('user_id', $key['columns'] ?? [], true));
        if (! $hasUserForeignKey) {
            Schema::table('player_profiles', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('player_profiles');
    }
};