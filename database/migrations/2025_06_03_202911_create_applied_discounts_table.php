<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('applied_discounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sub_order_item_id')
                ->constrained('sub_order_items')
                ->onDelete('cascade');

            $table->foreignId('discount_rule_id')
                ->constrained('discount_rules')
                ->onDelete('cascade');

            $table->float('amount');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applied_discounts');
    }
};
