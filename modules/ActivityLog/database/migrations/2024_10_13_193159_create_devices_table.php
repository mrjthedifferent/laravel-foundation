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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('device_id')->unique();
            $table->string('device_label')->nullable();
            $table->string('device_type')->nullable();
            $table->string('os')->nullable();
            $table->string('os_version')->nullable();
            $table->string('model')->nullable();
            $table->string('app_version')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
