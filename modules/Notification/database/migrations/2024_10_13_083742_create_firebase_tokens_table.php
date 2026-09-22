<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firebase_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token')->nullable();
            $table->string('device_id')->nullable();
            $table->timestamps();

            $table->unique(['token', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firebase_tokens');
    }
};
