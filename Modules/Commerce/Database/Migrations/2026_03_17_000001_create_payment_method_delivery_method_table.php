<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_method_delivery_method', function (Blueprint $table) {
            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->cascadeOnDelete();
            $table->foreignId('delivery_method_id')
                ->constrained('delivery_methods')
                ->cascadeOnDelete();
            $table->primary(['payment_method_id', 'delivery_method_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_method_delivery_method');
    }
};
