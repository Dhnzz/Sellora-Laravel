<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_association_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->json('atecedent_product_ids'); // mengikuti typo kolom di tabel global
            $table->json('consequent_product_ids');
            $table->float('support')->default(0);
            $table->float('confidence')->default(0);
            $table->float('lift')->default(0);
            $table->date('analysis_date')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_association_customers');
    }
};
