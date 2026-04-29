<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('repayments', function (Blueprint $table) {
            $table->decimal('commodity_kg', 14, 3)->nullable()->after('change_amount');
            $table->decimal('commodity_rate', 14, 2)->nullable()->after('commodity_kg');
            $table->string('commodity_name', 120)->nullable()->after('commodity_rate');
        });

        // Expand the method enum to include 'commodity'
        DB::statement("ALTER TABLE repayments MODIFY COLUMN method ENUM('cash', 'commodity', 'mobile_money', 'bank') DEFAULT 'cash'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE repayments MODIFY COLUMN method ENUM('cash', 'mobile_money', 'bank') DEFAULT 'cash'");

        Schema::table('repayments', function (Blueprint $table) {
            $table->dropColumn(['commodity_kg', 'commodity_rate', 'commodity_name']);
        });
    }
};
