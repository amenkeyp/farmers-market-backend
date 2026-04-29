<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_name'); // snapshot
            $table->string('unit');
            $table->decimal('quantity', 14, 3);
            // Snapshot of unit price at time of sale
            $table->decimal('unit_price', 14, 2);
            // For commodity (kg) priced items: snapshot of rate_per_kg used
            $table->decimal('rate_per_kg', 14, 4)->nullable();
            // line_total = quantity * unit_price (for non-commodity) OR quantity * rate_per_kg (for commodity)
            $table->decimal('line_total', 14, 2);
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
