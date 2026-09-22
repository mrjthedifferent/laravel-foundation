<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The dashboard charts sign-ins per day across every user. The existing index
 * leads with `user_id`, so an unfiltered scan by date could not use it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_login_history', function (Blueprint $table): void {
            $table->index('logged_in_at', 'user_login_history_logged_in_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('user_login_history', function (Blueprint $table): void {
            $table->dropIndex('user_login_history_logged_in_at_index');
        });
    }
};
