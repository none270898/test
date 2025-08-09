<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cryptocurrencies', function (Blueprint $table) {
            $table->integer('daily_mentions')->default(0)->after('last_updated');
            $table->decimal('current_sentiment', 3, 2)->default(0)->after('daily_mentions');
            $table->decimal('sentiment_change_24h', 3, 2)->default(0)->after('current_sentiment');
            $table->integer('trending_score')->default(0)->after('sentiment_change_24h');
            $table->timestamp('sentiment_updated_at')->nullable()->after('trending_score');
            
            $table->index(['trending_score', 'daily_mentions']);
            $table->index(['current_sentiment', 'daily_mentions']);
        });
    }

    public function down(): void
    {
        Schema::table('cryptocurrencies', function (Blueprint $table) {
            $table->dropIndex(['trending_score', 'daily_mentions']);
            $table->dropIndex(['current_sentiment', 'daily_mentions']);
            $table->dropColumn([
                'daily_mentions', 
                'current_sentiment', 
                'sentiment_change_24h', 
                'trending_score',
                'sentiment_updated_at'
            ]);
        });
    }
};