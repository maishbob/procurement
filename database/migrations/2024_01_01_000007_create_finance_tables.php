<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supplier Invoices (Kenya Compliance-Aware)
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders');
            $table->foreignId('grn_id')->nullable()->constrained('goods_received_notes');

            // Invoice Details
            $table->string('supplier_invoice_number');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->text('description')->nullable();

            // KRA / eTIMS Compliance Fields
            $table->string('supplier_kra_pin', 11);
            $table->string('etims_control_number')->nullable();
            $table->string('etims_invoice_reference')->nullable();
            $table->string('etims_qr_code')->nullable();
            $table->timestamp('etims_verified_at')->nullable();
            $table->boolean('is_etims_compliant')->default(false);

            // Financial Amounts
            $table->decimal('subtotal', 15, 2);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('gross_amount', 15, 2);

            // WHT (Withholding Tax) - Kenya Specific
            $table->boolean('subject_to_wht')->default(false);
            $table->string('wht_type')->nullable(); // services, professional_fees, etc.
            $table->decimal('wht_rate', 5, 2)->nullable();
            $table->decimal('wht_amount', 15, 2)->default(0);
            $table->decimal('net_payable', 15, 2);

            // Currency
            $table->string('currency', 3)->default('KES');
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->decimal('amount_in_base_currency', 15, 2)->nullable();

            // Three-Way Match Status
            $table->boolean('matches_po')->default(false);
            $table->boolean('matches_grn')->default(false);
            $table->boolean('three_way_match_passed')->default(false);
            $table->json('match_variances')->nullable();
            $table->decimal('variance_tolerance_percent', 5, 2)->nullable();

            // Workflow Status
            $table->enum('status', [
                'draft',
                'submitted',
                'verification_pending',
                'verified',
                'match_failed',
                'approval_pending',
                'approved',
                'payment_processing',
                'paid',
                'rejected',
                'cancelled',
                'completed'
            ])->default('draft');

            // Verification
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // Payment
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Attachments
            $table->json('attachments')->nullable(); // Invoice PDF, receipts

            // Metadata
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['supplier_id', 'status']);
            $table->index(['status', 'due_date']);
            $table->index('supplier_kra_pin');
            $table->index('three_way_match_passed');
        });

        // Invoice Items (Line-level detail)
        Schema::create('supplier_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('supplier_invoices')->onDelete('cascade');
            $table->foreignId('po_item_id')->nullable()->constrained('purchase_order_items');
            $table->foreignId('grn_item_id')->nullable()->constrained('grn_items');
            $table->integer('line_number');
            $table->string('description');
            $table->decimal('quantity', 15, 3);
            $table->string('unit_of_measure', 20);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->boolean('is_vatable')->default(true);
            $table->decimal('vat_rate', 5, 2)->default(16);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_including_vat', 15, 2);
            $table->timestamps();

            $table->index('invoice_id');
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->enum('payment_type', ['invoice', 'advance', 'refund', 'other'])->default('invoice');

            // Payment Details
            $table->date('payment_date');
            $table->string('payment_method', 50); // bank_transfer, cheque, mpesa, cash
            $table->string('reference_number')->nullable(); // Cheque number, transaction ref
            $table->text('description')->nullable();

            // Financial Amounts
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('wht_amount', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->string('currency', 3)->default('KES');

            // Bank Details
            $table->string('payment_bank')->nullable();
            $table->string('payment_account')->nullable();
            $table->string('payment_branch')->nullable();

            // Supplier Bank Details (copied at payment time)
            $table->string('supplier_bank_name')->nullable();
            $table->string('supplier_bank_branch')->nullable();
            $table->string('supplier_account_number')->nullable();
            $table->string('supplier_account_name')->nullable();

            // Workflow Status
            $table->enum('status', [
                'draft',
                'submitted',
                'verification_pending',
                'verified',
                'approval_pending',
                'approved',
                'payment_processing',
                'paid',
                'failed',
                'rejected',
                'cancelled'
            ])->default('draft');

            // Verification
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            // Processing
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_notes')->nullable();

            // Failure handling
            $table->text('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);

            // Attachments
            $table->json('attachments')->nullable(); // Payment receipts, bank slips

            // Metadata
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['supplier_id', 'status']);
            $table->index(['status', 'payment_date']);
        });

        // Payment Invoice Mapping (One payment can cover multiple invoices)
        Schema::create('payment_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained('supplier_invoices');
            $table->decimal('amount_allocated', 15, 2);
            $table->timestamps();

            $table->unique(['payment_id', 'invoice_id']);
        });

        // WHT Certificates (For compliance tracking)
        Schema::create('wht_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('tax_period'); // e.g., "January 2024"
            $table->integer('tax_year');
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('wht_amount', 15, 2);
            $table->decimal('wht_rate', 5, 2);
            $table->string('wht_type');
            $table->date('issue_date');
            $table->string('supplier_kra_pin', 11);
            $table->string('school_kra_pin', 11);
            $table->enum('status', ['draft', 'issued', 'submitted_to_kra', 'cancelled'])->default('draft');
            $table->foreignId('issued_by')->nullable()->constrained('users');
            $table->timestamp('issued_at')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'tax_year', 'tax_period']);
            $table->index('status');
        });

        // Payment Approvals
        Schema::create('payment_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->string('approval_level', 50);
            $table->integer('sequence');
            $table->foreignId('approver_id')->constrained('users');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'status']);
        });

        // Notifications Queue
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('type'); // requisition_approved, payment_ready, etc.
            $table->string('channel'); // email, sms, system
            $table->string('subject')->nullable();
            $table->text('message');
            $table->json('data')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // Add deferred foreign key constraints
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
        });

        Schema::table('wht_certificates', function (Blueprint $table) {
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('payment_approvals');
        Schema::dropIfExists('wht_certificates');
        Schema::dropIfExists('payment_invoices');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('supplier_invoice_items');
        Schema::dropIfExists('supplier_invoices');
    }
};
