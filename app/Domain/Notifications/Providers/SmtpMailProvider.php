<?php

namespace App\Domain\Notifications\Providers;

use App\Domain\Notifications\Contracts\NotificationProvider;
use App\Domain\Notifications\NotificationPayload;
use App\Domain\Notifications\NotificationResult;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Per-shop SMTP mailer — builds a transient Mailer from merchant-provided
 * credentials so sends go from the merchant's own domain.
 */
class SmtpMailProvider implements NotificationProvider
{
    public function __construct(private readonly array $config) {}

    public function channel(): string { return 'email'; }

    public function name(): string { return 'smtp'; }

    public function send(NotificationPayload $payload): NotificationResult
    {
        try {
            $mailer = $this->buildMailer();

            $fromAddress = $this->config['from_address'] ?? config('mail.from.address');
            $fromName    = $this->config['from_name']    ?? config('mail.from.name');

            $mailer->html($payload->body, function ($m) use ($payload, $fromAddress, $fromName) {
                $m->to($payload->to);
                $m->subject($payload->subject ?? 'Update from the shop');
                $m->from($fromAddress, $fromName);
            });

            return NotificationResult::success('smtp-' . Str::uuid()->toString());
        } catch (\Throwable $e) {
            Log::channel(config('bis.log_channel', 'stack'))
                ->error('[BIS][SMTP] send failed', ['err' => $e->getMessage()]);
            return NotificationResult::failure($e->getMessage());
        }
    }

    private function buildMailer()
    {
        $manager = app(MailManager::class);
        $name = 'bis_smtp_' . md5(json_encode([
            $this->config['smtp_host']       ?? '',
            $this->config['smtp_port']       ?? '',
            $this->config['smtp_user']       ?? '',
            $this->config['smtp_encryption'] ?? '',
        ]));

        config([
            "mail.mailers.$name" => [
                'transport'  => 'smtp',
                'host'       => $this->config['smtp_host'],
                'port'       => (int)($this->config['smtp_port'] ?? 587),
                'encryption' => $this->config['smtp_encryption'] ?? 'tls',
                'username'   => $this->config['smtp_user'] ?? null,
                'password'   => $this->config['smtp_pass'] ?? null,
                'timeout'    => 10,
                'local_domain' => null,
            ],
        ]);

        return $manager->mailer($name);
    }
}
