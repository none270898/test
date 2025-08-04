<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cryptocurrencies', function (Blueprint $table) {
            $table->id();
            $table->string('coingecko_id')->unique();
            $table->string('symbol')->index();
            $table->string('name');
            $table->string('image')->nullable();
            $table->decimal('current_price_usd', 20, 8)->nullable();
            $table->decimal('current_price_pln', 20, 8)->nullable();
            $table->decimal('market_cap', 20, 2)->nullable();
            $table->integer('market_cap_rank')->nullable();
            $table->decimal('price_change_24h', 10, 4)->nullable();
            $table->decimal('price_change_percentage_24h', 10, 4)->nullable();
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cryptocurrencies');
    }
};