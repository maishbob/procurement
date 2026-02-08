<?php

namespace App\Listeners;

use App\Events\PurchaseOrderIssuedEvent;
use App\Events\InvoiceVerifiedEvent;
use App\Events\PaymentProcessedEvent;

class UpdateBudgetListener
{
    /**
     * Handle PurchaseOrderIssuedEvent - Commit budget when PO issued
     */
    public function handlePOIssued(PurchaseOrderIssuedEvent $event): void
    {
        $purchaseOrder = $event->purchaseOrder;
        $requisition = $purchaseOrder->requisition;

        if (!$requisition || !$requisition->budget_line_id) {
            return;
        }

        try {
            $budgetService = app(\App\Services\BudgetService::class);

            // Commit budget for PO amount
            $budgetService->commitBudget(
                $requisition->budgetLine,
                $purchaseOrder->total_amount,
                "PO {$purchaseOrder->po_number} issued",
                $purchaseOrder->id,
                'PurchaseOrder'
            );

            \App\Core\Audit\AuditService::log(
                action: 'BUDGET_COMMITTED',
                status: 'success',
                model_type: 'BudgetTransaction',
                model_id: $requisition->budget_line_id,
                description: "Budget committed for PO {$purchaseOrder->po_number}",
                metadata: [
                    'po_id' => $purchaseOrder->id,
                    'amount' => $purchaseOrder->total_amount,
                ]
            );
        } catch (\Exception $e) {
            \App\Core\Audit\AuditService::log(
                action: 'BUDGET_COMMITMENT_FAILED',
                status: 'failed',
                model_type: 'BudgetTransaction',
                description: "Failed to commit budget for PO: {$e->getMessage()}",
                metadata: [
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Handle InvoiceVerifiedEvent - Adjust budget when invoice verified
     */
    public function handleInvoiceVerified(InvoiceVerifiedEvent $event): void
    {
        $invoice = $event->invoice;
        $purchaseOrder = $invoice->purchaseOrder;

        if (!$purchaseOrder || !$purchaseOrder->requisition || !$purchaseOrder->requisition->budget_line_id) {
            return;
        }

        try {
            $budgetService = app(\App\Services\BudgetService::class);
            $budgetLine = $purchaseOrder->requisition->budgetLine;

            // Adjust if invoice amount differs from PO
            $difference = $invoice->total_amount - $purchaseOrder->total_amount;

            if (abs($difference) > 0.01) {
                if ($difference > 0) {
                    // Invoice higher than PO - commit additional amount
                    $budgetService->commitBudget($budgetLine, $difference, "Invoice variance adjustment");
                } else {
                    // Invoice lower than PO - release amount
                    $budgetService->releaseBudget($budgetLine, abs($difference), "Invoice variance adjustment");
                }
            }

            \App\Core\Audit\AuditService::log(
                action: 'BUDGET_ADJUSTED',
                status: 'success',
                model_type: 'BudgetTransaction',
                model_id: $budgetLine->id,
                description: "Budget adjusted for invoice variance",
                metadata: [
                    'invoice_id' => $invoice->id,
                    'variance_amount' => $difference,
                ]
            );
        } catch (\Exception $e) {
            \App\Core\Audit\AuditService::log(
                action: 'BUDGET_ADJUSTMENT_FAILED',
                status: 'failed',
                model_type: 'BudgetTransaction',
                description: "Failed to adjust budget for invoice: {$e->getMessage()}",
                metadata: [
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Handle PaymentProcessedEvent - Execute budget when payment made
     */
    public function handlePaymentProcessed(PaymentProcessedEvent $event): void
    {
        $payment = $event->payment;

        // Get budget lines affected by these invoices
        $budgetLines = $payment->invoices()
            ->with('purchaseOrder.requisition.budgetLine')
            ->get()
            ->map(fn($inv) => $inv->purchaseOrder?->requisition?->budgetLine)
            ->filter()
            ->unique('id');

        try {
            $budgetService = app(\App\Services\BudgetService::class);

            foreach ($budgetLines as $budgetLine) {
                // Execute budget for payment amount
                $budgetService->executeBudget(
                    $budgetLine,
                    $payment->amount,
                    "Payment {$payment->reference_number} processed",
                    $payment->id,
                    'Payment'
                );
            }

            \App\Core\Audit\AuditService::log(
                action: 'BUDGET_EXECUTED',
                status: 'success',
                model_type: 'BudgetTransaction',
                description: "Budget executed for payment {$payment->reference_number}",
                metadata: [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'budget_lines' => $budgetLines->count(),
                ]
            );
        } catch (\Exception $e) {
            \App\Core\Audit\AuditService::log(
                action: 'BUDGET_EXECUTION_FAILED',
                status: 'failed',
                model_type: 'BudgetTransaction',
                description: "Failed to execute budget for payment: {$e->getMessage()}",
                metadata: [
                    'error' => $e->getMessage(),
                ]
            );
        }
    }
}
