<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Departments
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Cost Centers
        if (!Schema::hasTable('cost_centers')) {
            Schema::create('cost_centers', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code')->unique();
                $table->string('cost_element');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Budget Lines
        if (!Schema::hasTable('budget_lines')) {
            Schema::create('budget_lines', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
                $table->foreignId('cost_center_id')->constrained('cost_centers')->onDelete('cascade');
                $table->decimal('annual_budget', 15, 2);
                $table->decimal('available_budget', 15, 2);
                $table->integer('fiscal_year');
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Budget Transactions
        if (!Schema::hasTable('budget_transactions')) {
            Schema::create('budget_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('budget_line_id')->constrained('budget_lines')->onDelete('cascade');
                $table->enum('transaction_type', ['allocation', 'commitment', 'encumbrance', 'expenditure', 'reversal']);
                $table->decimal('amount', 15, 2);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('description')->nullable();
                $table->timestamp('transaction_date');
                $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('budget_transactions');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('departments');
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
