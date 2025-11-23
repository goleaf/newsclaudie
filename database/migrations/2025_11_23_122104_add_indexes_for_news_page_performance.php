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
     * Adds indexes to optimize news page queries:
     * - posts.published_at for date filtering and sorting
     * - posts.user_id for author filtering
     * - category_post pivot table already has indexes via unique constraint
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Add index on published_at for efficient date filtering and sorting
            $table->index('published_at', 'idx_posts_published_at');
            
            // Add index on user_id for efficient author filtering
            $table->index('user_id', 'idx_posts_user_id');
        });

        // Note: category_post pivot table already has a composite unique index
        // on (category_id, post_id) which provides efficient lookups for both
        // category filtering and post-category relationships.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes if they exist (SQLite-safe approach)
        $connection = Schema::getConnection();
        
        try {
            $connection->statement('DROP INDEX IF EXISTS idx_posts_published_at');
        } catch (\Exception $e) {
            // Index doesn't exist, continue
        }
        
        try {
            $connection->statement('DROP INDEX IF EXISTS idx_posts_user_id');
        } catch (\Exception $e) {
            // Index doesn't exist, continue
        }
    }
};
