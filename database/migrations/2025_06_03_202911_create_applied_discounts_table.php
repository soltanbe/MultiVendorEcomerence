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
        Schema::create('applied_discounts', function ($table) {
            $table->id();
            $table->unsignedBigInteger('sub_order_item_id');
            $table->unsignedBigInteger('discount_rule_id');
            $table->float('amount');
            $table->timestamps();

            $table->foreign('sub_order_item_id')->references('id')->on('sub_order_items')->onDelete('cascade');
            $table->foreign('discount_rule_id')->references('id')->on('discount_rules')->onDelete('cascade');
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
