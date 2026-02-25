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
        Schema::table('procurement_processes', function (Blueprint $table) {
            if (!Schema::hasColumn('procurement_processes', 'procurement_method')) {
                $table->string('procurement_method')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurement_processes', function (Blueprint $table) {
            if (Schema::hasColumn('procurement_processes', 'procurement_method')) {
                $table->dropColumn('procurement_method');
            }
        });
    }
};
