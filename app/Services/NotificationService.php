<?php

namespace App\Services;

use App\Core\Audit\AuditService;
use App\Models\User;
use App\Models\Requisition;
use App\Models\PurchaseOrder;
use App\Models\SupplierInvoice;
use App\Models\Payment;
use Illuminate\Notifications\Notification;

class NotificationService
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Notify approvers about pending requisition approval
     */
    public function notifyRequisitionApprovers(Requisition $requisition): void
    {
        $approverLevel = $requisition->next_approval_level;
        $approverRole = match ($approverLevel) {
            1 => 'department_head',
            2 => 'finance_manager',
            3 => 'procurement_officer',
            default => 'admin',
        };

        $approvers = User::role($approverRole)
            ->where('department_id', $requisition->department_id)
            ->active()
            ->get();

        foreach ($approvers as $approver) {
            $this->sendNotification($approver, [
                'title' => "Requisition #{$requisition->requisition_number} Awaiting Approval",
                'message' => "A requisition for {$requisition->items->count()} items totaling KES " . number_format($requisition->items->sum(fn($i) => $i->quantity * $i->unit_price), 2) . " requires your approval.",
                'type' => 'requisition_approval',
                'reference_id' => $requisition->id,
                'reference_type' => 'Requisition',
                'action_url' => route('requisitions.show', $requisition),
            ]);
        }
    }

    /**
     * Notify requisitioner about requisition status changes
     */
    public function notifyRequisitionStatusChange(Requisition $requisition, string $oldStatus, string $newStatus): void
    {
        $message = match ($newStatus) {
            'approved' => "Your requisition has been approved.",
            'rejected' => "Your requisition has been rejected. Reason: {$requisition->rejection_reason}",
            'converted_to_po' => "Your requisition has been converted to a Purchase Order.",
            default => "Requisition status changed to {$newStatus}.",
        };

        $this->sendNotification($requisition->requester, [
            'title' => "Requisition #{$requisition->requisition_number} Status Update",
            'message' => $message,
            'type' => 'requisition_status',
            'reference_id' => $requisition->id,
            'reference_type' => 'Requisition',
            'action_url' => route('requisitions.show', $requisition),
        ]);
    }

    /**
     * Notify supplier about purchase order issuance
     */
    public function notifySupplierPOIssued(PurchaseOrder $po): void
    {
        // TODO: Send email to supplier with PO details
        $emailData = [
            'supplier_name' => $po->supplier->name,
            'po_number' => $po->po_number,
            'total_amount' => $po->total_amount,
            'delivery_date' => $po->delivery_date,
            'po_url' => route('purchase-orders.show', $po),
        ];

        $this->auditService->log(
            action: 'SUPPLIER_NOTIFIED_PO_ISSUED',
            status: 'success',
            model_type: 'PurchaseOrder',
            model_id: $po->id,
            description: "Supplier {$po->supplier->name} notified of PO issuance via email",
        );
    }

    /**
     * Notify finance team about invoice awaiting verification
     */
    public function notifyInvoiceAwaitingVerification(SupplierInvoice $invoice): void
    {
        $financeTeam = User::role('finance_manager')->active()->get();

        foreach ($financeTeam as $financer) {
            $this->sendNotification($financer, [
                'title' => "Invoice #{$invoice->invoice_number} Awaiting Three-Way Match Verification",
                'message' => "Invoice from {$invoice->supplier->name} for KES " . number_format($invoice->total_amount, 2) . " requires three-way match verification.",
                'type' => 'invoice_verification',
                'reference_id' => $invoice->id,
                'reference_type' => 'SupplierInvoice',
                'action_url' => route('finance.invoices.show', $invoice),
            ]);
        }
    }

    /**
     * Notify payment approvers about pending payment
     */
    public function notifyPaymentApprovers(Payment $payment): void
    {
        $approverRole = $payment->total_amount > 100000 ? 'super_admin' : 'finance_manager';
        $approvers = User::role($approverRole)->active()->get();

        foreach ($approvers as $approver) {
            $this->sendNotification($approver, [
                'title' => "Payment Awaiting Approval",
                'message' => "A payment of KES " . number_format($payment->total_amount, 2) . " for {$payment->invoices->count()} invoice(s) requires your approval.",
                'type' => 'payment_approval',
                'reference_id' => $payment->id,
                'reference_type' => 'Payment',
                'action_url' => route('finance.payments.show', $payment),
            ]);
        }
    }

    /**
     * Notify relevant parties about low stock alert
     */
    public function notifyLowStockAlert(string $itemName, int $currentQty, int $reorderLevel): void
    {
        $procurement = User::role('procurement_officer')->active()->get();
        $storeManagers = User::role('store_manager')->active()->get();

        $notifyUsers = $procurement->merge($storeManagers)->unique('id');

        foreach ($notifyUsers as $user) {
            $this->sendNotification($user, [
                'title' => "Low Stock Alert",
                'message' => "{$itemName} is below reorder level. Current: {$currentQty}, Reorder Level: {$reorderLevel}",
                'type' => 'low_stock_alert',
                'reference_type' => 'InventoryItem',
            ]);
        }
    }

    /**
     * Notify relevant parties about budget threshold exceeded
     */
    public function notifyBudgetThresholdExceeded(string $costCenterName, float $percentageUsed): void
    {
        $financeTeam = User::role('finance_manager')->active()->get();
        $superAdmins = User::role('super_admin')->active()->get();

        foreach ($financeTeam->merge($superAdmins)->unique('id') as $user) {
            $this->sendNotification($user, [
                'title' => "Budget Threshold Alert",
                'message' => "{$costCenterName} budget has reached {$percentageUsed}% utilization.",
                'type' => 'budget_alert',
                'reference_type' => 'BudgetLine',
            ]);
        }
    }

    /**
     * Send generic notification to user
     */
    private function sendNotification(User $user, array $notificationData): void
    {
        // Create notification in database
        // TODO: Implement notification dispatch to email/SMS based on user preferences

        $this->auditService->log(
            action: 'NOTIFICATION_SENT',
            status: 'success',
            model_type: 'Notification',
            model_id: 0,
            description: "Notification sent to {$user->name}: {$notificationData['title']}",
            metadata: [
                'user_id' => $user->id,
                'notification_type' => $notificationData['type'] ?? 'general',
            ]
        );
    }

    /**
     * Get notification preferences for a user
     */
    public function getUserNotificationPreferences(User $user): array
    {
        return [
            'email_on_requisition_approval' => $user->settings['notification_email_requisition'] ?? true,
            'email_on_po_issued' => $user->settings['notification_email_po'] ?? true,
            'email_on_invoice' => $user->settings['notification_email_invoice'] ?? true,
            'email_on_payment' => $user->settings['notification_email_payment'] ?? true,
            'sms_on_urgent' => $user->settings['notification_sms_urgent'] ?? false,
        ];
    }

    /**
     * Update notification preferences
     */
    public function updateUserNotificationPreferences(User $user, array $preferences): void
    {
        $user->settings = array_merge($user->settings ?? [], [
            'notification_email_requisition' => $preferences['email_on_requisition_approval'] ?? true,
            'notification_email_po' => $preferences['email_on_po_issued'] ?? true,
            'notification_email_invoice' => $preferences['email_on_invoice'] ?? true,
            'notification_email_payment' => $preferences['email_on_payment'] ?? true,
            'notification_sms_urgent' => $preferences['sms_on_urgent'] ?? false,
        ]);
        $user->save();
    }
}
