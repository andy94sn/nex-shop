<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_links', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->index();          // 'social' | 'messenger' | 'payment' | …
            $table->string('name', 100);                  // display name, e.g. "Facebook", "Visa"
            $table->string('url')->nullable();            // destination URL or handle
            $table->string('icon')->nullable();           // image path or icon class
            $table->boolean('status')->default(true);     // active / inactive
            $table->unsignedInteger('sort')->default(0);  // display order
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_links');
    }
};
