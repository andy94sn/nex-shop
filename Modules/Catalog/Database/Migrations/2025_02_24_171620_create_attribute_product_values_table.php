<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(table: "product_attribute_values", callback: function (Blueprint $table) {
            $table->foreignId('product_id')->constrained();
            $table->foreignId('attribute_value_id')->constrained();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['product_id', 'attribute_value_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(table: "product_attribute_values");
    }
};
