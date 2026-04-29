<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmers')->restrictOnDelete();
            // 1 credit transaction = 1 debt row
            $table->foreignId('transaction_id')->unique()->constrained('transactions')->cascadeOnDelete();
            // Original debt amount = transaction.total_amount (subtotal + interest)
            $table->decimal('original_amount', 14, 2);
            // Decreases as repayments are applied (FIFO)
            $table->decimal('remaining_amount', 14, 2);
            $table->enum('status', ['open', 'partially_paid', 'paid'])->default('open')->index();
            $table->timestamp('issued_at'); // = transaction.completed_at, used for FIFO ordering
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['farmer_id', 'status', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
