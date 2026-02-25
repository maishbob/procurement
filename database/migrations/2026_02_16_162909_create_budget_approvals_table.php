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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('budget_approvals');
        Schema::enableForeignKeyConstraints();

        Schema::create('budget_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_line_id');
            $table->unsignedBigInteger('approver_id');
            $table->enum('action', ['submitted', 'reviewed', 'approved', 'rejected']);
            $table->enum('approver_role', ['hod', 'finance', 'principal', 'board']);
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->foreign('budget_line_id')->references('id')->on('budget_lines')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_approvals');
    }
};
