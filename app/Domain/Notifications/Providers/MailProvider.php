<?php

namespace App\Domain\Notifications\Providers;

use App\Domain\Notifications\Contracts\NotificationProvider;
use App\Domain\Notifications\NotificationPayload;
use App\Domain\Notifications\NotificationResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MailProvider implements NotificationProvider
{
    public function channel(): string { return 'email'; }

    public function name(): string { return 'laravel_mailer'; }

    public function send(NotificationPayload $payload): NotificationResult
    {
        $log = Log::channel(config('bis.log_channel', 'stack'));

        try {
            Mail::html($payload->body, function ($m) use ($payload) {
                $m->to($payload->to);
                $m->subject($payload->subject ?? 'Update from the shop');
                if ($from = config('bis.mail_from')) {
                    $m->from($from['address'] ?? config('mail.from.address'), $from['name'] ?? config('mail.from.name'));
                }
            });

            // Note: "sent" here means the SMTP transport accepted the
            // message — it does NOT guarantee inbox delivery. If you're
            // seeing sent status but no customer receipt, check that
            // MAIL_HOST is a real provider (not Mailtrap sandbox) and that
            // the From address is verified with your ESP.
            $log->info('[BIS][Mail] handed off', [
                'to'      => $payload->to,
                'subject' => $payload->subject,
                'mailer'  => config('mail.default'),
                'host'    => config('mail.mailers.' . config('mail.default') . '.host'),
            ]);

            return NotificationResult::success('mail-' . Str::uuid()->toString());
        } catch (\Throwable $e) {
            $log->error('[BIS][Mail] send failed', [
                'to'  => $payload->to,
                'err' => $e->getMessage(),
            ]);
            return NotificationResult::failure($e->getMessage());
        }
    }
}
