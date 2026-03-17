<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // attribute_groups already has is_filter (added by 2025_05_05_120011)
        // we only add the sort column here
        if (! Schema::hasColumn('attribute_groups', 'sort')) {
            Schema::table('attribute_groups', function (Blueprint $table) {
                $table->unsignedInteger('sort')->default(0)->after('is_filter');
            });
        }

        Schema::table('attributes', function (Blueprint $table) {
            if (! Schema::hasColumn('attributes', 'is_filter')) {
                $table->boolean('is_filter')->default(false)->after('title');
            }
            if (! Schema::hasColumn('attributes', 'sort')) {
                $table->unsignedInteger('sort')->default(0)->after('is_filter');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->constrained('shop_brands')->nullOnDelete()->after('category_id');
            $table->json('slug')->nullable()->after('brand_id');           // translatable: {ro: '...', ru: '...'}
            $table->json('subtitle')->nullable()->after('title');           // translatable – quick specs line
            $table->json('short_description')->nullable()->after('subtitle'); // translatable
            $table->decimal('price_eur', 12, 2)->nullable()->after('rrp_old');
            $table->unsignedInteger('sort')->default(0)->after('is_new_until');
            // SEO (Task 3)
            $table->json('meta_title')->nullable()->after('sort');          // translatable
            $table->json('meta_description')->nullable()->after('meta_title'); // translatable
        });

        Schema::table('product_files', function (Blueprint $table) {
            $table->json('title')->nullable()->after('path');               // translatable
            $table->string('file_type', 20)->nullable()->after('title');   // pdf, doc…
            $table->string('file_size', 30)->nullable()->after('file_type'); // e.g. "2.3 Mb"
            $table->string('locale', 10)->default('ro')->after('file_size'); // ro / ru / etc.
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('linked_article');              // linked product article/SKU
            $table->string('color_value', 20)->nullable(); // hex or color code
            $table->json('color_label')->nullable();       // translatable
            $table->string('option')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('product_description_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->json('title')->nullable();             // translatable
            $table->json('content')->nullable();           // translatable rich text
            $table->string('image')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });


    }

    public function down(): void
    {
        Schema::dropIfExists('product_description_sections');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_attribute_values');

        Schema::table('product_files', function (Blueprint $table) {
            $cols = ['title', 'file_type', 'file_size', 'locale'];
            $existing = array_filter($cols, fn($c) => Schema::hasColumn('product_files', $c));
            if ($existing) {
                $table->dropColumn(array_values($existing));
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'brand_id')) {
                $table->dropForeign(['brand_id']);
            }
            $cols = ['brand_id', 'slug', 'subtitle', 'short_description', 'price_eur', 'stock', 'is_active', 'is_new', 'sort', 'meta_title', 'meta_description'];
            $existing = array_filter($cols, fn($c) => Schema::hasColumn('products', $c));
            if ($existing) {
                $table->dropColumn(array_values($existing));
            }
        });

        Schema::table('attributes', function (Blueprint $table) {
            $cols = ['is_filter', 'sort'];
            $existing = array_filter($cols, fn($c) => Schema::hasColumn('attributes', $c));
            if ($existing) {
                $table->dropColumn(array_values($existing));
            }
        });

        // attribute_groups: only drop 'sort' — is_filter is owned by 2025_05_05_120011, image never existed here
        Schema::table('attribute_groups', function (Blueprint $table) {
            if (Schema::hasColumn('attribute_groups', 'sort')) {
                $table->dropColumn(['sort']);
            }
        });
    }
};
