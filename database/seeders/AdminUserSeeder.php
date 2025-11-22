<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class AdminUserSeeder extends Seeder
{
    /**
     * Ensure a demo administrator account exists.
     */
    public function run(): void
    {
        $user = User::firstOrNew(['email' => 'admin@demo.com']);

        $user->name = 'Demo Admin';
        $user->email_verified_at = now();
        $user->is_admin = true;
        $user->is_author = true;
        $user->is_banned = false;
        $user->password = Hash::make('password');

        $user->save();
    }
}



