<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Purchase Orders
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('requisition_id')->nullable()->constrained('requisitions');
            $table->foreignId('procurement_process_id')->nullable()->constrained('procurement_processes');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('supplier_bid_id')->nullable()->constrained('supplier_bids');

            // PO Details
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('po_date');
            $table->date('delivery_date');
            $table->string('delivery_location');
            $table->text('delivery_instructions')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->integer('payment_terms_days')->default(30);

            // Financial
            $table->decimal('subtotal', 15, 2);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('KES');
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->decimal('total_amount_base', 15, 2)->nullable(); // In KES

            // Status Workflow
            $table->enum('status', [
                'draft',
                'submitted',
                'approved',
                'rejected',
                'issued',
                'acknowledged',
                'partially_received',
                'fully_received',
                'invoiced',
                'payment_approved',
                'paid',
                'closed',
                'cancelled'
            ])->default('draft');

            // Receiving Status
            $table->decimal('total_received_quantity', 15, 3)->default(0);
            $table->decimal('total_received_value', 15, 2)->default(0);
            $table->integer('grn_count')->default(0);

            // Approval & Authorization
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            // Closure
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->text('closure_notes')->nullable();

            // Contact Information
            $table->foreignId('buyer_id')->constrained('users'); // Procurement officer
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('department_id')->constrained('departments');

            // Attachments
            $table->json('attachments')->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'supplier_id']);
            $table->index(['status', 'po_date']);
            $table->index('delivery_date');
        });

        // Purchase Order Items
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('requisition_item_id')->nullable()->constrained('requisition_items');
            $table->integer('line_number');

            // Item Details
            $table->string('item_code')->nullable();
            $table->string('description');
            $table->text('specifications')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('unit_of_measure', 20);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);

            // Tax
            $table->boolean('is_vatable')->default(true);
            $table->string('vat_type', 20)->default('vatable');
            $table->decimal('vat_rate', 5, 2)->default(16);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_including_vat', 15, 2);

            // Receiving Status
            $table->decimal('received_quantity', 15, 3)->default(0);
            $table->decimal('pending_quantity', 15, 3);
            $table->decimal('rejected_quantity', 15, 3)->default(0);
            $table->enum('receiving_status', ['pending', 'partial', 'complete', 'over_received'])->default('pending');

            // Inventory Linkage
            $table->unsignedBigInteger('inventory_item_id')->nullable(); // FK added after inventory table creation

            $table->timestamps();

            $table->index(['purchase_order_id', 'line_number']);
            $table->index('receiving_status');
        });

        // Goods Received Notes (GRN)
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 50)->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('received_by')->constrained('users');

            // Receipt Details
            $table->date('receipt_date');
            $table->time('receipt_time')->nullable();
            $table->string('delivery_note_number')->nullable();
            $table->string('vehicle_registration')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone', 20)->nullable();
            $table->string('received_at_location');

            // Status Workflow
            $table->enum('status', [
                'draft',
                'submitted',
                'inspection_pending',
                'inspection_passed',
                'inspection_failed',
                'partial_acceptance',
                'approved',
                'rejected',
                'posted',
                'completed',
                'cancelled'
            ])->default('draft');

            // Inspection
            $table->foreignId('inspected_by')->nullable()->constrained('users');
            $table->timestamp('inspected_at')->nullable();
            $table->text('inspection_notes')->nullable();
            $table->text('inspection_findings')->nullable();
            $table->json('inspection_checklist')->nullable();

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            // Posting to Inventory
            $table->boolean('posted_to_inventory')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users');

            // Financial
            $table->decimal('total_value', 15, 2)->default(0);

            // Quality Issues
            $table->boolean('has_discrepancies')->default(false);
            $table->text('discrepancy_details')->nullable();
            $table->boolean('has_quality_issues')->default(false);
            $table->text('quality_issue_details')->nullable();

            // Attachments
            $table->json('attachments')->nullable(); // Photos, delivery notes

            // Metadata
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['purchase_order_id', 'status']);
            $table->index('receipt_date');
        });

        // GRN Items
        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_received_notes')->onDelete('cascade');
            $table->foreignId('po_item_id')->constrained('purchase_order_items');
            $table->integer('line_number');

            // Ordered vs Received
            $table->decimal('ordered_quantity', 15, 3);
            $table->decimal('received_quantity', 15, 3);
            $table->decimal('accepted_quantity', 15, 3);
            $table->decimal('rejected_quantity', 15, 3)->default(0);
            $table->string('unit_of_measure', 20);

            // Quality Check
            $table->enum('quality_status', ['acceptable', 'marginal', 'rejected', 'quarantine'])->default('acceptable');
            $table->text('rejection_reason')->nullable();
            $table->text('quality_notes')->nullable();

            // Pricing (from PO)
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_value', 15, 2);

            // Variances
            $table->decimal('quantity_variance', 15, 3)->default(0);
            $table->decimal('value_variance', 15, 2)->default(0);

            $table->timestamps();

            $table->index('grn_id');
            $table->index('po_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
        Schema::dropIfExists('goods_received_notes');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
