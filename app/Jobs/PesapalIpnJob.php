<?php

namespace App\Jobs;

use App\Core\Audit\AuditService;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentGatewayTransaction;
use App\Modules\Finance\Services\PaymentService;
use App\Modules\Finance\Services\PesapalGatewayService;
use App\Core\Workflow\WorkflowEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PesapalIpnJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private PaymentGatewayTransaction $transaction,
        private array $ipnPayload = []
    ) {}

    public function handle(
        PesapalGatewayService $pesapalService,
        PaymentService $paymentService,
        WorkflowEngine $workflowEngine,
        AuditService $auditService
    ): void {
        $trackingId = $this->transaction->gateway_transaction_id;

        // Confirm status with PesaPal
        try {
            $status = $pesapalService->checkPaymentStatus($trackingId);
        } catch (\Exception $e) {
            Log::error('PesapalIpnJob: status check failed', [
                'transaction_id' => $this->transaction->id,
                'error' => $e->getMessage(),
            ]);
            $this->release(60 * pow(2, $this->attempts() - 1)); // exponential backoff
            return;
        }

        $externalStatus = strtoupper($status['payment_status_description'] ?? $status['status'] ?? '');

        if ($externalStatus === 'COMPLETED') {
            $this->markCompleted($auditService, $paymentService, $workflowEngine);
        } elseif (in_array($externalStatus, ['FAILED', 'INVALID', 'REVERSED'], true)) {
            $this->markFailed($auditService, $externalStatus);
        } else {
            // Still pending — release back to queue and retry later
            $this->release(120);
        }
    }

    private function markCompleted(
        AuditService $auditService,
        PaymentService $paymentService,
        WorkflowEngine $workflowEngine
    ): void {
        $this->transaction->update(['transaction_status' => 'completed']);

        $payment = Payment::find($this->transaction->payment_id);
        if (!$payment) {
            return;
        }

        // Update payment status to paid
        $payment->update([
            'status'    => 'paid',
            'paid_at'   => now(),
        ]);

        // Move committed budget to spent
        try {
            $paymentService->updateBudgetSpent($payment);
        } catch (\Exception $e) {
            Log::warning('PesapalIpnJob: updateBudgetSpent failed', ['error' => $e->getMessage()]);
        }

        $auditService->logCustom(
            Payment::class,
            $payment->id,
            'pesapal_payment_completed',
            ['transaction_id' => $this->transaction->id]
        );
    }

    private function markFailed(AuditService $auditService, string $reason): void
    {
        $this->transaction->update([
            'transaction_status' => 'failed',
            'error_message'      => "PesaPal status: {$reason}",
        ]);

        $auditService->logCustom(
            PaymentGatewayTransaction::class,
            $this->transaction->id,
            'pesapal_payment_failed',
            ['reason' => $reason, 'ipn_payload' => $this->ipnPayload]
        );
    }

    public function backoff(): array
    {
        return [30, 120, 300]; // 30s, 2min, 5min
    }
}
