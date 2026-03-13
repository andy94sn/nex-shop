<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_brands', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->json('slug')->nullable();              // translatable: {ro: '...', ru: '...'}
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('featured_sort')->default(0);
            // SEO (Task 3)
            $table->json('meta_title')->nullable();        // translatable
            $table->json('meta_description')->nullable();  // translatable
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_brands');
    }
};
