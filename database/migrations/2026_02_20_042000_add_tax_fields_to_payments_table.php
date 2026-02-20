<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'wht_rate')) {
                $table->decimal('wht_rate', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('payments', 'wht_amount')) {
                $table->decimal('wht_amount', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('payments', 'gross_amount')) {
                $table->decimal('gross_amount', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('payments', 'net_amount')) {
                $table->decimal('net_amount', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('payments', 'withholding_tax_amount')) {
                $table->decimal('withholding_tax_amount', 15, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'wht_rate')) {
                $table->dropColumn('wht_rate');
            }
            if (Schema::hasColumn('payments', 'wht_amount')) {
                $table->dropColumn('wht_amount');
            }
            if (Schema::hasColumn('payments', 'gross_amount')) {
                $table->dropColumn('gross_amount');
            }
            if (Schema::hasColumn('payments', 'net_amount')) {
                $table->dropColumn('net_amount');
            }
            if (Schema::hasColumn('payments', 'withholding_tax_amount')) {
                $table->dropColumn('withholding_tax_amount');
            }
        });
    }
};
