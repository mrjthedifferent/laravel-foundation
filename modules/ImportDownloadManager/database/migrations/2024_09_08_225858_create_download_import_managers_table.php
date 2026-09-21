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
        Schema::create('download_import_managers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->comment('Requested User ID')->constrained()->index();
            $table->string('title');
            $table->string('url')->nullable();
            $table->longText('remarks')->nullable();
            $table->enum('status', ['pending', 'processing', 'failed', 'completed'])->default('pending');
            $table->enum('type', ['import', 'download']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_import_managers');
    }
};
