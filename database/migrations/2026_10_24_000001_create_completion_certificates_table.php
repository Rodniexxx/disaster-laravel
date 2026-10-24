<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('completion_certificates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('milestone', 64);
            $table->string('certificate_id', 32)->unique();
            $table->timestamp('issued_at');
            $table->unique(['user_id', 'milestone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('completion_certificates');
    }
};
