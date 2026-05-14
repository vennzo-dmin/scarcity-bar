<?php

namespace Database\Seeders;

use App\Domain\Subscriptions\DefaultTemplatesProvisioner;
use App\Models\BisShopSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class BisDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $provisioner = new DefaultTemplatesProvisioner();

        User::query()->chunkById(100, function ($shops) use ($provisioner) {
            foreach ($shops as $shop) {
                BisShopSetting::firstOrCreate(
                    ['user_id' => $shop->id],
                    array_merge(['user_id' => $shop->id], BisShopSetting::defaults())
                );
                $provisioner->provisionFor($shop->id, 'en');
            }
        });
    }
}
