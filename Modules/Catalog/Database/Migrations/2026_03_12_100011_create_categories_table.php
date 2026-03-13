<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // ── Hierarchy ──────────────────────────────────────────────────
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();

            // ── Shop identity ──────────────────────────────────────────────
            $table->json('slug')->nullable()->after('parent_id');          // translatable: {ro: '...', ru: '...'}

            // ── Content ────────────────────────────────────────────────────
            $table->json('short_description')->nullable()->after('title'); // translatable
            $table->json('description')->nullable()->after('short_description'); // translatable
            $table->string('featured_image')->nullable()->after('image_path');

            // ── Visibility flags ───────────────────────────────────────────
            $table->boolean('is_active')->default(true)->after('featured_image');
            $table->boolean('is_hidden_in_store')->default(false)->after('is_active');
            $table->boolean('is_hidden_in_menu')->default(false)->after('is_hidden_in_store');

            // ── Featured (homepage) ────────────────────────────────────────
            $table->boolean('is_featured')->default(false)->after('is_hidden_in_menu');
            $table->unsignedInteger('featured_sort')->default(0)->after('is_featured');

            // ── Display sort ───────────────────────────────────────────────
            $table->unsignedInteger('sort')->default(0)->after('featured_sort');

            // ── Misc ───────────────────────────────────────────────────────
            $table->json('similar_categories')->nullable()->after('sort'); // array of category IDs

            // ── SEO ────────────────────────────────────────────────────────
            $table->json('meta_title')->nullable()->after('similar_categories');       // translatable
            $table->json('meta_description')->nullable()->after('meta_title');         // translatable
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'parent_id',
                'slug',
                'short_description',
                'description',
                'featured_image',
                'is_active',
                'is_hidden_in_store',
                'is_hidden_in_menu',
                'is_featured',
                'featured_sort',
                'sort',
                'similar_categories',
                'meta_title',
                'meta_description',
            ]);
        });
    }
};
