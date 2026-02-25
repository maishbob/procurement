<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'asl_status')) {
                $table->enum('asl_status', [
                    'not_applied',
                    'pending_review',
                    'approved',
                    'suspended',
                    'removed',
                ])->default('not_applied')->after('is_blacklisted');
            }
            if (!Schema::hasColumn('suppliers', 'asl_approved_at')) {
                $table->timestamp('asl_approved_at')->nullable()->after('asl_status');
            }
            if (!Schema::hasColumn('suppliers', 'asl_approved_by')) {
                $table->foreignId('asl_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('asl_approved_at');
            }
            if (!Schema::hasColumn('suppliers', 'asl_review_due_at')) {
                $table->date('asl_review_due_at')->nullable()->after('asl_approved_by');
            }
            if (!Schema::hasColumn('suppliers', 'asl_categories')) {
                $table->json('asl_categories')->nullable()->after('asl_review_due_at');
            }
            if (!Schema::hasColumn('suppliers', 'onboarding_status')) {
                $table->enum('onboarding_status', [
                    'incomplete',
                    'under_review',
                    'approved',
                    'expired',
                ])->default('incomplete')->after('asl_categories');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['asl_approved_by']);
            $table->dropColumn([
                'asl_status',
                'asl_approved_at',
                'asl_approved_by',
                'asl_review_due_at',
                'asl_categories',
                'onboarding_status',
            ]);
        });
    }
};
