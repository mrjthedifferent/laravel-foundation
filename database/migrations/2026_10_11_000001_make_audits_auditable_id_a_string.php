<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `auditable_id` was an unsigned bigint, so auditing a model with a ULID or UUID
 * key (every Syncable model) failed on insert. A string holds integer, ULID and
 * UUID keys alike; existing integer ids are kept as their text form.
 */
return new class extends Migration
{
    private function table(): string
    {
        return (string) config('audit.drivers.database.table', 'audits');
    }

    public function up(): void
    {
        Schema::table($this->table(), function (Blueprint $table): void {
            $table->string('auditable_id', 36)->change();
        });
    }

    public function down(): void
    {
        // Only safe while every auditable id is still numeric.
        Schema::table($this->table(), function (Blueprint $table): void {
            $table->unsignedBigInteger('auditable_id')->change();
        });
    }
};
