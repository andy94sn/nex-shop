<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Promotions (Task 22) ──────────────────────────────────────────
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->json('title');                          // translatable
            $table->string('slug')->unique();
            $table->json('banner_image')->nullable();       // translatable
            $table->json('banner_image_mobile')->nullable();// translatable
            $table->string('status')->default('draft');     // draft|active|expired
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('meta_title')->nullable();         // translatable
            $table->json('meta_description')->nullable();   // translatable
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_promotion', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->primary(['promotion_id', 'product_id']);
        });

        // ── Newsletter Subscribers (Task 23) ──────────────────────────────
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('locale', 10)->default('ro');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
        Schema::dropIfExists('product_promotion');
        Schema::dropIfExists('promotions');
    }
};
