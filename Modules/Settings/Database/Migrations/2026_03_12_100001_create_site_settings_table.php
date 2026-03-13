<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('site_logo')->nullable();
            $table->string('site_favicon')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->json('contact_address')->nullable();   // translatable
            $table->unsignedBigInteger('terms_page_id')->nullable();
            $table->string('currency_symbol', 10)->default('Lei');
            $table->string('currency_code', 10)->default('MDL');
            $table->string('default_locale', 10)->default('ro');
            // Global SEO
            $table->json('seo_title')->nullable();         // translatable
            $table->json('seo_description')->nullable();   // translatable
            $table->string('seo_og_image')->nullable();
            // Footer
            $table->json('footer_text')->nullable();       // translatable rich text
            $table->string('footer_logo')->nullable();
            $table->string('footer_logo_dark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
