<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firebase_tokens', function (Blueprint $table): void {
            $table->string('platform', 20)->nullable()->after('device_id');
        });
    }

    public function down(): void
    {
        Schema::table('firebase_tokens', function (Blueprint $table): void {
            $table->dropColumn('platform');
        });
    }
};
