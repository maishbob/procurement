<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Inventory Stores/Locations
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['main_store', 'department_store', 'warehouse', 'office'])->default('main_store');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->foreignId('store_keeper_id')->nullable()->constrained('users');
            $table->string('location');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        // Inventory Items (Master)
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('item_categories');
            $table->string('unit_of_measure', 20);
            $table->string('alternative_uom', 20)->nullable();
            $table->decimal('uom_conversion_factor', 10, 4)->nullable();

            // Inventory Control
            $table->boolean('track_inventory')->default(true);
            $table->boolean('track_serial_numbers')->default(false);
            $table->boolean('track_batch_numbers')->default(false);
            $table->boolean('is_asset')->default(false);

            // Stock Levels
            $table->decimal('minimum_stock_level', 15, 3)->nullable();
            $table->decimal('maximum_stock_level', 15, 3)->nullable();
            $table->decimal('reorder_point', 15, 3)->nullable();
            $table->decimal('reorder_quantity', 15, 3)->nullable();
            $table->integer('lead_time_days')->nullable();

            // Pricing & Valuation
            $table->decimal('standard_cost', 15, 2)->nullable();
            $table->decimal('average_cost', 15, 2)->nullable();
            $table->decimal('last_purchase_price', 15, 2)->nullable();
            $table->string('valuation_method', 20)->default('FIFO'); // FIFO, LIFO, Average

            // Tax
            $table->boolean('is_vatable')->default(true);
            $table->string('vat_type', 20)->default('vatable');

            // Preferred Supplier
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('suppliers');

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_obsolete')->default(false);
            $table->date('obsolete_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'category_id']);
            $table->index('is_obsolete');
        });

        // Stock Levels (per store)
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->foreignId('store_id')->constrained('stores');
            $table->decimal('quantity_on_hand', 15, 3)->default(0);
            $table->decimal('quantity_allocated', 15, 3)->default(0);
            $table->decimal('quantity_available', 15, 3)->default(0);
            $table->decimal('quantity_on_order', 15, 3)->default(0);
            $table->decimal('value', 15, 2)->default(0);
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamp('last_count_at')->nullable();
            $table->timestamps();

            $table->unique(['inventory_item_id', 'store_id']);
            $table->index('last_movement_at');
        });

        // Stock Transactions
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->foreignId('store_id')->constrained('stores');
            $table->enum('transaction_type', [
                'receipt',          // From GRN
                'issue',            // To department
                'adjustment',       // Stock correction
                'transfer_out',     // To another store
                'transfer_in',      // From another store
                'return',           // Return from department
                'disposal',         // Write-off/disposal
                'cycle_count'       // Physical count adjustment
            ]);

            // Quantities
            $table->decimal('quantity', 15, 3);
            $table->string('unit_of_measure', 20);
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('total_value', 15, 2)->nullable();

            // References
            $table->string('reference_type')->nullable(); // grn, issue, adjustment
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable();

            // Transfer Details
            $table->foreignId('to_store_id')->nullable()->constrained('stores');
            $table->foreignId('from_store_id')->nullable()->constrained('stores');

            // Issue/Return Details
            $table->foreignId('issued_to_user_id')->nullable()->constrained('users');
            $table->foreignId('issued_to_department_id')->nullable()->constrained('departments');
            $table->date('expected_return_date')->nullable();

            // Approval (for adjustments)
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            // Batch/Serial Tracking
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expiry_date')->nullable();

            // Status
            $table->enum('status', ['draft', 'pending_approval', 'posted', 'cancelled'])->default('posted');
            $table->text('notes')->nullable();
            $table->text('justification')->nullable(); // For adjustments

            // Metadata
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('transaction_date');
            $table->timestamps();

            $table->index(['inventory_item_id', 'store_id', 'transaction_date'], 'idx_trans_date');
            $table->index(['transaction_type', 'status'], 'idx_trans_status');
            $table->index('reference_type');
        });

        // Stock Issues (Formal issue to departments)
        Schema::create('stock_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number', 50)->unique();
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('issued_by')->constrained('users');
            $table->date('issue_date');
            $table->text('purpose')->nullable();
            $table->enum('status', ['draft', 'approved', 'issued', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'issue_date']);
            $table->index('status');
        });

        // Stock Issue Items
        Schema::create('stock_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_issue_id')->constrained('stock_issues')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->integer('line_number');
            $table->decimal('quantity', 15, 3);
            $table->string('unit_of_measure', 20);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_value', 15, 2);
            $table->boolean('returnable')->default(false);
            $table->date('expected_return_date')->nullable();
            $table->timestamps();

            $table->index('stock_issue_id');
        });

        // Stock Adjustments (For corrections/cycle counts)
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number', 50)->unique();
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->enum('adjustment_type', ['cycle_count', 'damage', 'loss', 'found', 'correction', 'disposal']);
            $table->decimal('current_quantity', 15, 3);
            $table->decimal('counted_quantity', 15, 3);
            $table->decimal('variance_quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('variance_value', 15, 2);
            $table->text('reason');
            $table->text('justification')->nullable();
            $table->date('adjustment_date');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'posted', 'rejected'])->default('draft');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'adjustment_date']);
            $table->index('status');
        });

        // Asset Register (For capital items)
        Schema::create('asset_register', function (Blueprint $table) {
            $table->id();
            $table->string('asset_number', 50)->unique();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items');
            $table->foreignId('grn_id')->nullable()->constrained('goods_received_notes');
            $table->string('asset_name');
            $table->text('description')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('model_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 15, 2);
            $table->foreignId('current_location_store_id')->nullable()->constrained('stores');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users');
            $table->foreignId('assigned_to_department_id')->nullable()->constrained('departments');
            $table->enum('status', ['in_use', 'available', 'maintenance', 'disposed', 'lost', 'stolen'])->default('available');
            $table->date('disposal_date')->nullable();
            $table->text('disposal_reason')->nullable();
            $table->foreignId('custodian_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'assigned_to_department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_register');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_issue_items');
        Schema::dropIfExists('stock_issues');
        Schema::dropIfExists('stock_transactions');
        Schema::dropIfExists('stock_levels');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('stores');
    }
};
