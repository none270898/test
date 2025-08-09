<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sentiment_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('cryptocurrency_id')->constrained()->onDelete('cascade');
            $table->enum('trigger_type', ['sentiment_spike', 'sentiment_drop', 'mention_spike']);
            $table->decimal('threshold_value', 3, 2); // For sentiment thresholds
            $table->integer('mention_threshold')->nullable(); // For mention count thresholds
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
            
            $table->index(['is_active', 'trigger_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sentiment_alerts');
    }
};