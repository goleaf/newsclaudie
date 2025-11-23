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
     * Adds index on approved_at for analytics queries.
     * Supports: Comment::approvedBetween() scope
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            // Index for approved_at to optimize date range queries
            $table->index('approved_at', 'comments_approved_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex('comments_approved_at_index');
        });
    }
};
