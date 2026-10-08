<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->id();
            // "user:<id>" for signed-in callers, "ip:<address>" otherwise.
            $table->string('scope', 120);
            $table->string('key', 255);
            $table->string('method', 10);
            $table->string('path', 2048);
            // sha256 of method, path and body: the same key with a different request is refused.
            $table->char('request_hash', 64);
            // Null while the first request is still running.
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['scope', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
