<?php

namespace App\Jobs\Bis;

use App\Models\BisAlertSubscription;
use App\Models\BisShopSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchAlertBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 20;

    public function __construct(
        public readonly int $userId,
        public readonly string $type,
        public readonly array $alertSubscriptionIds,
    ) {}

    public function handle(): void
    {
        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $this->userId],
            array_merge(['user_id' => $this->userId], BisShopSetting::defaults())
        );

        $sending   = $settings->sending ?? [];
        $batchSize = (int)($sending['batch_size'] ?? 200);
        $staggerMs = (int)($sending['stagger_ms'] ?? 0);
        $vipFirst  = (bool)($sending['vip_first'] ?? false);
        $vipTags   = (array)($sending['vip_tags'] ?? []);

        $subs = BisAlertSubscription::with('subscriber')
            ->whereIn('id', $this->alertSubscriptionIds)
            ->where('user_id', $this->userId)
            ->get();

        if ($vipFirst && !empty($vipTags)) {
            $subs = $subs->sortByDesc(function ($sub) use ($vipTags) {
                $tags = $sub->subscriber?->tags ?? [];
                return count(array_intersect($tags, $vipTags)) > 0 ? 1 : 0;
            })->values();
        }

        $delay = 0;
        foreach ($subs->chunk($batchSize) as $chunk) {
            foreach ($chunk as $sub) {
                SendAlertNotificationJob::dispatch($sub->id)
                    ->delay(now()->addMilliseconds($delay));
                $delay += $staggerMs;
            }
        }
    }
}
