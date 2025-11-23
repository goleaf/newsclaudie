<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys with proper cascade rules
            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete(); // Delete comments when user is deleted
            
            $table->foreignIdFor(Post::class)
                ->constrained()
                ->cascadeOnDelete(); // Delete comments when post is deleted

            $table->text('content'); // Changed from string(1024) to text for flexibility

            $table->timestamps();
            
            // Add indexes for foreign keys (if not automatically created)
            $table->index('user_id');
            $table->index('post_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comments');
    }
};
