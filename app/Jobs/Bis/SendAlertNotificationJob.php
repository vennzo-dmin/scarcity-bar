<?php

namespace App\Jobs\Bis;

use App\Domain\Notifications\NotificationEngine;
use App\Models\BisAlertSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAlertNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public readonly int $alertSubscriptionId) {}

    public function handle(NotificationEngine $engine): void
    {
        $alert = BisAlertSubscription::with('subscriber')->find($this->alertSubscriptionId);
        if (!$alert) return;

        if ($alert->status === BisAlertSubscription::STATUS_SENT) return;

        try {
            $engine->sendForAlert($alert);

            $alert->update([
                'status'      => BisAlertSubscription::STATUS_SENT,
                'notified_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::channel(config('bis.log_channel', 'stack'))
                ->error('[BIS] SendAlertNotificationJob failed', [
                    'alert' => $this->alertSubscriptionId,
                    'err'   => $e->getMessage(),
                ]);

            $alert->update(['status' => BisAlertSubscription::STATUS_FAILED]);
            throw $e;
        }
    }
}
