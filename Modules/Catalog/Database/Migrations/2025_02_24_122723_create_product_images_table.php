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
        Schema::create(table: "product_images", callback: function (Blueprint $table) {
            $table->id();
            $table->foreignId(column: "product_id")->constrained();
            $table->string(column: "filename");
            $table->string(column: "path");
            $table->boolean(column: "is_main")->default(false);
            $table->integer(column: 'sort_order')->default(value: 0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(table: "product_images");
    }
};
