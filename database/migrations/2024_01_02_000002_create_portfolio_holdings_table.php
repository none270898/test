<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('cryptocurrency_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 20, 8);
            $table->decimal('average_buy_price', 20, 8)->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'cryptocurrency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_holdings');
    }
};