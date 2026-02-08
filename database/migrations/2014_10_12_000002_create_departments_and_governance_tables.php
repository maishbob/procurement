<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Departments (users table already created in 2014_10_12_000000)
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('head_of_department_id')->nullable(); // FK added below after table creation
            $table->unsignedBigInteger('parent_department_id')->nullable(); // FK added below after table creation
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });

        // Add foreign key constraints after departments table is created
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('head_of_department_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('parent_department_id')->references('id')->on('departments')->onDelete('set null');
        });

        // Add foreign key constraint to users.department_id
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });

        // Cost Centers
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('budget_owner_id')->nullable()->constrained('users');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['department_id', 'is_active']);
        });

        // Budget Lines
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->string('budget_code', 50)->unique();
            $table->string('fiscal_year', 10);
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->foreignId('department_id')->constrained('departments');
            $table->string('category', 100);
            $table->text('description')->nullable();
            $table->decimal('allocated_amount', 15, 2);
            $table->decimal('committed_amount', 15, 2)->default(0);
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->decimal('available_amount', 15, 2);
            $table->string('currency', 3)->default('KES');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['fiscal_year', 'is_active']);
            $table->index(['department_id', 'fiscal_year']);
        });

        // Approval Hierarchies
        Schema::create('approval_hierarchies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments');
            $table->string('approval_type', 50); // requisition, po, payment
            $table->integer('level'); // 1, 2, 3
            $table->string('level_name', 50); // HOD, Principal, Board
            $table->decimal('min_amount', 15, 2)->nullable();
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->foreignId('approver_role_id')->constrained('roles');
            $table->integer('sequence');
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['department_id', 'approval_type', 'is_active']);
        });

        // Immutable Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('user_name');
            $table->string('user_email');
            $table->string('action', 50);
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('justification')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
            $table->index('action');
        });

        // Audit Logs Archive (for old records)
        Schema::create('audit_logs_archive', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('user_name');
            $table->string('user_email');
            $table->string('action', 50);
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('justification')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('archived_at');

            $table->index('created_at');
            $table->index('archived_at');
        });

        // State Transitions Log
        Schema::create('state_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('transitionable_type');
            $table->unsignedBigInteger('transitionable_id');
            $table->string('workflow', 50);
            $table->string('from_state', 50);
            $table->string('to_state', 50);
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->text('justification')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['transitionable_type', 'transitionable_id']);
            $table->index('created_at');
        });

        // Exchange Rates (Kenya context - KES base)
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 15, 6);
            $table->date('effective_date');
            $table->string('source', 50)->default('manual'); // manual, CBK, API
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency', 'effective_date']);
            $table->index('effective_date');
        });

        // Locked Exchange Rates (for transactions)
        Schema::create('locked_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type', 50);
            $table->unsignedBigInteger('transaction_id');
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('locked_rate', 15, 6);
            $table->timestamp('locked_at');
            $table->timestamps();

            $table->index(['transaction_type', 'transaction_id']);
        });

        // Jobs queue table
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // Failed jobs
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('locked_exchange_rates');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('state_transitions');
        Schema::dropIfExists('audit_logs_archive');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('approval_hierarchies');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('cost_centers');

        // Remove foreign key constraints before dropping departments
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });

        // Drop departments table (which will cascade drop other constraints)
        Schema::dropIfExists('departments');
        // Note: users table is dropped by 2014_10_12_000000 migration
    }
};
