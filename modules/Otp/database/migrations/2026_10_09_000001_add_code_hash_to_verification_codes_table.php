<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Codes are stored as a keyed hash from 1.8 on; the plain `code` column is
     * kept (nullable, wider) so rows written by older versions still verify.
     */
    public function up(): void
    {
        Schema::table('verification_codes', function (Blueprint $table): void {
            $table->string('code_hash', 64)->nullable()->after('code');
        });

        Schema::table('verification_codes', function (Blueprint $table): void {
            $table->string('code', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('verification_codes', function (Blueprint $table): void {
            $table->dropColumn('code_hash');
        });
    }
};
