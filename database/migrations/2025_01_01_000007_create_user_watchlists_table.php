<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_watchlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('cryptocurrency_id')->constrained()->onDelete('cascade');
            $table->boolean('notifications_enabled')->default(true);
            $table->timestamps();
            
            $table->unique(['user_id', 'cryptocurrency_id']);
            $table->index(['user_id', 'notifications_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_watchlists');
    }
};