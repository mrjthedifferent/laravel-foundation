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
        Schema::create('email_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->nullable();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->string('mailer')->nullable();
            $table->string('notification')->nullable();
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('uuid');
            $table->index('to_email');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
