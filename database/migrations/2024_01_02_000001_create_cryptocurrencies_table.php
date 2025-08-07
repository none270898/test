<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cryptocurrencies', function (Blueprint $table) {
            $table->id();
            $table->string('coingecko_id')->unique();
            $table->string('symbol');
            $table->string('name');
            $table->string('image')->nullable();
            $table->decimal('current_price_usd', 20, 8)->default(0);
            $table->decimal('current_price_pln', 20, 8)->default(0);
            $table->decimal('price_change_24h', 10, 2)->default(0);
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cryptocurrencies');
    }
};