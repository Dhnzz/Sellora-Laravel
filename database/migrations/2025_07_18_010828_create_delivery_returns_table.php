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
        Schema::create('delivery_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_transaction_id')->constrained()->cascadeOnDelete();
            $table->date('return_date');
            $table->text('reason');
            $table->enum('status', ['pending', 'confirmed'])->default('pending');
            $table->foreignId('admin_id')->constrained()->cascadeOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->integer('total_amount');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['sales_transaction_id', 'return_date', 'total_amount'], 'dr_st_retdate_total_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_returns');
    }
};
