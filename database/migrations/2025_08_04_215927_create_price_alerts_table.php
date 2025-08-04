<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('price_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('cryptocurrency_id')->constrained()->onDelete('cascade');
            $table->enum('alert_type', ['above', 'below']);
            $table->decimal('target_price', 20, 8);
            $table->string('currency', 3)->default('PLN');
            $table->boolean('is_active')->default(true);
            $table->boolean('email_notification')->default(true);
            $table->boolean('push_notification')->default(true);
            $table->timestamp('triggered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('price_alerts');
    }
};
