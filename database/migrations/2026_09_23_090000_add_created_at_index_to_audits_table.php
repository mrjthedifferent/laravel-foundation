<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The dashboard counts and charts audits by day, and the activity log filters by
 * date; both scan `created_at`, which carried no index of its own.
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
            $table->index('created_at', 'audits_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table($this->table(), function (Blueprint $table): void {
            $table->dropIndex('audits_created_at_index');
        });
    }
};
