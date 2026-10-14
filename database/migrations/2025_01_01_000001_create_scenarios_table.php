<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scenarios')) {
            return;
        }

        Schema::create('scenarios', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 50)->unique();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->text('learning_objectives')->nullable();
            $table->string('difficulty', 40)->default('Intermediate');
            $table->string('status', 20)->default('active');
            $table->text('safety_info')->nullable();
            $table->text('completion_info')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenarios');
    }
};
