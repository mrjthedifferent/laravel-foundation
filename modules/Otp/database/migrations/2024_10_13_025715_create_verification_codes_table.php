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
        Schema::create('verification_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 6);
            $table->enum('contact_type', ['email', 'phone']);
            $table->string('contact');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['contact_type', 'contact']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
    }
};
