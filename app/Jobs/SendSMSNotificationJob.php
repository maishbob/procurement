<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class SendSMSNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tries = 3;
    protected int $backoff = 60;

    public function __construct(
        protected User $user,
        protected string $message,
        protected string $type = 'notification'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if user has SMS notification enabled
            $preferences = $this->user->getUserPreferences();
            if (!($preferences['notification_sms'] ?? true)) {
                return;
            }

            // Check if user has phone number
            if (!$this->user->phone) {
                throw new \Exception('User has no phone number on file');
            }

            // Send SMS via configured provider (Twilio, Africastalking, etc)
            $smsProvider = config('procurement.sms_provider', 'twilio');

            if ($smsProvider === 'twilio') {
                $this->sendViaTwilio();
            } elseif ($smsProvider === 'africastalking') {
                $this->sendViaAfricasTalking();
            } else {
                throw new \Exception("SMS provider '{$smsProvider}' not configured");
            }

            // Log successful delivery
            \App\Core\Audit\AuditService::log(
                action: 'SMS_SENT',
                status: 'success',
                model_type: 'Notification',
                model_id: $this->user->id,
                description: "SMS notification sent to {$this->user->phone}",
                metadata: [
                    'type' => $this->type,
                    'phone' => $this->user->phone,
                    'provider' => $smsProvider,
                ]
            );
        } catch (\Exception $e) {
            // Log failure
            \App\Core\Audit\AuditService::log(
                action: 'SMS_FAILED',
                status: 'failed',
                model_type: 'Notification',
                model_id: $this->user->id,
                description: "Failed to send SMS to {$this->user->phone}: {$e->getMessage()}",
                metadata: [
                    'type' => $this->type,
                    'error' => $e->getMessage(),
                ]
            );

            if ($this->attempts() < $this->tries) {
                $this->release(60);
            } else {
                $this->fail($e);
            }
        }
    }

    /**
     * Send SMS via Twilio
     */
    protected function sendViaTwilio(): void
    {
        $twilio = new \Twilio\Rest\Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );

        $twilio->messages->create(
            $this->user->phone,
            [
                'from' => config('services.twilio.from'),
                'body' => $this->message,
            ]
        );
    }

    /**
     * Send SMS via Africas Talking
     */
    protected function sendViaAfricasTalking(): void
    {
        // Implementation for Africas Talking API
        $apiKey = config('services.africastalking.api_key');
        $username = config('services.africastalking.username');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sandbox.africastalking.com/version1/messaging');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'username' => $username,
            'to' => $this->user->phone,
            'message' => $this->message,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
            'apiKey: ' . $apiKey,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            throw new \Exception('Failed to send SMS via Africas Talking');
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'notification',
            'sms',
            'user:' . $this->user->id,
            'type:' . $this->type,
        ];
    }
}
