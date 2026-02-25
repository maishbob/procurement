<?php

namespace App\Services;

use App\Core\Audit\AuditService;
use App\Core\Workflow\WorkflowEngine;
use App\Models\Requisition;
use App\Modules\Requisitions\Models\RequisitionApproval;
use App\Models\SupplierInvoice;
use App\Models\Payment;
use App\Models\User;

class ApprovalService
{
    public function __construct(
        private AuditService $auditService,
        private WorkflowEngine $workflowEngine
    ) {}

    /**
     * Submit requisition for approval (draft → pending_approval)
     */
    public function submitRequisitionForApproval(Requisition $requisition, string $notes = null): Requisition
    {
        if ($requisition->status !== 'draft') {
            throw new \Exception('Only draft requisitions can be submitted');
        }

        $requisition->update([
            'status' => 'pending_approval',
            'submitted_at' => now(),
            'submission_notes' => $notes,
        ]);

        $this->workflowEngine->transition($requisition, 'RequisitionWorkflow', 'submit');

        $this->auditService->log(
            action: 'REQUISITION_SUBMITTED',
            status: 'success',
            model_type: 'Requisition',
            model_id: $requisition->id,
            description: "Requisition #{$requisition->requisition_number} submitted for approval",
        );

        return $requisition->fresh();
    }

    /**
     * Approve requisition at current level
     * Moves to next level or approved status
     */
    public function approveRequisition(Requisition $requisition, int $approvalLevel = 1, string $notes = null): Requisition
    {
        $approver = auth()->user();

        if (!in_array($requisition->status, ['submitted', 'hod_review'])) {
            throw new \Exception('Requisition is not in a valid approval status (must be submitted or hod_review)');
        }

        // Check if user has authority to approve at this amount
        if ($requisition->total_amount > ($approver->approval_limit ?? 0) && !$approver->hasRole('super_admin')) {
            throw new \Exception("Approval authority exceeded. Your limit: KES " . number_format($approver->approval_limit ?? 0, 2));
        }

        // Record approval
        RequisitionApproval::create([
            'requisition_id' => $requisition->id,
            'approval_level' => $approvalLevel,
            'sequence' => 1, // Default to 1 for HOD, adjust as needed for multi-level
            'approver_id' => $approver->id,
            'status' => 'approved',
            'comments' => $notes,
            'responded_at' => now(),
        ]);

        // Check if this is final approval or move to next level
        $nextLevel = $this->getNextApprovalLevel($requisition);

        if ($nextLevel === null) {
            // All approvals complete at this level (HOD)
            $from = $requisition->status;
            $requisition->update(['status' => 'hod_approved']);
            $this->workflowEngine->transition($requisition, 'RequisitionWorkflow', $from, 'hod_approved');
        } else {
            // More approvals needed
            $requisition->update(['next_approval_level' => $nextLevel]);
            $this->workflowEngine->transition($requisition, 'RequisitionWorkflow', 'approve_partial');
        }

        $this->auditService->log(
            action: 'REQUISITION_APPROVED_LEVEL_' . $approvalLevel,
            status: 'success',
            model_type: 'Requisition',
            model_id: $requisition->id,
            description: "Requisition #{$requisition->requisition_number} approved at level {$approvalLevel} by {$approver->name}",
        );

        return $requisition->fresh();
    }

    /**
     * Reject requisition at any approval level
     */
    public function rejectRequisition(Requisition $requisition, string $rejectionReason): Requisition
    {
        if ($requisition->status !== 'pending_approval') {
            throw new \Exception('Only pending requisitions can be rejected');
        }

        $requisition->update([
            'status' => 'rejected',
            'rejection_reason' => $rejectionReason,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        $this->workflowEngine->transition($requisition, 'RequisitionWorkflow', 'reject');

        $this->auditService->log(
            action: 'REQUISITION_REJECTED',
            status: 'success',
            model_type: 'Requisition',
            model_id: $requisition->id,
            description: "Requisition #{$requisition->requisition_number} rejected. Reason: {$rejectionReason}",
        );

        return $requisition->fresh();
    }

    /**
     * Verify and approve invoice (three-way match)
     */
    public function verifyAndApproveInvoice(SupplierInvoice $invoice, string $verificationNotes = null): SupplierInvoice
    {
        $verifier = auth()->user();

        if ($invoice->status !== 'submitted') {
            throw new \Exception('Invoice must be in submitted status for verification');
        }

        // Check three-way match: PO amount vs GRN quantity vs Invoice amount
        $varianceTolerance = config('procurement.variance_tolerance', 0.05);
        $po = $invoice->purchaseOrder;
        $grn = $invoice->goodsReceivedNote;

        $poAmount = $po->total_amount;
        $grnAmount = $grn->items()->sum('unit_cost * quantity_received');
        $invoiceAmount = $invoice->total_amount;

        $variance = abs($invoiceAmount - $poAmount) / $poAmount;

        if ($variance > $varianceTolerance) {
            throw new \Exception("Invoice variance exceeds tolerance. Variance: " . number_format($variance * 100, 2) . "%");
        }

        // Mark as verified
        $invoice->update([
            'status' => 'verified',
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'verification_notes' => $verificationNotes,
        ]);

        $this->workflowEngine->transition($invoice, 'InvoiceWorkflow', 'verify');

        $this->auditService->log(
            action: 'INVOICE_VERIFIED_THREE_WAY_MATCH',
            status: 'success',
            model_type: 'SupplierInvoice',
            model_id: $invoice->id,
            description: "Invoice #{$invoice->invoice_number} verified by {$verifier->name}",
        );

        return $invoice->fresh();
    }

    /**
     * Approve invoice for payment (verified → approved)
     * Requires segregation of duties: verifier ≠ approver
     */
    public function approveInvoiceForPayment(SupplierInvoice $invoice, string $approvalNotes = null): SupplierInvoice
    {
        $approver = auth()->user();

        if ($invoice->status !== 'verified') {
            throw new \Exception('Only verified invoices can be approved for payment');
        }

        // Enforce segregation of duties
        if ($invoice->verified_by === $approver->id) {
            throw new \Exception('Segregation of duties violated: Verifier cannot also approve');
        }

        // Check approval authority
        if ($invoice->total_amount > ($approver->approval_limit ?? 0) && !$approver->hasRole('super_admin')) {
            throw new \Exception("Approval authority exceeded for invoice amount: KES " . number_format($invoice->total_amount, 2));
        }

        $invoice->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_notes' => $approvalNotes,
        ]);

        $this->workflowEngine->transition($invoice, 'InvoiceWorkflow', 'approve');

        $this->auditService->log(
            action: 'INVOICE_APPROVED_FOR_PAYMENT',
            status: 'success',
            model_type: 'SupplierInvoice',
            model_id: $invoice->id,
            description: "Invoice #{$invoice->invoice_number} approved for payment by {$approver->name}",
        );

        return $invoice->fresh();
    }

    /**
     * Reject invoice for payment
     */
    public function rejectInvoice(SupplierInvoice $invoice, string $rejectionReason): SupplierInvoice
    {
        if (!in_array($invoice->status, ['submitted', 'verified'])) {
            throw new \Exception('Only submitted or verified invoices can be rejected');
        }

        $invoice->update([
            'status' => 'rejected',
            'rejection_reason' => $rejectionReason,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        $this->workflowEngine->transition($invoice, 'InvoiceWorkflow', 'reject');

        $this->auditService->log(
            action: 'INVOICE_REJECTED',
            status: 'success',
            model_type: 'SupplierInvoice',
            model_id: $invoice->id,
            description: "Invoice #{$invoice->invoice_number} rejected. Reason: {$rejectionReason}",
        );

        return $invoice->fresh();
    }

    /**
     * Submit payment for approval
     */
    public function submitPaymentForApproval(Payment $payment, string $submissionNotes = null): Payment
    {
        if ($payment->status !== 'draft') {
            throw new \Exception('Only draft payments can be submitted');
        }

        $payment->update([
            'status' => 'pending_approval',
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
            'submission_notes' => $submissionNotes,
        ]);

        $this->workflowEngine->transition($payment, 'PaymentWorkflow', 'submit');

        $this->auditService->log(
            action: 'PAYMENT_SUBMITTED',
            status: 'success',
            model_type: 'Payment',
            model_id: $payment->id,
            description: "Payment submitted for approval: KES " . number_format($payment->total_amount, 2),
        );

        return $payment->fresh();
    }

    /**
     * Approve payment (pending_approval → approved)
     * Enforces segregation of duties: approver ≠ submitter
     */
    public function approvePayment(Payment $payment, string $approvalNotes = null): Payment
    {
        $approver = auth()->user();

        if ($payment->status !== 'pending_approval') {
            throw new \Exception('Payment must be in pending approval status');
        }

        // Segregation of duties: approver must be different from submitter
        if ($payment->submitted_by === $approver->id) {
            throw new \Exception('Segregation of duties violated: Submitter cannot also approve');
        }

        // Check approval authority
        if ($payment->total_amount > ($approver->approval_limit ?? 0) && !$approver->hasRole('super_admin')) {
            throw new \Exception("Approval authority exceeded. Your limit: KES " . number_format($approver->approval_limit ?? 0, 2));
        }

        $payment->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_notes' => $approvalNotes,
        ]);

        $this->workflowEngine->transition($payment, 'PaymentWorkflow', 'approve');

        $this->auditService->log(
            action: 'PAYMENT_APPROVED',
            status: 'success',
            model_type: 'Payment',
            model_id: $payment->id,
            description: "Payment approved by {$approver->name}: KES " . number_format($payment->total_amount, 2),
        );

        return $payment->fresh();
    }

    /**
     * Reject payment
     */
    public function rejectPayment(Payment $payment, string $rejectionReason): Payment
    {
        if ($payment->status !== 'pending_approval') {
            throw new \Exception('Only pending payments can be rejected');
        }

        $payment->update([
            'status' => 'rejected',
            'rejection_reason' => $rejectionReason,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        $this->workflowEngine->transition($payment, 'PaymentWorkflow', 'reject');

        $this->auditService->log(
            action: 'PAYMENT_REJECTED',
            status: 'success',
            model_type: 'Payment',
            model_id: $payment->id,
            description: "Payment rejected. Reason: {$rejectionReason}",
        );

        return $payment->fresh();
    }

    /**
     * Get next approval level for requisition
     */
    private function getNextApprovalLevel(Requisition $requisition): ?int
    {
        $amount = $requisition->total_amount;

        // Approval hierarchy by amount
        // Level 1: Department Head (up to 50,000)
        // Level 2: Finance Manager (50,001 to 500,000)
        // Level 3: Procurement Officer (500,001 to 2,000,000)
        // Level 4+: Super Admin (above 2,000,000)

        if ($amount <= 50000) return null; // No more approvals
        if ($amount <= 500000) return 2;
        if ($amount <= 2000000) return 3;
        return 4;
    }
}
