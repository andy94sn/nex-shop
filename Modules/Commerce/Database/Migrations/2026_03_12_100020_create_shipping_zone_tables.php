<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates all shipping-related tables in the correct dependency order.
 *
 * Runs before create_commerce_tables.php (timestamp 100020 < 100030) so that
 * the `orders` table can reference delivery_methods, payment_methods and
 * shipping_zones via foreign keys from day one.
 *
 * Table hierarchy:
 *   delivery_methods
 *   payment_methods
 *   countries
 *   cities                     → country_id FK
 *   shipping_zones
 *   shipping_zone_countries    → zone + country pivot
 *   shipping_zone_cities       → zone + city pivot
 *   shipping_rates             → zone + delivery_method + payment_method
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Delivery Methods ──────────────────────────────────────────────
        Schema::create('delivery_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();               // 'shipping', 'pickup'
            $table->json('name');                           // translatable
            $table->json('description')->nullable();        // translatable
            $table->string('icon')->nullable();
            $table->boolean('requires_address')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // ── Payment Methods ───────────────────────────────────────────────
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();               // 'cash', 'cod', 'maib', 'credit'
            $table->json('name');                           // translatable
            $table->json('description')->nullable();        // translatable
            $table->string('icon')->nullable();
            $table->boolean('is_online')->default(false);
            $table->string('gateway')->nullable();          // 'maib', 'stripe', …
            $table->json('gateway_config')->nullable();     // arbitrary gateway JSON config
            $table->boolean('requires_credit_plan')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // ── Countries ─────────────────────────────────────────────────────
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();            // ISO-3166 alpha-2 (MD, RO, …)
            $table->json('name');                           // translatable
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // ── Cities ────────────────────────────────────────────────────────
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->json('name');                           // translatable
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // ── Shipping Zones ────────────────────────────────────────────────
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->json('name');                           // translatable, e.g. "Chișinău"
            $table->decimal('default_cost', 10, 2)->default(0); // fallback when no rate matches
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // ── Zone ↔ Country pivot (whole-country coverage) ─────────────────
        Schema::create('shipping_zone_countries', function (Blueprint $table) {
            $table->foreignId('shipping_zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->primary(['shipping_zone_id', 'country_id']);
        });

        // ── Zone ↔ City pivot (city-level, overrides country) ─────────────
        Schema::create('shipping_zone_cities', function (Blueprint $table) {
            $table->foreignId('shipping_zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->primary(['shipping_zone_id', 'city_id']);
        });

        // ── Shipping Rates ────────────────────────────────────────────────
        // One rate row = one conditional cost rule inside a zone.
        // Geographic targeting is at the zone level; rates only carry
        // method conditions and cart-range conditions.
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained('shipping_zones')->cascadeOnDelete();

            // Conditions (all nullable = wildcard / "match any")
            $table->foreignId('delivery_method_id')
                ->nullable()->constrained('delivery_methods')->nullOnDelete();
            $table->foreignId('payment_method_id')
                ->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->decimal('cart_min', 10, 2)->nullable(); // cart total >= cart_min
            $table->decimal('cart_max', 10, 2)->nullable(); // cart total <= cart_max

            // Result
            $table->decimal('cost', 10, 2)->default(0);
            $table->boolean('is_free')->default(false);
            $table->json('label')->nullable();              // translatable label

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);    // lower = higher priority
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_zone_cities');
        Schema::dropIfExists('shipping_zone_countries');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('delivery_methods');
    }
};
