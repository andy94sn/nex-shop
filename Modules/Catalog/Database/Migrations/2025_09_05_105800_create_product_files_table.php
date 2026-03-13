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
        Schema::create(table: 'product_files', callback: function (Blueprint $table) {
            $table->id();
            $table->foreignId(column: 'product_id')->constrained();
            $table->text(column: 'filename')->nullable();
            $table->text(column: 'path')->nullable();
            $table->boolean(column: 'is_presentation')->default(value: false);
            $table->integer(column: 'sort_order')->default(value: 0);
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
        Schema::dropIfExists(table: 'product_files');
    }
};
