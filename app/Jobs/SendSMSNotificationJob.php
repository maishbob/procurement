<?php

namespace App\Jobs;

use App\Core\Audit\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class SendSMSNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        protected User   $user,
        protected string $message,
        protected string $type = 'notification'
    ) {}

    public function handle(AuditService $auditService): void
    {
        try {
            // Respect per-user SMS preference
            if (!($this->user->getUserPreferences()['notification_sms'] ?? false)) {
                return;
            }

            if (!$this->user->phone) {
                throw new \Exception('User has no phone number on file');
            }

            if (!config('procurement.notifications.sms_enabled', env('NOTIFY_SMS_ENABLED', false))) {
                return; // SMS globally disabled
            }

            $provider = config('services.sms.driver', env('SMS_DRIVER', 'africastalking'));

            match ($provider) {
                'twilio'         => $this->sendViaTwilio(),
                'africastalking' => $this->sendViaAfricasTalking(),
                default          => throw new \Exception("SMS provider '{$provider}' is not configured"),
            };

            $auditService->log(
                action: 'SMS_SENT',
                model: 'Notification',
                modelId: $this->user->id,
                metadata: ['type' => $this->type, 'phone' => $this->user->phone, 'provider' => $provider],
            );
        } catch (\Exception $e) {
            $auditService->log(
                action: 'SMS_FAILED',
                model: 'Notification',
                modelId: $this->user->id,
                metadata: ['type' => $this->type, 'error' => $e->getMessage()],
            );

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            } else {
                $this->fail($e);
            }
        }
    }

    protected function sendViaTwilio(): void
    {
        $twilio = new \Twilio\Rest\Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );

        $twilio->messages->create($this->user->phone, [
            'from' => config('services.twilio.from'),
            'body' => $this->message,
        ]);
    }

    protected function sendViaAfricasTalking(): void
    {
        $apiKey   = config('services.africastalking.api_key');
        $username = config('services.africastalking.username');
        $senderId = config('services.africastalking.sender_id', '');

        // Production URL by default; override via AFRICASTALKING_API_URL for sandbox testing
        $apiUrl = config(
            'services.africastalking.api_url',
            'https://api.africastalking.com/version1/messaging'
        );

        $payload = [
            'username' => $username,
            'to'       => $this->user->phone,
            'message'  => $this->message,
        ];
        if ($senderId) {
            $payload['from'] = $senderId;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
                'apiKey: ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!$response || $curlError) {
            throw new \Exception("Africa's Talking API error: {$curlError}");
        }

        $decoded = json_decode($response, true);
        $status  = $decoded['SMSMessageData']['Recipients'][0]['status'] ?? null;
        if ($status && $status !== 'Success') {
            $code = $decoded['SMSMessageData']['Recipients'][0]['statusCode'] ?? 'unknown';
            throw new \Exception("Africa's Talking delivery failed (statusCode={$code})");
        }
    }

    public function tags(): array
    {
        return ['notification', 'sms', 'user:' . $this->user->id, 'type:' . $this->type];
    }
}
