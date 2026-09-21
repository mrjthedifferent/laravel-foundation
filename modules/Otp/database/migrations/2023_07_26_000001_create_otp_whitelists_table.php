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
        Schema::create('otp_whitelists', function (Blueprint $table) {
            $table->id();
            $table->enum('recipient_type', ['email', 'phone'])->comment('Type of recipient');
            $table->string('recipient')->comment('Email or phone number');
            $table->string('fixed_otp', 6)->comment('Fixed OTP for this recipient');
            $table->boolean('is_active')->default(true)->comment('Whether this whitelist entry is active');
            $table->text('description')->nullable()->comment('Optional description for this whitelist entry');
            $table->timestamps();

            // Add a unique index to prevent duplicate entries
            $table->unique(['recipient_type', 'recipient']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_whitelists');
    }
};
