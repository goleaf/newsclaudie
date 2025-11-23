<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds metadata columns for moderation and spam prevention:
     * - ip_address: For tracking and blocking spam sources
     * - user_agent: For identifying bots and automated submissions
     * - approved_at: Timestamp when comment was approved (useful for analytics)
     * - approved_by: Admin who approved the comment (accountability)
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->string('ip_address', 45)->nullable()->after('status'); // IPv6 support (45 chars)
            $table->string('user_agent', 500)->nullable()->after('ip_address');
            $table->timestamp('approved_at')->nullable()->after('user_agent');
            $table->foreignId('approved_by')->nullable()->after('approved_at')
                ->constrained('users')
                ->nullOnDelete(); // Keep comment if approver is deleted
            
            // Index for spam detection queries
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['ip_address']);
            $table->dropColumn(['ip_address', 'user_agent', 'approved_at', 'approved_by']);
        });
    }
};
