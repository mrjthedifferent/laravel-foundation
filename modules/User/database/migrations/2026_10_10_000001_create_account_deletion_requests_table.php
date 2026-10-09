<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletion_requests', function (Blueprint $table): void {
            $table->id();
            // Never cascades: the request is the record that a deletion was asked for and done.
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            // pending_review · scheduled · rejected · cancelled · done
            $table->string('status', 20)->index();
            $table->string('source', 20)->default('app');
            $table->timestamp('requested_at');
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('anonymized_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('anonymized_at');
        });
        Schema::dropIfExists('account_deletion_requests');
    }
};
