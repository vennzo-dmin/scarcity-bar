<?php

namespace App\Domain\Notifications\Providers;

use App\Domain\Notifications\Contracts\NotificationProvider;
use App\Domain\Notifications\NotificationPayload;
use App\Domain\Notifications\NotificationResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Twilio SMS provider — uses merchant-provided credentials only.
 * No global API key; tenant isolation by design.
 */
class TwilioSmsProvider implements NotificationProvider
{
    public function __construct(private readonly array $config) {}

    public function channel(): string { return 'sms'; }

    public function name(): string { return 'twilio'; }

    public function send(NotificationPayload $payload): NotificationResult
    {
        $sid   = $this->config['account_sid'] ?? null;
        $token = $this->config['auth_token']  ?? null;
        $from  = $this->config['from']        ?? null;

        if (!$sid || !$token || !$from) {
            return NotificationResult::failure('twilio_credentials_missing');
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->timeout(10)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To'   => $payload->to,
                    'Body' => $this->truncateForSms($payload->body),
                ]);

            if ($response->successful()) {
                return NotificationResult::success($response->json('sid'));
            }

            $error = $response->json('message') ?? ('http_' . $response->status());
            return NotificationResult::failure($error);
        } catch (\Throwable $e) {
            Log::channel(config('bis.log_channel', 'stack'))
                ->error('[BIS][Twilio] send failed', ['err' => $e->getMessage()]);
            return NotificationResult::failure($e->getMessage());
        }
    }

    private function truncateForSms(string $body): string
    {
        $stripped = trim(strip_tags($body));
        if (strlen($stripped) > 480) {
            $stripped = substr($stripped, 0, 477) . '...';
        }
        return $stripped;
    }
}
