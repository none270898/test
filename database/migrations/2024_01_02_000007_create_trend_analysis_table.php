<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trend_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cryptocurrency_id')->constrained()->onDelete('cascade');
            $table->decimal('sentiment_avg', 3, 2); // Average sentiment
            $table->integer('mention_count'); // How many mentions in timeframe
            $table->enum('trend_direction', ['up', 'down', 'neutral']);
            $table->integer('confidence_score'); // 0-100%
            $table->timestamp('analysis_period_start');
            $table->timestamp('analysis_period_end')->nullable();
            $table->timestamps();
            
            $table->index(['cryptocurrency_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trend_analyses');
    }
};