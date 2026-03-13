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
        Schema::create(table: "currencies", callback: function (Blueprint $table) {
            $table->string(column: "currency_id")->primary();
            $table->string(column: "name")->nullable();
            $table->float(column: "rate")->nullable();
            $table->float(column: "multiple")->nullable();
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
        Schema::dropIfExists(table: "currencies");
    }
};
