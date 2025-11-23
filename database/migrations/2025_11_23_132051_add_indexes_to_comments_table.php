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
     * Adds composite indexes for common query patterns:
     * - status + created_at: For filtering approved/pending/rejected comments with ordering
     * - post_id + status: For getting comments by post and status
     * - user_id + created_at: For user comment history
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            // Composite index for status filtering with date ordering
            // Supports: Comment::approved()->latest()
            $table->index(['status', 'created_at'], 'comments_status_created_at_index');
            
            // Composite index for post comments by status
            // Supports: $post->comments()->approved()
            $table->index(['post_id', 'status'], 'comments_post_id_status_index');
            
            // Composite index for user comments with date ordering
            // Supports: User comment history queries
            $table->index(['user_id', 'created_at'], 'comments_user_id_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex('comments_status_created_at_index');
            $table->dropIndex('comments_post_id_status_index');
            $table->dropIndex('comments_user_id_created_at_index');
        });
    }
};
