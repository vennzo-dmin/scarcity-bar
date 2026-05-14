<?php

namespace Tests\Feature;

use App\Models\BisAlertSubscription;
use App\Models\BisShopSetting;
use App\Models\BisSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontSubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_creates_subscriber_and_alert_when_widget_enabled(): void
    {
        $shop = User::create([
            'name'     => 'demo-shop.myshopify.com',
            'email'    => 'owner@example.com',
            'password' => bcrypt('x'),
        ]);

        BisShopSetting::create(array_merge(
            ['user_id' => $shop->id],
            BisShopSetting::defaults(),
            ['is_enabled' => true]
        ));

        $res = $this->postJson('/api/storefront/subscribe', [
            'shop'          => $shop->name,
            'type'          => 'back_in_stock',
            'product_id'    => 12345,
            'variant_id'    => 67890,
            'product_title' => 'Test Product',
            'email'         => 'shopper@example.com',
            'consent_email' => true,
        ]);

        $res->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('bis_subscribers', [
            'user_id' => $shop->id,
            'email'   => 'shopper@example.com',
        ]);
        $this->assertDatabaseHas('bis_alert_subscriptions', [
            'user_id'    => $shop->id,
            'variant_id' => 67890,
            'status'     => BisAlertSubscription::STATUS_ACTIVE,
        ]);
    }

    public function test_subscribe_is_rejected_when_widget_disabled(): void
    {
        $shop = User::create([
            'name'     => 'disabled-shop.myshopify.com',
            'email'    => 'owner@example.com',
            'password' => bcrypt('x'),
        ]);

        BisShopSetting::create(array_merge(
            ['user_id' => $shop->id],
            BisShopSetting::defaults(),
            ['is_enabled' => false]
        ));

        $res = $this->postJson('/api/storefront/subscribe', [
            'shop'          => $shop->name,
            'type'          => 'back_in_stock',
            'product_id'    => 1,
            'product_title' => 'P',
            'email'         => 'x@y.com',
        ]);

        $res->assertStatus(403);
    }

    public function test_subscribe_requires_email_or_phone(): void
    {
        $shop = User::create([
            'name'     => 'demo2-shop.myshopify.com',
            'email'    => 'owner@example.com',
            'password' => bcrypt('x'),
        ]);

        BisShopSetting::create(array_merge(
            ['user_id' => $shop->id],
            BisShopSetting::defaults(),
            ['is_enabled' => true]
        ));

        $res = $this->postJson('/api/storefront/subscribe', [
            'shop'          => $shop->name,
            'type'          => 'back_in_stock',
            'product_id'    => 1,
            'product_title' => 'P',
        ]);

        $res->assertStatus(422);
    }
}
