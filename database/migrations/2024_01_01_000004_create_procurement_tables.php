<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Procurement Processes (RFQ/RFP/Tender)
        Schema::create('procurement_processes', function (Blueprint $table) {
            $table->id();
            $table->string('process_number', 50);
            $table->foreignId('requisition_id')->nullable()->constrained('requisitions');
            $table->enum('type', ['rfq', 'rfp', 'tender', 'spot_buy', 'framework_agreement'])->default('rfq');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('issue_date')->nullable();
            $table->timestamp('submission_deadline')->nullable();
            $table->date('evaluation_date')->nullable();
            $table->date('award_date')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->enum('status', ['draft', 'rfq_issued', 'bids_received', 'evaluation', 'evaluation_complete', 'award_recommendation', 'award_approved', 'award_rejected', 'po_generation', 'completed', 'cancelled'])->default('draft');
            $table->json('evaluation_criteria')->nullable();
            $table->foreignId('evaluation_team_lead')->nullable()->constrained('users');
            $table->json('evaluation_team_members')->nullable();
            $table->text('evaluation_notes')->nullable();
            $table->foreignId('recommended_supplier_id')->nullable()->constrained('suppliers');
            $table->text('award_justification')->nullable();
            $table->foreignId('awarded_supplier_id')->nullable()->constrained('suppliers');
            $table->foreignId('awarded_by')->nullable()->constrained('users');
            $table->timestamp('awarded_at')->nullable();
            $table->decimal('estimated_total', 15, 2)->nullable();
            $table->decimal('awarded_amount', 15, 2)->nullable();
            $table->string('currency', 3)->default('KES');
            $table->json('attachments')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'type']);
            $table->index('submission_deadline');
        });

        Schema::table('procurement_processes', function (Blueprint $table) {
            $table->unique('process_number');
        });

        // Procurement Process Items
        Schema::create('procurement_process_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_process_id')->constrained('procurement_processes')->onDelete('cascade');
            $table->foreignId('requisition_item_id')->nullable()->constrained('requisition_items');
            $table->integer('line_number');
            $table->string('description');
            $table->text('specifications')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('unit_of_measure', 20);
            $table->decimal('estimated_unit_price', 15, 2)->nullable();
            $table->timestamps();
            $table->index('procurement_process_id');
        });

        // Invited Suppliers
        Schema::create('procurement_invited_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_process_id')->constrained('procurement_processes')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->timestamp('invited_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->boolean('submitted_bid')->default(false);
            $table->timestamp('bid_submitted_at')->nullable();
            $table->enum('status', ['invited', 'declined', 'submitted', 'late', 'disqualified'])->default('invited');
            $table->timestamps();
        });

        Schema::table('procurement_invited_suppliers', function (Blueprint $table) {
            $table->unique(['procurement_process_id', 'supplier_id'], 'uk_proc_sup');
        });

        // Supplier Bids
        Schema::create('supplier_bids', function (Blueprint $table) {
            $table->id();
            $table->string('bid_number', 50);
            $table->foreignId('procurement_process_id')->constrained('procurement_processes')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->timestamp('submitted_at');
            $table->boolean('is_late')->default(false);
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('KES');
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->decimal('total_amount_base', 15, 2)->nullable();
            $table->integer('delivery_days')->nullable();
            $table->integer('validity_days')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->enum('status', ['submitted', 'under_review', 'qualified', 'disqualified', 'awarded', 'rejected'])->default('submitted');
            $table->decimal('evaluation_score', 5, 2)->nullable();
            $table->json('evaluation_scores')->nullable();
            $table->text('evaluation_comments')->nullable();
            $table->text('disqualification_reason')->nullable();
            $table->boolean('coi_declared')->default(false);
            $table->text('coi_details')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->index(['procurement_process_id', 'supplier_id']);
            $table->index('status');
        });

        Schema::table('supplier_bids', function (Blueprint $table) {
            $table->unique('bid_number');
        });

        // Supplier Bid Items
        Schema::create('supplier_bid_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_bid_id')->constrained('supplier_bids')->onDelete('cascade');
            $table->foreignId('procurement_item_id')->constrained('procurement_process_items');
            $table->integer('line_number');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->boolean('is_vatable')->default(true);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_including_vat', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('supplier_bid_id');
        });

        // Bid Evaluations
        Schema::create('bid_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_bid_id')->constrained('supplier_bids')->onDelete('cascade');
            $table->foreignId('evaluator_id')->constrained('users');
            $table->json('scores');
            $table->decimal('total_score', 5, 2);
            $table->text('comments')->nullable();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->enum('recommendation', ['award', 'reject', 'conditional'])->nullable();
            $table->text('recommendation_notes')->nullable();
            $table->timestamp('evaluated_at');
            $table->timestamps();
        });

        Schema::table('bid_evaluations', function (Blueprint $table) {
            $table->unique(['supplier_bid_id', 'evaluator_id'], 'uk_eval');
        });

        // Conflict of Interest Declarations
        Schema::create('conflict_of_interest_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('declarable_type');
            $table->unsignedBigInteger('declarable_id');
            $table->boolean('has_conflict')->default(false);
            $table->text('conflict_details')->nullable();
            $table->timestamp('declared_at');
            $table->timestamps();
            $table->index(['declarable_type', 'declarable_id'], 'idx_coi_decl');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conflict_of_interest_declarations');
        Schema::dropIfExists('bid_evaluations');
        Schema::dropIfExists('supplier_bid_items');
        Schema::dropIfExists('supplier_bids');
        Schema::dropIfExists('procurement_invited_suppliers');
        Schema::dropIfExists('procurement_process_items');
        Schema::dropIfExists('procurement_processes');
    }
};
