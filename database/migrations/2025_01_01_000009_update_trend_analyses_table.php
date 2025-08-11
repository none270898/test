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
            // Add columns with default values first
            $table->date('analysis_date')->default(DB::raw('CURRENT_DATE'))->after('analysis_period_end');
            $table->json('hourly_breakdown')->nullable()->after('confidence_score');
            $table->decimal('previous_sentiment', 3, 2)->nullable()->after('sentiment_avg');
            $table->decimal('sentiment_change', 3, 2)->nullable()->after('previous_sentiment');
        });

        // Update existing records to have proper analysis_date
        DB::table('trend_analyses')->update([
            'analysis_date' => DB::raw('DATE(created_at)')
        ]);

        // Now add the unique constraint
        Schema::table('trend_analyses', function (Blueprint $table) {
            $table->index(['analysis_date', 'confidence_score']);
        });
    }

    public function down(): void
    {
        Schema::table('trend_analyses', function (Blueprint $table) {
            $table->dropIndex(['analysis_date', 'confidence_score']);
            $table->dropColumn(['analysis_date', 'hourly_breakdown', 'previous_sentiment', 'sentiment_change']);
        });
    }
};

