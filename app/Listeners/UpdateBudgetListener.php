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

            app(\App\Core\Audit\AuditService::class)->log(
                'BUDGET_COMMITTED',
                'BudgetTransaction',
                $requisition->budget_line_id,
                null,
                null,
                "Budget committed for PO {$purchaseOrder->po_number}",
                [
                    'po_id' => $purchaseOrder->id,
                    'amount' => $purchaseOrder->total_amount,
                ]
            );
        } catch (\Exception $e) {
            app(\App\Core\Audit\AuditService::class)->log(
                'BUDGET_COMMITMENT_FAILED',
                'BudgetTransaction',
                null,
                null,
                null,
                "Failed to commit budget for PO: {$e->getMessage()}",
                [
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

            app(\App\Core\Audit\AuditService::class)->log(
                'BUDGET_ADJUSTED',
                'BudgetTransaction',
                $budgetLine->id,
                null,
                null,
                "Budget adjusted for invoice variance",
                [
                    'invoice_id' => $invoice->id,
                    'variance_amount' => $difference,
                ]
            );
        } catch (\Exception $e) {
            app(\App\Core\Audit\AuditService::class)->log(
                'BUDGET_ADJUSTMENT_FAILED',
                'BudgetTransaction',
                null,
                null,
                null,
                "Failed to adjust budget for invoice: {$e->getMessage()}",
                [
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

            app(\App\Core\Audit\AuditService::class)->log(
                'BUDGET_EXECUTED',
                'BudgetTransaction',
                null,
                null,
                null,
                "Budget executed for payment {$payment->reference_number}",
                [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'budget_lines' => $budgetLines->count(),
                ]
            );
        } catch (\Exception $e) {
            app(\App\Core\Audit\AuditService::class)->log(
                'BUDGET_EXECUTION_FAILED',
                'BudgetTransaction',
                null,
                null,
                null,
                "Failed to execute budget for payment: {$e->getMessage()}",
                [
                    'error' => $e->getMessage(),
                ]
            );
        }
    }
}
