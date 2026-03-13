<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Credit Plans (Task 18) ────────────────────────────────────────
        Schema::create('credit_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('months');
            $table->float('interest_rate')->default(0);
            $table->string('interest_label', 20)->default('0%');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // ── Category ↔ CreditPlan pivot (Task 18) ────────────────────────
        Schema::create('category_credit_plan', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_plan_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'credit_plan_id']);
        });

        // ── Product ↔ CreditPlan override pivot (Task 18) ────────────────
        Schema::create('credit_plan_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_plan_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'credit_plan_id']);
        });

        // ── Credit Extras (Task 18) ───────────────────────────────────────
        Schema::create('credit_extras', function (Blueprint $table) {
            $table->id();
            $table->json('title');                          // translatable
            $table->float('price_per_month');
            $table->json('available_durations');            // [2, 3, 4, 5]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Shipping Regions (Task 20) ────────────────────────────────────
        Schema::create('shipping_regions', function (Blueprint $table) {
            $table->id();
            $table->json('name');                           // translatable
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // ── Coupons (Task 19) ─────────────────────────────────────────────
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('percentage'); // percentage|fixed
            $table->decimal('value', 10, 2);
            $table->decimal('min_order_value', 10, 2)->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('coupon_category', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'category_id']);
        });

        Schema::create('coupon_product', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'product_id']);
        });

        // ── Orders (Task 21d) ─────────────────────────────────────────────
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('status')->default('pending');  // pending|confirmed|processing|shipped|delivered|cancelled
            $table->string('payment_method')->default('cash'); // cash|credit
            $table->foreignId('credit_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->json('credit_extras_selected')->nullable();  // [{extra_id, duration}]
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->foreignId('shipping_region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shipping_address')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('coupon_discount', 12, 2)->default(0);
            // Credit client data
            $table->string('id_card_front')->nullable();
            $table->string('id_card_back')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('idnp', 13)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('article');
            $table->json('title');                         // snapshot
            $table->json('subtitle')->nullable();          // snapshot
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->string('image')->nullable();           // snapshot
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('coupon_product');
        Schema::dropIfExists('coupon_category');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('shipping_regions');
        Schema::dropIfExists('credit_extras');
        Schema::dropIfExists('credit_plan_product');
        Schema::dropIfExists('category_credit_plan');
        Schema::dropIfExists('credit_plans');
    }
};
