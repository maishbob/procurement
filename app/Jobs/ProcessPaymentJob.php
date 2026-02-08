<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Payment;
use App\Services\PaymentService;

class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tries = 2;
    protected int $timeout = 300;

    public function __construct(
        protected Payment $payment,
        protected string $referenceNumber,
        protected string $processingNotes = ''
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $paymentService = app(PaymentService::class);

            // Process payment
            $paymentService->processPayment(
                $this->payment,
                auth()->user(), // Current user acts as processor
                $this->referenceNumber,
                $this->processingNotes
            );

            // Log successful processing
            \App\Core\Audit\AuditService::log(
                action: 'PAYMENT_PROCESSED',
                status: 'success',
                model_type: 'Payment',
                model_id: $this->payment->id,
                description: "Payment {$this->payment->id} processed with reference {$this->referenceNumber}",
                metadata: [
                    'payment_id' => $this->payment->id,
                    'amount' => $this->payment->amount,
                    'supplier_id' => $this->payment->supplier_id,
                    'reference' => $this->referenceNumber,
                ]
            );

            // Send notification to creator and approvers
            $this->notifyStakeholders();
        } catch (\Exception $e) {
            \App\Core\Audit\AuditService::log(
                action: 'PAYMENT_PROCESSING_FAILED',
                status: 'failed',
                model_type: 'Payment',
                model_id: $this->payment->id,
                description: "Failed to process payment {$this->payment->id}: {$e->getMessage()}",
                metadata: [
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }

    /**
     * Notify payment stakeholders
     */
    protected function notifyStakeholders(): void
    {
        // Notify payment creator
        if ($this->payment->created_by) {
            $creator = \App\Models\User::find($this->payment->created_by);
            if ($creator) {
                dispatch(new SendEmailNotificationJob(
                    $creator,
                    new \App\Notifications\PaymentProcessedNotification($this->payment)
                ));
            }
        }

        // Notify approvers
        $approvers = \App\Models\Payment::where('id', $this->payment->id)
            ->with('approvals')
            ->first()
            ?->approvals()
            ?->where('status', 'approved')
            ?->get()
            ?->map(fn($a) => \App\Models\User::find($a->approved_by))
            ?->filter()
            ?->unique('id');

        foreach ($approvers ?? [] as $approver) {
            dispatch(new SendEmailNotificationJob(
                $approver,
                new \App\Notifications\PaymentProcessedNotification($this->payment)
            ));
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'payments',
            'payment:' . $this->payment->id,
            'supplier:' . $this->payment->supplier_id,
        ];
    }
}
