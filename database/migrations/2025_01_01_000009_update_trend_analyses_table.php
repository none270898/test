<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trend_analyses', function (Blueprint $table) {
            $table->date('analysis_date')->nullable()->after('analysis_period_end');
            $table->json('hourly_breakdown')->nullable()->after('confidence_score');
            $table->decimal('previous_sentiment', 3, 2)->nullable()->after('sentiment_avg');
            $table->decimal('sentiment_change', 3, 2)->nullable()->after('previous_sentiment');
        });

        // Krok 2: Uzupełnij analysis_date dla istniejących rekordów
        DB::statement("UPDATE trend_analyses SET analysis_date = DATE(IFNULL(created_at, NOW())) WHERE analysis_date IS NULL");

        // Krok 3: Zmień kolumnę analysis_date na NOT NULL
        DB::statement("ALTER TABLE trend_analyses MODIFY COLUMN analysis_date DATE NOT NULL");

        // Krok 4: Dodaj indeks
        Schema::table('trend_analyses', function (Blueprint $table) {
            $table->index(['analysis_date', 'confidence_score'], 'idx_analysis_date_confidence');
        });
    }

    public function down(): void
    {
        Schema::table('trend_analyses', function (Blueprint $table) {
            // Usuń indeks
            $table->dropIndex('idx_analysis_date_confidence');
            
            // Usuń kolumny
            $table->dropColumn([
                'analysis_date', 
                'hourly_breakdown', 
                'previous_sentiment', 
                'sentiment_change'
            ]);
        });
    }
};

