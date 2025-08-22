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
        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_agent_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_id')->unique();
            $table->date('invoice_date');
            $table->decimal('discount_percent', 5, 2)->nullable()->check('discount_percent >= 0 AND discount_percent <= 100');
            $table->decimal('initial_total_amount', 15, 4)->check('initial_total_amount >= 0');
            $table->decimal('final_total_amount', 15, 4)->check('final_total_amount >= 0');
            $table->text('note');
            $table->enum('transaction_status', ['process', 'success'])->default('process');
            $table->timestamp('delivery_confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['invoice_date', 'sales_agent_id', 'transaction_status'], 'st_invdate_agent_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_transactions');
    }
};
