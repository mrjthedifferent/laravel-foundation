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
        Schema::create('error_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('fingerprint')->index();
            $table->string('exception_class');
            $table->text('message');
            $table->string('file');
            $table->unsignedInteger('line');
            $table->json('trace')->nullable();
            $table->string('request_method')->nullable();
            $table->string('request_path')->nullable();
            $table->text('request_url')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('context')->nullable();
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_reports');
    }
};
