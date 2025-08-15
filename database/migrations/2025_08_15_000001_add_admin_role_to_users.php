<?php
// database/migrations/2025_08_15_000001_add_admin_role_to_users.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('premium_expires_at');
            $table->index('is_admin');
        });
        
        // Stwórz pierwszego administratora (zmień email na swój)
        DB::table('users')->where('email', 'lnone270898l@gmail.com')->update(['is_admin' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_admin']);
            $table->dropColumn('is_admin');
        });
    }
};