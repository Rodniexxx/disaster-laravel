<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('announcements')) {
            return;
        }

        Schema::create('announcements', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 150);
            $table->text('message');
            $table->timestamp('publish_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('status', 'idx_announcements_status');
            $table->index('publish_date', 'idx_announcements_publish_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
