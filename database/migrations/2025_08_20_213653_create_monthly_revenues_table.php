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
        if (!Schema::hasTable('monthly_revenues')) {
            Schema::create('monthly_revenues', function (Blueprint $t) {
                $t->id();
                $t->smallInteger('year');
                $t->tinyInteger('month'); // 1..12
                $t->decimal('total_revenue', 18, 2);
                $t->timestamps();
                $t->unique(['year', 'month']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_revenues');
    }
};
