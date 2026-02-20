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
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected'])->default('draft')->after('is_active');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->unsignedBigInteger('submitted_by')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_by');
            $table->timestamp('submitted_at')->nullable()->after('approved_by');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->dropColumn(['status', 'rejection_reason', 'submitted_by', 'approved_by', 'submitted_at', 'approved_at', 'rejected_at']);
        });
    }
};
