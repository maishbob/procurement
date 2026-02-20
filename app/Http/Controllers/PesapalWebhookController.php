<?php

namespace App\Http\Controllers;

use App\Jobs\PesapalIpnJob;
use App\Modules\Finance\Models\PaymentGatewayTransaction;
use App\Modules\Finance\Services\PesapalGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PesapalWebhookController extends Controller
{
    public function __construct(private PesapalGatewayService $pesapalService) {}

    /**
     * Handle PesaPal IPN callback.
     *
     * PesaPal POSTs to this URL when a payment status changes.
     * We validate the request, look up the transaction, and dispatch
     * a queued job to do the actual reconciliation.
     */
    public function callback(Request $request): JsonResponse
    {
        // Validate IPN token against the registered IPN ID in config.
        // PesaPal sends the IPN ID that was registered during setup — reject anything that doesn't match.
        $receivedIpnId = $request->input('pesapal_notification_type')
            ?? $request->input('ipn_id')
            ?? $request->header('X-PesaPal-IPN-ID');

        $expectedIpnId = config('services.pesapal.ipn_id', env('PESAPAL_IPN_ID'));

        if ($expectedIpnId && $receivedIpnId && !hash_equals((string) $expectedIpnId, (string) $receivedIpnId)) {
            \Log::warning('PesaPal IPN: IPN ID mismatch — possible forged request', [
                'received' => $receivedIpnId,
                'ip'       => $request->ip(),
            ]);
            return response()->json(['status' => 'forbidden'], 403);
        }

        // Validate required fields
        if (!$request->has('OrderTrackingId') && !$request->has('orderTrackingId')) {
            return response()->json(['status' => 'invalid_request'], 400);
        }

        $orderTrackingId  = $request->input('OrderTrackingId') ?? $request->input('orderTrackingId');
        $merchantRef      = $request->input('OrderMerchantReference') ?? $request->input('merchant_reference');

        // Find the matching transaction
        $transaction = PaymentGatewayTransaction::where('gateway_transaction_id', $orderTrackingId)
            ->orWhere('merchant_reference', $merchantRef)
            ->first();

        if (!$transaction) {
            // Return 200 so PesaPal doesn't keep retrying — just log and skip
            \Log::warning('PesaPal IPN: no matching transaction', [
                'order_tracking_id' => $orderTrackingId,
                'merchant_reference' => $merchantRef,
            ]);
            return response()->json(['status' => 'not_found'], 200);
        }

        // Dispatch async job so we respond to PesaPal immediately
        PesapalIpnJob::dispatch($transaction, $request->all());

        return response()->json(['status' => 'received'], 200);
    }
}
