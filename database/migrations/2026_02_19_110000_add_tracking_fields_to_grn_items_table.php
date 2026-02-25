<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grn_items', function (Blueprint $table) {
            if (!Schema::hasColumn('grn_items', 'serial_number')) {
                $table->string('serial_number')->nullable();
            }
            if (!Schema::hasColumn('grn_items', 'batch_number')) {
                $table->string('batch_number')->nullable();
            }
            if (!Schema::hasColumn('grn_items', 'expiry_date')) {
                $table->date('expiry_date')->nullable();
            }
            if (!Schema::hasColumn('grn_items', 'storage_location')) {
                $table->string('storage_location')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('grn_items', function (Blueprint $table) {
            $table->dropColumn(['serial_number', 'batch_number', 'expiry_date', 'storage_location']);
        });
    }
};
