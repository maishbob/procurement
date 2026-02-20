<?php

namespace App\Jobs;

use App\Core\Audit\AuditService;
use App\Modules\Finance\Models\SupplierInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VerifyEtimsInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Maximum retry attempts */
    public int $tries = 3;

    public function __construct(public readonly SupplierInvoice $invoice) {}

    /**
     * Exponential back-off: 30 s → 2 min → 5 min.
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(AuditService $auditService): void
    {
        if (!config('procurement.etims.enabled', false)) {
            return;
        }

        $controlNumber = $this->invoice->etims_control_number;

        if (empty($controlNumber)) {
            Log::debug("VerifyEtimsInvoiceJob: invoice #{$this->invoice->invoice_number} has no eTIMS control number, skipping.");
            return;
        }

        $apiUrl = rtrim(config('procurement.etims.api_url'), '/');

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Accept' => 'application/json'])
                ->post("{$apiUrl}/api/verify", [
                    'etims_control_number' => $controlNumber,
                    'invoice_number'       => $this->invoice->invoice_number,
                    'total_amount'         => $this->invoice->total_amount,
                ]);

            if ($response->successful()) {
                $this->invoice->update([
                    'etims_verified'    => true,
                    'etims_verified_at' => now(),
                    'etims_qr_code'     => $response->json('qr_code'),
                ]);

                $auditService->logCompliance(
                    'etims_verification',
                    SupplierInvoice::class,
                    $this->invoice->id,
                    ['status' => 'verified', 'control_number' => $controlNumber],
                    ['result' => 'passed']
                );
            } else {
                $this->invoice->update(['etims_verified' => false]);

                $auditService->logCompliance(
                    'etims_verification',
                    SupplierInvoice::class,
                    $this->invoice->id,
                    [
                        'status'         => 'failed',
                        'control_number' => $controlNumber,
                        'response_status' => $response->status(),
                        'response_body'   => $response->body(),
                    ],
                    ['result' => 'failed']
                );

                Log::warning("VerifyEtimsInvoiceJob: eTIMS verification failed for invoice #{$this->invoice->invoice_number}", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("VerifyEtimsInvoiceJob: exception for invoice #{$this->invoice->invoice_number}: {$e->getMessage()}");
            // Re-throw so the queue worker retries with back-off
            throw $e;
        }
    }
}
