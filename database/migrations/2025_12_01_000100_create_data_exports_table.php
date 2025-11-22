<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('data_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50)->index();
            $table->string('format', 10);
            $table->string('status', 20)->default('pending')->index();
            $table->string('disk', 50)->default(config('filesystems.default', 'local'));
            $table->string('path')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->json('filters')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_exports');
    }
};
