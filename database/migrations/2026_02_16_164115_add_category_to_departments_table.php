<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'category')) {
                $table->enum('category', ['Academic', 'Operations'])->default('Operations')->after('name');
            }
        });

        // Update existing departments with appropriate categories
        DB::table('departments')
            ->where('name', 'like', 'Academics%')
            ->update(['category' => 'Academic']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
