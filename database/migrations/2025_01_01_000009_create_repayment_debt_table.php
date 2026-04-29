<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('repayment_debt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repayment_id')->constrained('repayments')->cascadeOnDelete();
            $table->foreignId('debt_id')->constrained('debts')->cascadeOnDelete();
            // Amount of this repayment applied to this specific debt
            $table->decimal('amount_applied', 14, 2);
            // Snapshot of debt remaining BEFORE this allocation (audit)
            $table->decimal('debt_remaining_before', 14, 2);
            $table->decimal('debt_remaining_after', 14, 2);
            $table->timestamps();

            $table->unique(['repayment_id', 'debt_id']);
            $table->index('debt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repayment_debt');
    }
};
