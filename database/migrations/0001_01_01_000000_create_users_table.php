<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shop uses guest-only sessions (no authentication).
     * Cart, wishlist, comparisons and last-order data are all keyed by session ID.
     *
     * The 'users' table is reserved for B2B API clients in the shared database —
     * do NOT create it here.
     */
    public function up(): void
    {
        Schema::create('shop_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_sessions');
    }
};
