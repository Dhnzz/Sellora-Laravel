<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monthly_revenue_predictions', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year'); // periode yg DIPREDIKSI (bulan depan)
            $table->tinyInteger('month'); // 1..12
            $table->decimal('predicted_profit', 18, 2);
            $table->decimal('threshold_profit', 18, 2)->nullable();
            $table->boolean('is_profitable')->default(false);
            $table->decimal('pct_change_vs_last', 8, 2)->nullable();
            $table->string('model_version', 64)->default('lstm_v1');
            $table->json('meta')->nullable(); // isi plan cashflow + debug scaler, dsb
            $table->timestamps();
            $table->unique(['year', 'month', 'model_version'], 'uniq_pred_period_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_revenue_predictions');
    }
};
