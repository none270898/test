<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sentiment_data', function (Blueprint $table) {
            $table->id();
            $table->enum('source', ['reddit', 'twitter', 'forum', 'news']);
            $table->text('content');
            $table->decimal('sentiment_score', 3, 2); // -1.00 to 1.00
            $table->json('keywords'); // ['BTC', 'ETH', etc.]
            $table->string('source_url')->nullable();
            $table->string('author')->nullable();
            $table->timestamp('published_at');
            $table->timestamps();
            
            $table->index(['published_at', 'sentiment_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sentiment_data');
    }
};
