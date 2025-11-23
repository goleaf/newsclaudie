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
     * Adds proper foreign key constraints with cascade rules to the comments table.
     * This ensures referential integrity and automatic cleanup when related records are deleted.
     * 
     * Note: This migration assumes the table was created without proper constraints.
     * If running on a fresh database, the constraints in the create_comments_table migration
     * will be used instead.
     */
    public function up(): void
    {
        // Check if constraints already exist before adding them
        Schema::table('comments', function (Blueprint $table): void {
            // Drop existing foreign keys if they exist (without cascade)
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }

            try {
                $table->dropForeign(['post_id']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }

            // Add foreign keys with proper cascade rules
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['post_id']);
        });
    }
};
