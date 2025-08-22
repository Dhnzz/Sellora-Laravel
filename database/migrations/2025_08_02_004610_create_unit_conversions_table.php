<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unit_convertions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_unit_id')->constrained('product_units')->onDelete('cascade');
            $table->foreignId('to_unit_id')->constrained('product_units')->onDelete('cascade');
            $table->integer('convertion_factor')->unsigned();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['product_id', 'from_unit_id', 'to_unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_convertions');
    }
};
