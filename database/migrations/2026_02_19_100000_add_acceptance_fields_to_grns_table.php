<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->enum('acceptance_status', [
                'pending',
                'accepted',
                'partially_accepted',
                'rejected',
            ])->default('pending')->after('status');

            $table->unsignedBigInteger('accepted_by')->nullable()->after('acceptance_status');
            $table->timestamp('accepted_at')->nullable()->after('accepted_by');
            $table->text('acceptance_notes')->nullable()->after('accepted_at');
            $table->string('completion_certificate_path')->nullable()->after('acceptance_notes');

            $table->foreign('accepted_by')->references('id')->on('users')->onDelete('set null');
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
