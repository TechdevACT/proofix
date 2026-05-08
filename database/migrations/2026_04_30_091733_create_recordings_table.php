<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('recordings', function (Blueprint $table) {
        $table->id();
        $table->string('order_number')->index();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('station')->nullable();
        $table->string('video_path')->nullable();
        $table->integer('duration_seconds')->nullable();
        $table->enum('upload_status', ['pending', 'uploaded', 'failed'])->default('pending');
        $table->timestamp('recorded_at');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
