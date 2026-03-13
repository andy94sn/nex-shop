<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Banners (Task 11) ─────────────────────────────────────────────
        Schema::create('shop_banners', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable();              // translatable (alt text)
            $table->json('image');                          // translatable – image per locale
            $table->json('image_mobile')->nullable();       // translatable
            $table->string('url')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
        });

        // ── Promo Banner – single record (Task 12) ────────────────────────
        Schema::create('promo_banners', function (Blueprint $table) {
            $table->id();
            $table->json('image');                          // translatable
            $table->json('image_mobile')->nullable();       // translatable
            $table->string('url')->nullable();
            $table->json('title')->nullable();              // translatable
            $table->json('subtitle')->nullable();           // translatable
            $table->json('description')->nullable();        // translatable
            $table->json('button_text')->nullable();        // translatable
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
        });

        // ── Pages / CMS (Task 9a, 9c, 9d) ────────────────────────────────
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');                          // translatable
            $table->json('content')->nullable();            // translatable rich text
            $table->boolean('is_active')->default(true);
            $table->string('type')->default('generic');     // generic|terms|about|contact|faq
            // SEO
            $table->json('meta_title')->nullable();         // translatable
            $table->json('meta_description')->nullable();   // translatable
            // Contact page extras (Task 9d)
            $table->json('intro_text')->nullable();         // translatable
            $table->decimal('map_lat', 10, 7)->nullable();
            $table->decimal('map_lng', 10, 7)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Contact Departments (Task 9d) ─────────────────────────────────
        Schema::create('contact_departments', function (Blueprint $table) {
            $table->id();
            $table->json('title');                          // translatable
            $table->json('address')->nullable();            // translatable
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->json('schedule_mon_fri')->nullable();   // {from, to}
            $table->json('schedule_sat')->nullable();
            $table->json('schedule_sun')->nullable();       // {from, to, is_day_off}
            $table->json('schedule_break')->nullable();     // {from, to}
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── FAQ Items (Task 9b) ───────────────────────────────────────────
        Schema::create('faq_items', function (Blueprint $table) {
            $table->id();
            $table->json('question');                       // translatable
            $table->json('answer');                         // translatable rich text
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Support Requests (Task 9b) ────────────────────────────────────
        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->text('message');
            $table->string('page_source')->nullable();
            $table->string('status')->default('new');      // new|read|replied
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
        Schema::dropIfExists('faq_items');
        Schema::dropIfExists('contact_departments');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('promo_banners');
        Schema::dropIfExists('banners');
    }
};
