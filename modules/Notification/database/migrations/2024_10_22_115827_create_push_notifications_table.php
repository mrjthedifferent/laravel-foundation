<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('image')->nullable();
            $table->string('url')->nullable();
            $table->text('description')->nullable();
            $table->json('result')->nullable();
            $table->string('recipient_type')->default('all');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notifications');
    }
};
