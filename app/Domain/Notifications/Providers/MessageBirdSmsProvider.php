<?php

namespace App\Domain\Notifications\Providers;

use App\Domain\Notifications\Contracts\NotificationProvider;
use App\Domain\Notifications\NotificationPayload;
use App\Domain\Notifications\NotificationResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessageBirdSmsProvider implements NotificationProvider
{
    public function __construct(private readonly array $config) {}

    public function channel(): string { return 'sms'; }

    public function name(): string { return 'messagebird'; }

    public function send(NotificationPayload $payload): NotificationResult
    {
        $apiKey     = $this->config['api_key']    ?? null;
        $originator = $this->config['originator'] ?? null;

        if (!$apiKey || !$originator) {
            return NotificationResult::failure('messagebird_credentials_missing');
        }

        try {
            $response = Http::withHeaders([
                    'Authorization' => "AccessKey {$apiKey}",
                    'Content-Type'  => 'application/json',
                ])
                ->timeout(10)
                ->post('https://rest.messagebird.com/messages', [
                    'originator' => $originator,
                    'recipients' => [$payload->to],
                    'body'       => $this->truncateForSms($payload->body),
                ]);

            if ($response->successful()) {
                return NotificationResult::success($response->json('id'));
            }

            $error = $response->json('errors.0.description') ?? ('http_' . $response->status());
            return NotificationResult::failure($error);
        } catch (\Throwable $e) {
            Log::channel(config('bis.log_channel', 'stack'))
                ->error('[BIS][MessageBird] send failed', ['err' => $e->getMessage()]);
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
