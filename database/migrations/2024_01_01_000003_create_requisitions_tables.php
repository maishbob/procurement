<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Item Categories (must be first, no dependencies)
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable(); // FK added after table creation
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add foreign key for self-referencing parent_id
        Schema::table('item_categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('item_categories')->onDelete('set null');
        });

        // Catalog Items (depends on item_categories and suppliers)
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('specifications')->nullable();
            $table->unsignedBigInteger('category_id')->nullable(); // FK added later
            $table->string('unit_of_measure', 20);
            $table->decimal('standard_price', 15, 2)->nullable();
            $table->string('currency', 3)->default('KES');
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_vatable')->default(true);
            $table->string('vat_type', 20)->default('vatable');
            $table->boolean('subject_to_wht')->default(false);
            $table->string('wht_type')->nullable();
            $table->unsignedBigInteger('preferred_supplier_id')->nullable(); // FK added later
            $table->integer('lead_time_days')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'category_id']);
        });

        // Requisitions
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('requisition_number', 50)->unique();
            $table->unsignedBigInteger('department_id'); // FK added later
            $table->unsignedBigInteger('cost_center_id')->nullable(); // FK added later
            $table->unsignedBigInteger('budget_line_id')->nullable(); // FK added later
            $table->unsignedBigInteger('requested_by'); // FK added later

            // Request Details
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('justification');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('type', ['goods', 'services', 'works', 'consultancy'])->default('goods');
            $table->date('required_by_date');
            $table->string('delivery_location')->nullable();

            // Financial
            $table->decimal('estimated_total', 15, 2);
            $table->string('currency', 3)->default('KES');
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->decimal('estimated_total_base', 15, 2)->nullable(); // In KES

            // Workflow Status
            $table->enum('status', [
                'draft',
                'submitted',
                'hod_review',
                'hod_approved',
                'budget_review',
                'budget_approved',
                'procurement_queue',
                'sourcing',
                'quoted',
                'evaluated',
                'awarded',
                'po_created',
                'completed',
                'rejected',
                'cancelled'
            ])->default('draft');

            // Approval flags
            $table->boolean('requires_hod_approval')->default(false);
            $table->boolean('requires_principal_approval')->default(false);
            $table->boolean('requires_board_approval')->default(false);
            $table->boolean('requires_tender')->default(false);

            // Emergency/Special Procurement
            $table->boolean('is_emergency')->default(false);
            $table->text('emergency_justification')->nullable();
            $table->boolean('is_single_source')->default(false);
            $table->text('single_source_justification')->nullable();
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('suppliers');

            // Attachments
            $table->json('attachments')->nullable();

            // Dates
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Metadata
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // FK added later
            $table->unsignedBigInteger('updated_by')->nullable(); // FK added later
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'department_id']);
            $table->index('requested_by');
            $table->index('required_by_date');
            $table->index(['status', 'created_at']);
        });

        // Requisition Items
        Schema::create('requisition_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requisition_id'); // FK added later
            $table->integer('line_number');

            // Item Details
            $table->string('item_code')->nullable();
            $table->string('description');
            $table->text('specifications')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('unit_of_measure', 20);
            $table->decimal('estimated_unit_price', 15, 2);
            $table->decimal('estimated_total_price', 15, 2);

            // Tax
            $table->boolean('is_vatable')->default(true);
            $table->string('vat_type', 20)->default('vatable'); // vatable, exempt, zero-rated
            $table->boolean('subject_to_wht')->default(false);
            $table->string('wht_type')->nullable();

            // Linked to catalog item (if exists)
            $table->unsignedBigInteger('catalog_item_id')->nullable(); // FK added later

            // Status tracking
            $table->enum('status', ['pending', 'approved', 'sourcing', 'ordered', 'received', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('purchase_order_id')->nullable(); // FK added in purchase_orders migration

            $table->timestamps();

            $table->index(['requisition_id', 'line_number']);
            $table->index('status');
        });

        // Requisition Approvals - moved here from later in the file
        Schema::create('requisition_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requisition_id'); // FK added later
            $table->string('approval_level', 50); // hod,principal, budget_owner, board
            $table->integer('sequence');
            $table->unsignedBigInteger('approver_id'); // FK added later
            $table->enum('status', ['pending', 'approved', 'rejected', 'escalated'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['requisition_id', 'status']);
            $table->index(['approver_id', 'status']);
        });

        // Add all foreign key constraints
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('item_categories')->onDelete('set null');
            $table->foreign('preferred_supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });

        Schema::table('requisitions', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers')->onDelete('set null');
            $table->foreign('budget_line_id')->references('id')->on('budget_lines')->onDelete('set null');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('requisition_items', function (Blueprint $table) {
            $table->foreign('requisition_id')->references('id')->on('requisitions')->onDelete('cascade');
            $table->foreign('catalog_item_id')->references('id')->on('catalog_items')->onDelete('set null');
        });

        Schema::table('requisition_approvals', function (Blueprint $table) {
            $table->foreign('requisition_id')->references('id')->on('requisitions')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_approvals');
        Schema::dropIfExists('requisition_items');
        Schema::dropIfExists('requisitions');
        Schema::dropIfExists('catalog_items');

        // Drop self-referencing foreign key before dropping item_categories
        Schema::table('item_categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });

        Schema::dropIfExists('item_categories');
    }
};
