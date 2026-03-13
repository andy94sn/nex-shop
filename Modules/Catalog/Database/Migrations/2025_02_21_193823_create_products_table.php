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
        Schema::create(table: "products", callback: function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->nullable();
            $table->boolean(column: "status")->default(true);
            $table->string('article')->nullable();
            $table->string('code')->nullable();
            $table->json('title');
            $table->json('description')->nullable();
            $table->float('rrp')->nullable();
            $table->float('rrp_old')->nullable();
            $table->float('price_euro')->nullable();
            $table->float('price_usd')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('height')->nullable();
            $table->integer('length')->nullable();
            $table->integer('width')->nullable();
            $table->timestamp("is_new_until")->nullable();
            $table->timestamp("rrp_updated_at")->nullable();
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
        Schema::dropIfExists(table: "products");
    }
};
