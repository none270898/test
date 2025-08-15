<?php
// database/seeders/AdminSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sprawdź czy admin już istnieje
        $admin = User::create([
            'name' => 'CryptoNote Admin',
            'email' => 'admin@cryptonote.pl',
            'email_verified_at' => now(),
            'password' => Hash::make('Ka!.091mLmuTrlin1589'),
            'premium' => true,
            'premium_expires_at' => now()->addYear(),
            'is_admin' => true,
            'alerts_enabled' => true,
            'email_notifications' => true,
        ]);

        $this->command->info('✅ Admin user created: admin@cryptonote.pl (password: admin123!)');
    }
}