<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variables', function (Blueprint $table) {
            $table->id();
            $table->string('group', 100)->index();      // logical namespace, e.g. "seo", "homepage", "shipping"
            $table->string('key', 100)->index();         // variable name within the group, e.g. "title", "phone"
            $table->text('value')->nullable();           // raw value (string / JSON string)
            $table->boolean('is_json')->default(false);  // treat value as JSON when reading
            $table->timestamps();

            $table->unique(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variables');
    }
};
