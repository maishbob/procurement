<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_received_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_received_notes', 'acceptance_status')) {
                $table->enum('acceptance_status', [
                    'pending',
                    'accepted',
                    'partially_accepted',
                    'rejected',
                ])->default('pending')->after('status');
            }
            if (!Schema::hasColumn('goods_received_notes', 'accepted_by')) {
                $table->unsignedBigInteger('accepted_by')->nullable()->after('acceptance_status');
                $table->foreign('accepted_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('goods_received_notes', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('accepted_by');
            }
            if (!Schema::hasColumn('goods_received_notes', 'acceptance_notes')) {
                $table->text('acceptance_notes')->nullable()->after('accepted_at');
            }
            if (!Schema::hasColumn('goods_received_notes', 'completion_certificate_path')) {
                $table->string('completion_certificate_path')->nullable()->after('acceptance_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->dropForeign(['accepted_by']);
            $table->dropColumn([
                'acceptance_status',
                'accepted_by',
                'accepted_at',
                'acceptance_notes',
                'completion_certificate_path',
            ]);
        });
    }
};
