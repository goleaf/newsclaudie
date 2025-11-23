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
     * Changes content column from string(1024) to text for better flexibility.
     * Text columns can store up to 65,535 characters, which is more appropriate
     * for user-generated content like comments.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->text('content')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->string('content', 1024)->change();
        });
    }
};
