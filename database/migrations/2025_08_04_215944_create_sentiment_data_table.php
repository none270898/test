<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sentiment_data', function (Blueprint $table) {
            $table->id();
            $table->enum('source', ['reddit', 'twitter', 'bitcoin_pl', 'bithub_pl']);
            $table->text('content');
            $table->string('url')->nullable();
            $table->decimal('sentiment_score', 3, 2); // -1.00 to 1.00
            $table->json('keywords'); // ['BTC', 'ETH', etc.]
            $table->string('language', 2)->default('pl');
            $table->timestamp('published_at');
            $table->timestamps();
            
            $table->index(['source', 'published_at']);
            $table->index('sentiment_score');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sentiment_data');
    }
};