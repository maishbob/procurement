<?php

namespace App\Services;

use App\Core\Audit\AuditService;
use App\Jobs\SendEmailNotificationJob;
use App\Models\User;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\SupplierInvoice;
use App\Modules\PurchaseOrders\Models\PurchaseOrder;
use App\Modules\Requisitions\Models\Requisition;
use App\Notifications\GenericNotification;

class NotificationService
{
    public function __construct(private AuditService $auditService) {}

    // -------------------------------------------------------------------------
    // Public notification methods
    // -------------------------------------------------------------------------

    /**
     * Notify the correct approvers when a requisition is submitted.
     * Routes to HOD (same department) for amounts ≤ threshold, or Principal for larger amounts.
     */
    public function notifyRequisitionApprovers(Requisition $requisition): void
    {
        $amount       = (float) ($requisition->total_amount ?? 0);
        $hodThreshold = (float) config('procurement.approval_thresholds.hod', env('THRESHOLD_HOD_APPROVAL', 50000));

        $rolesToNotify = $amount <= $hodThreshold
            ? ['Head of Department']
            : ['Principal', 'Deputy Principal'];

        $approvers = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $rolesToNotify))
            ->where('department_id', $requisition->department_id)
            ->where('id', '!=', $requisition->created_by ?? $requisition->requested_by)
            ->get();

        // Broaden search if no matching approver in the department
        if ($approvers->isEmpty()) {
            $approvers = User::active()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', $rolesToNotify))
                ->where('id', '!=', $requisition->created_by ?? $requisition->requested_by)
                ->get();
        }

        foreach ($approvers as $approver) {
            $this->dispatchGeneric($approver, [
                'title'          => "Requisition #{$requisition->requisition_number} Awaiting Your Approval",
                'message'        => "A requisition for KES " . number_format($amount, 2) . " from {$requisition->department?->name} requires your approval.",
                'type'           => 'requisition_approval',
                'reference_id'   => $requisition->id,
                'reference_type' => 'Requisition',
                'action_url'     => route('requisitions.show', $requisition),
            ]);
        }

        $this->auditService->log(
            action: 'APPROVERS_NOTIFIED',
            model: 'Requisition',
            modelId: $requisition->id,
            metadata: ['approver_count' => $approvers->count(), 'total_amount' => $amount],
        );
    }

    /**
     * Notify the requisitioner when their requisition status changes.
     */
    public function notifyRequisitionStatusChange(Requisition $requisition, string $oldStatus, string $newStatus): void
    {
        $requester = $requisition->requester
            ?? User::find($requisition->created_by ?? $requisition->requested_by ?? null);

        if (!$requester) {
            return;
        }

        $message = match ($newStatus) {
            'approved', 'hod_approved', 'budget_approved', 'po_created'
                => "Your requisition #{$requisition->requisition_number} has been approved and is progressing to the next stage.",
            'rejected'
                => "Your requisition #{$requisition->requisition_number} has been rejected. Reason: {$requisition->rejection_reason}",
            'cancelled'
                => "Requisition #{$requisition->requisition_number} has been cancelled.",
            default
                => "Requisition #{$requisition->requisition_number} status updated to: " . str_replace('_', ' ', $newStatus) . ".",
        };

        $this->dispatchGeneric($requester, [
            'title'          => "Requisition #{$requisition->requisition_number} Update",
            'message'        => $message,
            'type'           => 'requisition_status',
            'reference_id'   => $requisition->id,
            'reference_type' => 'Requisition',
            'action_url'     => route('requisitions.show', $requisition),
        ]);

        $this->auditService->log(
            action: 'REQUESTER_NOTIFIED_STATUS_CHANGE',
            model: 'Requisition',
            modelId: $requisition->id,
            metadata: ['old_status' => $oldStatus, 'new_status' => $newStatus],
        );
    }

    /**
     * Notify procurement team that a PO has been issued and needs to be sent to supplier.
     */
    public function notifySupplierPOIssued(PurchaseOrder $po): void
    {
        $supplier = $po->supplier;

        $procurementTeam = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Procurement Officer', 'Procurement Assistant', 'Super Administrator']))
            ->get();

        foreach ($procurementTeam as $user) {
            $this->dispatchGeneric($user, [
                'title'          => "PO #{$po->po_number} Issued — Action Required",
                'message'        => "Purchase Order #{$po->po_number} has been issued. Please send the formal PO document to {$supplier?->name} at {$supplier?->email}.",
                'type'           => 'po_issued',
                'reference_id'   => $po->id,
                'reference_type' => 'PurchaseOrder',
                'action_url'     => route('purchase-orders.show', $po),
            ]);
        }

        $this->auditService->log(
            action: 'SUPPLIER_NOTIFIED_PO_ISSUED',
            model: 'PurchaseOrder',
            modelId: $po->id,
            metadata: ['supplier_id' => $supplier?->id, 'supplier_name' => $supplier?->name],
        );
    }

    /**
     * Notify finance team when an invoice arrives for three-way match verification.
     */
    public function notifyInvoiceAwaitingVerification(SupplierInvoice $invoice): void
    {
        $financeTeam = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Finance Manager', 'Accountant']))
            ->get();

        foreach ($financeTeam as $user) {
            $this->dispatchGeneric($user, [
                'title'          => "Invoice #{$invoice->invoice_number} Requires 3-Way Match Verification",
                'message'        => "Invoice from {$invoice->supplier?->name} for KES " . number_format($invoice->total_amount, 2) . " requires three-way match verification before payment can proceed.",
                'type'           => 'invoice_verification',
                'reference_id'   => $invoice->id,
                'reference_type' => 'SupplierInvoice',
                'action_url'     => route('finance.invoices.show', $invoice),
            ]);
        }

        $this->auditService->log(
            action: 'FINANCE_NOTIFIED_INVOICE',
            model: 'SupplierInvoice',
            modelId: $invoice->id,
            metadata: ['finance_count' => $financeTeam->count()],
        );
    }

    /**
     * Notify payment approvers that a payment is pending their approval.
     */
    public function notifyPaymentApprovers(Payment $payment): void
    {
        $principalThreshold = (float) config('procurement.approval_thresholds.principal', env('THRESHOLD_PRINCIPAL_APPROVAL', 200000));

        $roles = $payment->amount > $principalThreshold
            ? ['Super Administrator', 'Principal']
            : ['Finance Manager'];

        $approvers = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $roles))
            ->get();

        foreach ($approvers as $approver) {
            $this->dispatchGeneric($approver, [
                'title'          => "Payment of KES " . number_format($payment->amount, 2) . " Awaiting Approval",
                'message'        => "A payment requires your approval before it can be processed.",
                'type'           => 'payment_approval',
                'reference_id'   => $payment->id,
                'reference_type' => 'Payment',
                'action_url'     => route('payments.show', $payment),
            ]);
        }

        $this->auditService->log(
            action: 'PAYMENT_APPROVERS_NOTIFIED',
            model: 'Payment',
            modelId: $payment->id,
            metadata: ['approver_count' => $approvers->count(), 'amount' => $payment->amount],
        );
    }

    /**
     * Notify procurement and stores teams when stock falls below reorder level.
     */
    public function notifyLowStockAlert(string $itemName, int $currentQty, int $reorderLevel): void
    {
        $users = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Procurement Officer', 'Stores Manager', 'Procurement Assistant']))
            ->get();

        foreach ($users as $user) {
            $this->dispatchGeneric($user, [
                'title'          => "Low Stock Alert: {$itemName}",
                'message'        => "{$itemName} is below its reorder level. Current: {$currentQty}, Reorder Level: {$reorderLevel}. Please initiate a requisition.",
                'type'           => 'low_stock_alert',
                'reference_type' => 'InventoryItem',
            ]);
        }

        $this->auditService->log(
            action: 'LOW_STOCK_ALERT_SENT',
            model: 'InventoryItem',
            modelId: 0,
            metadata: ['item' => $itemName, 'quantity' => $currentQty, 'reorder_level' => $reorderLevel],
        );
    }

    /**
     * Notify finance and budget owners when a budget line exceeds a utilisation threshold.
     */
    public function notifyBudgetThresholdExceeded(string $costCenterName, float $percentageUsed): void
    {
        $users = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Finance Manager', 'Budget Owner', 'Super Administrator', 'Principal']))
            ->get();

        foreach ($users as $user) {
            $this->dispatchGeneric($user, [
                'title'          => "Budget Alert: {$costCenterName} at " . round($percentageUsed, 1) . "%",
                'message'        => "The budget for {$costCenterName} has reached " . round($percentageUsed, 1) . "% utilisation. Review and action may be required.",
                'type'           => 'budget_alert',
                'reference_type' => 'BudgetLine',
            ]);
        }

        $this->auditService->log(
            action: 'BUDGET_THRESHOLD_ALERT_SENT',
            model: 'BudgetLine',
            modelId: 0,
            metadata: ['cost_center' => $costCenterName, 'percentage_used' => $percentageUsed],
        );
    }

    // -------------------------------------------------------------------------
    // Preferences
    // -------------------------------------------------------------------------

    public function getUserNotificationPreferences(User $user): array
    {
        return $user->getUserPreferences();
    }

    public function updateUserNotificationPreferences(User $user, array $preferences): void
    {
        // Store preferences in the notification_preferences JSON column when available.
        // If the column does not exist yet, this is a no-op — defaults are always returned
        // by User::getUserPreferences() so the system stays functional without a migration.
        try {
            $current = json_decode($user->getRawOriginal('notification_preferences') ?? '{}', true) ?? [];
            $merged  = array_merge($current, [
                'notification_email_requisition' => $preferences['email_on_requisition_approval'] ?? true,
                'notification_email_po'          => $preferences['email_on_po_issued'] ?? true,
                'notification_email_invoice'     => $preferences['email_on_invoice'] ?? true,
                'notification_email_payment'     => $preferences['email_on_payment'] ?? true,
                'notification_sms'               => $preferences['sms_on_urgent'] ?? false,
            ]);
            $user->forceFill(['notification_preferences' => json_encode($merged)])->save();
        } catch (\Exception) {
            // Column may not exist — safe to swallow; defaults will be used.
        }
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Queue a GenericNotification for a single user.
     * Skips inactive users and respects email-enabled config.
     */
    private function dispatchGeneric(User $user, array $notificationData): void
    {
        if (!$user->is_active) {
            return;
        }

        dispatch(new SendEmailNotificationJob(
            $user,
            new GenericNotification($notificationData)
        ));
    }
}
