<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `type` was a fixed enum, so every new setting type (this migration adds
     * "encrypted") meant a schema migration. A plain string keeps the column
     * open-ended; the allowed values are already enforced where it matters,
     * in StoreSettingRequest/UpdateSettingRequest.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->string('type')->default('text')->change();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->enum('type', ['text', 'textarea', 'file', 'image', 'integer', 'float', 'boolean', 'select', 'multi-select', 'array', 'disabled', 'json'])->default('text')->change();
        });
    }
};
