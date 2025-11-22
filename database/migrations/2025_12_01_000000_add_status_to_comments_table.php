<?php

declare(strict_types=1);

use App\Enums\CommentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->string('status', 20)
                ->default(CommentStatus::Pending->value)
                ->index()
                ->after('content');
        });

        DB::table('comments')
            ->whereNull('status')
            ->update(['status' => CommentStatus::Approved->value]);
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};
