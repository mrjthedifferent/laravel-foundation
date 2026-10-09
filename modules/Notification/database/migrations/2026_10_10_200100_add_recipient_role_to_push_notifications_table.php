<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notifications', function (Blueprint $table): void {
            $table->string('recipient_role')->nullable()->after('recipient_type');
        });
    }

    public function down(): void
    {
        Schema::table('push_notifications', function (Blueprint $table): void {
            $table->dropColumn('recipient_role');
        });
    }
};
