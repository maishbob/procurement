// This is the active migration for annual_procurement_plans table.
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
        Schema::create('annual_procurement_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_number')->unique();
            $table->string('fiscal_year');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('prepared_by')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comments')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_comments')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_comments')->nullable();
            $table->decimal('total_budget', 18, 2)->nullable();
            $table->decimal('allocated_amount', 18, 2)->nullable();
            $table->decimal('spent_amount', 18, 2)->nullable();
            $table->decimal('committed_amount', 18, 2)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_procurement_plans');
    }
};
