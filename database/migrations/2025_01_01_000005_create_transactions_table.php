<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // TXN-YYYYMMDD-xxxxx
            $table->foreignId('farmer_id')->constrained('farmers')->restrictOnDelete();
            $table->foreignId('operator_id')->constrained('users')->restrictOnDelete();
            $table->enum('type', ['cash', 'credit'])->index();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending')->index();

            // Pure goods value (sum of items.line_total) in FCFA, before interest
            $table->decimal('subtotal', 14, 2)->default(0);
            // Interest rate applied to credit transactions (e.g. 0.05 = 5%). 0 for cash.
            $table->decimal('interest_rate', 8, 4)->default(0);
            // Interest in FCFA = subtotal * interest_rate
            $table->decimal('interest_amount', 14, 2)->default(0);
            // What the farmer ultimately owes / paid: subtotal + interest
            $table->decimal('total_amount', 14, 2)->default(0);
            // For cash transactions: amount paid (cash). For credit: 0 by default
            $table->decimal('paid_amount', 14, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['farmer_id', 'type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
