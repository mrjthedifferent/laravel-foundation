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
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group')->default('General');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['text', 'textarea', 'file', 'image', 'integer', 'float', 'boolean', 'select', 'multi-select', 'array', 'disabled', 'json'])->default('text');
            $table->json('options')->nullable(); // for dropdown type.
            $table->string('description')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_disabled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
