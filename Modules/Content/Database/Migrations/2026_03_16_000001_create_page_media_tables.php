<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Page Images ───────────────────────────────────────────────────
        Schema::create('page_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('path');                         // storage path
            $table->json('alt')->nullable();                // translatable alt text
            $table->json('caption')->nullable();            // translatable caption
            $table->boolean('is_cover')->default(false);    // hero / cover image flag
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // ── Page Attachments ──────────────────────────────────────────────
        Schema::create('page_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('path');                         // storage path
            $table->json('label')->nullable();              // translatable display name
            $table->string('mime_type')->nullable();        // e.g. application/pdf
            $table->unsignedBigInteger('size')->nullable(); // bytes
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_attachments');
        Schema::dropIfExists('page_images');
    }
};
