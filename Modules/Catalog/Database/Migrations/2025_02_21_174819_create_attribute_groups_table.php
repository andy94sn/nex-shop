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
        Schema::create(table: "attribute_groups", callback: function (Blueprint $table) {
            $table->id();
            $table->foreignId(column: "category_id")->nullable()->constrained();
            $table->boolean(column: "status")->default(true);
            $table->boolean(column: "is_default")->nullable();
            $table->json('title');
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
        Schema::dropIfExists(table: "attribute_groups");
    }
};
