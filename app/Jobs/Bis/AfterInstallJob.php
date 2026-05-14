<?php

namespace App\Jobs\Bis;

use App\Domain\Subscriptions\DefaultTemplatesProvisioner;
use App\Models\BisShopSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Contracts\ShopModel;

class AfterInstallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ShopModel $shop;

    public function __construct(ShopModel $shop)
    {
        $this->shop = $shop;
    }

    public function handle(): void
    {
        $shopId = $this->shop->getId()->toNative();

        BisShopSetting::firstOrCreate(
            ['user_id' => $shopId],
            array_merge(['user_id' => $shopId], BisShopSetting::defaults())
        );

        (new DefaultTemplatesProvisioner())->provisionFor($shopId, 'en');

        Log::channel(config('bis.log_channel', 'stack'))
            ->info('[BIS] After-install provisioning done', ['shop_id' => $shopId]);
    }
}
