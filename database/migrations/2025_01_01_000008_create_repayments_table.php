<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('repayments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // RPY-YYYYMMDD-xxxxx
            $table->foreignId('farmer_id')->constrained('farmers')->restrictOnDelete();
            $table->foreignId('operator_id')->constrained('users')->restrictOnDelete();
            // Amount tendered by farmer
            $table->decimal('amount', 14, 2);
            // Portion that was actually applied to debts (could be < amount if overpayment refunded)
            $table->decimal('applied_amount', 14, 2)->default(0);
            // Overpayment refunded (in FCFA) - we never auto-credit, we refund
            $table->decimal('change_amount', 14, 2)->default(0);
            $table->enum('method', ['cash', 'mobile_money', 'bank'])->default('cash');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->index(['farmer_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repayments');
    }
};
