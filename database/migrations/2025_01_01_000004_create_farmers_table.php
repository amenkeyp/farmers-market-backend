<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->unique(); // National ID / Farm ID
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable()->index();
            $table->string('village')->nullable();
            $table->string('region')->nullable();
            $table->decimal('credit_limit', 14, 2)->default(0);
            // Denormalized running balance updated within transactions for fast checks. Source of truth = sum(debts.remaining_amount)
            $table->decimal('current_debt', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmers');
    }
};
