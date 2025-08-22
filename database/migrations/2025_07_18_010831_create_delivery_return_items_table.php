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
        Schema::create('delivery_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('unit_price');
            $table->integer('quantity');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['delivery_return_id', 'product_id'], 'dri_dr_product_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_return_items');
    }
};
