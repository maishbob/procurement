<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_invoices', 'supplier_invoice_id')) {
                $table->foreignId('supplier_invoice_id')->nullable()->constrained('supplier_invoices')->nullOnDelete()->after('payment_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('payment_invoices', 'supplier_invoice_id')) {
                $table->dropForeign(['supplier_invoice_id']);
                $table->dropColumn('supplier_invoice_id');
            }
        });
    }
};
