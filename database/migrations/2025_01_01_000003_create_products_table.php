<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('unit')->default('kg'); // kg, sac, litre, unit
            // Price per unit in FCFA. For commodity-priced items, keep 0 and use rate_per_kg
            $table->decimal('unit_price', 14, 2)->default(0);
            // Conversion rate FCFA per kg (used when product is sold by weight as commodity)
            $table->decimal('rate_per_kg', 14, 4)->nullable();
            $table->decimal('stock_quantity', 14, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
