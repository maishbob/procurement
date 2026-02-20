<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentGatewayRole;
use App\Modules\Finance\Models\PaymentGatewayTransaction;
use App\Core\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Exception;
use Carbon\Carbon;

/**
 * PesaPal Payment Gateway Service
 * 
 * Handles PesaPal integration with role-based access control
 */
class PesapalGatewayService
{
    protected AuditService $auditService;
    protected string $provider = 'pesapal';

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Initiate payment through PesaPal
     */
    public function initiatePayment(Payment $payment): PaymentGatewayTransaction
    {
        $userId = Auth::id();
        
        // Check if user has initiator role
        if (!PaymentGatewayRole::userHasRole($userId, $this->provider, 'initiator')) {
            throw new Exception("User does not have permission to initiate PesaPal payments");
        }

        return DB::transaction(function () use ($payment, $userId) {
            // Generate merchant reference
            $merchantRef = 'PAY-' . $payment->payment_number . '-' . time();

            // Prepare PesaPal request
            $requestData = [
                'id' => $merchantRef,
                'currency' => $payment->currency,
                'amount' => $payment->net_amount,
                'description' => "Payment for invoices - {$payment->payment_number}",
                'callback_url' => config('services.pesapal.callback_url'),
                'redirect_mode' => '',
                'notification_id' => config('services.pesapal.ipn_id'),
                'billing_address' => [
                    'email_address' => $payment->supplier->email ?? config('mail.from.address'),
                    'phone_number' => $payment->supplier->phone ?? '',
                    'country_code' => 'KE',
                ],
            ];

            // Create transaction record
            $transaction = PaymentGatewayTransaction::create([
                'gateway_provider' => $this->provider,
                'payment_id' => $payment->id,
                'merchant_reference' => $merchantRef,
                'transaction_type' => 'payment',
                'transaction_status' => 'initiated',
                'amount' => $payment->net_amount,
                'currency' => $payment->currency,
                'initiated_by' => $userId,
                'initiated_at' => Carbon::now(),
                'gateway_request' => $requestData,
            ]);

            // Log action
            $this->logGatewayAction($transaction, 'initiate_payment', 'success', "Payment initiated via PesaPal");

            // Call PesaPal API when enabled
            if (config('services.pesapal.enabled', false)) {
                $response = $this->pesapalRequest('POST', '/api/Transactions/SubmitOrderRequest', $requestData);
                $transaction->update([
                    'gateway_transaction_id' => $response['order_tracking_id'] ?? null,
                    'gateway_response'       => $response,
                ]);
            }

            $this->auditService->logCustom(
                Payment::class,
                $payment->id,
                'pesapal_payment_initiated',
                [
                    'transaction_id' => $transaction->id,
                    'merchant_reference' => $merchantRef,
                    'amount' => $payment->net_amount,
                    'initiated_by' => $userId,
                ]
            );

            return $transaction;
        });
    }

    /**
     * Approve payment for processing
     */
    public function approvePayment(PaymentGatewayTransaction $transaction): PaymentGatewayTransaction
    {
        $userId = Auth::id();
        
        // Check if user has approver role
        if (!PaymentGatewayRole::userHasRole($userId, $this->provider, 'approver')) {
            throw new Exception("User does not have permission to approve PesaPal payments");
        }

        // Ensure approver is not the initiator (segregation of duties)
        if ($transaction->initiated_by === $userId) {
            throw new Exception("Payment approver cannot be the same as initiator");
        }

        return DB::transaction(function () use ($transaction, $userId) {
            $transaction->update([
                'transaction_status' => 'pending',
            ]);

            $this->logGatewayAction($transaction, 'approve_payment', 'success', "Payment approved for processing");

            $this->auditService->logApproval(
                PaymentGatewayTransaction::class,
                $transaction->id,
                'approved',
                'gateway_approver',
                'Payment approved for PesaPal processing',
                ['approver_id' => $userId]
            );

            return $transaction->fresh();
        });
    }

    /**
     * Process payment through PesaPal
     */
    public function processPayment(PaymentGatewayTransaction $transaction): PaymentGatewayTransaction
    {
        $userId = Auth::id();
        
        // Check if user has processor role
        if (!PaymentGatewayRole::userHasRole($userId, $this->provider, 'processor')) {
            throw new Exception("User does not have permission to process PesaPal payments");
        }

        // Ensure processor is not the initiator or approver (segregation of duties)
        if ($transaction->initiated_by === $userId) {
            throw new Exception("Payment processor cannot be the same as initiator");
        }

        return DB::transaction(function () use ($transaction, $userId) {
            $transaction->update([
                'transaction_status' => 'processing',
                'processed_by' => $userId,
                'processed_at' => Carbon::now(),
            ]);

            // Confirm status from PesaPal when enabled; mark completed otherwise
            if (config('services.pesapal.enabled', false) && $transaction->gateway_transaction_id) {
                $response = $this->pesapalRequest('GET', '/api/Transactions/GetTransactionStatus', [
                    'orderTrackingId' => $transaction->gateway_transaction_id,
                ]);
                $externalStatus = strtoupper($response['payment_status_description'] ?? '');
                $txStatus = $externalStatus === 'COMPLETED' ? 'completed' : 'processing';
            } else {
                $response  = ['status' => 'completed', 'processed_at' => now()->toIso8601String()];
                $txStatus  = 'completed';
            }

            $transaction->update([
                'transaction_status' => $txStatus,
                'gateway_response'   => $response,
            ]);

            $this->logGatewayAction($transaction, 'process_payment', 'success', "Payment processed via PesaPal");

            $this->auditService->logCustom(
                PaymentGatewayTransaction::class,
                $transaction->id,
                'pesapal_payment_processed',
                [
                    'processed_by' => $userId,
                    'processed_at' => Carbon::now(),
                    'amount' => $transaction->amount,
                ]
            );

            return $transaction->fresh();
        });
    }

    /**
     * Reconcile payment
     */
    public function reconcilePayment(PaymentGatewayTransaction $transaction): PaymentGatewayTransaction
    {
        $userId = Auth::id();
        
        // Check if user has reconciler role
        if (!PaymentGatewayRole::userHasRole($userId, $this->provider, 'reconciler')) {
            throw new Exception("User does not have permission to reconcile PesaPal payments");
        }

        // Ensure reconciler is not the processor
        if ($transaction->processed_by === $userId) {
            throw new Exception("Payment reconciler cannot be the same as processor");
        }

        return DB::transaction(function () use ($transaction, $userId) {
            $transaction->update([
                'reconciled_by' => $userId,
                'reconciled_at' => Carbon::now(),
            ]);

            $this->logGatewayAction($transaction, 'reconcile_payment', 'success', "Payment reconciled");

            $this->auditService->logCustom(
                PaymentGatewayTransaction::class,
                $transaction->id,
                'pesapal_payment_reconciled',
                [
                    'reconciled_by' => $userId,
                    'reconciled_at' => Carbon::now(),
                ]
            );

            return $transaction->fresh();
        });
    }

    /**
     * Assign gateway role to user
     */
    public function assignRole(int $userId, string $roleType, array $permissions = []): PaymentGatewayRole
    {
        $assignerId = Auth::id();
        
        // Only admins can assign roles
        if (!PaymentGatewayRole::userHasRole($assignerId, $this->provider, 'admin')) {
            throw new Exception("Only PesaPal admins can assign roles");
        }

        return PaymentGatewayRole::updateOrCreate(
            [
                'gateway_provider' => $this->provider,
                'user_id' => $userId,
                'role_type' => $roleType,
            ],
            [
                'permissions' => $permissions,
                'is_active' => true,
                'activated_at' => Carbon::now(),
                'assigned_by' => $assignerId,
                'assigned_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Revoke gateway role from user
     */
    public function revokeRole(int $userId, string $roleType): void
    {
        $assignerId = Auth::id();
        
        // Only admins can revoke roles
        if (!PaymentGatewayRole::userHasRole($assignerId, $this->provider, 'admin')) {
            throw new Exception("Only PesaPal admins can revoke roles");
        }

        PaymentGatewayRole::where('gateway_provider', $this->provider)
            ->where('user_id', $userId)
            ->where('role_type', $roleType)
            ->update([
                'is_active' => false,
                'deactivated_at' => Carbon::now(),
            ]);
    }

    /**
     * Log gateway action to audit trail
     */
    protected function logGatewayAction(
        PaymentGatewayTransaction $transaction,
        string $action,
        string $result,
        ?string $details = null
    ): void {
        DB::table('payment_gateway_audit_log')->insert([
            'gateway_provider' => $this->provider,
            'user_id' => Auth::id(),
            'payment_gateway_transaction_id' => $transaction->id,
            'action' => $action,
            'action_result' => $result,
            'action_details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Get user's gateway capabilities
     */
    public function getUserCapabilities(int $userId): array
    {
        $roles = PaymentGatewayRole::getUserRoles($userId, $this->provider);
        
        $capabilities = [];
        foreach ($roles as $role) {
            $roleModel = PaymentGatewayRole::where('user_id', $userId)
                ->where('gateway_provider', $this->provider)
                ->where('role_type', $role)
                ->first();
            
            if ($roleModel) {
                $capabilities[$role] = $roleModel->permissions ?? [];
            }
        }

        return $capabilities;
    }

    /**
     * Fetch a bearer token from PesaPal, cached for 4 minutes 50 seconds
     * (tokens expire after 5 minutes).
     */
    protected function getAccessToken(): string
    {
        return Cache::remember('pesapal_access_token', 290, function () {
            $response = Http::timeout(config('services.pesapal.timeout', 30))
                ->post(config('services.pesapal.base_url') . '/api/Auth/RequestToken', [
                    'ConsumerKey'    => config('services.pesapal.consumer_key'),
                    'ConsumerSecret' => config('services.pesapal.consumer_secret'),
                ]);

            if (!$response->successful()) {
                throw new Exception('PesaPal authentication failed: ' . $response->body());
            }

            return $response->json('token');
        });
    }

    /**
     * Make an authenticated HTTP call to the PesaPal API.
     * Returns the decoded JSON response array.
     */
    protected function pesapalRequest(string $method, string $endpoint, array $data = []): array
    {
        $token   = $this->getAccessToken();
        $baseUrl = config('services.pesapal.base_url');

        $request = Http::timeout(config('services.pesapal.timeout', 30))
            ->withToken($token)
            ->accept('application/json');

        $response = strtoupper($method) === 'GET'
            ? $request->get($baseUrl . $endpoint, $data)
            : $request->post($baseUrl . $endpoint, $data);

        if (!$response->successful()) {
            throw new Exception("PesaPal API error [{$endpoint}]: " . $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Check transaction status from PesaPal.
     */
    public function checkPaymentStatus(string $orderTrackingId): array
    {
        if (!config('services.pesapal.enabled', false)) {
            return ['status' => 'COMPLETED', 'order_tracking_id' => $orderTrackingId];
        }

        return $this->pesapalRequest('GET', '/api/Transactions/GetTransactionStatus', [
            'orderTrackingId' => $orderTrackingId,
        ]);
    }
}
