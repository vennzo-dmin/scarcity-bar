<?php

namespace App\Domain\Subscriptions;

use App\Models\BisNotificationTemplate;

class DefaultTemplatesProvisioner
{
    public function provisionFor(int $userId, string $locale = 'en'): void
    {
        $templates = [
            [
                'type' => 'back_in_stock', 'channel' => 'email', 'locale' => $locale,
                'subject' => '{{product_title}} is back in stock',
                'body' => $this->emailBody('back_in_stock'),
            ],
            [
                'type' => 'price_drop', 'channel' => 'email', 'locale' => $locale,
                'subject' => 'Price drop on {{product_title}}',
                'body' => $this->emailBody('price_drop'),
            ],
            [
                'type' => 'back_in_stock', 'channel' => 'sms', 'locale' => $locale,
                'subject' => null,
                'body' => 'Hi {{customer_first_name}}, {{product_title}} is back in stock at {{store_name}}. Shop: {{cta_link}}',
            ],
            [
                'type' => 'price_drop', 'channel' => 'sms', 'locale' => $locale,
                'subject' => null,
                'body' => 'Price drop on {{product_title}} at {{store_name}}. Shop: {{cta_link}}',
            ],
        ];

        foreach ($templates as $tpl) {
            BisNotificationTemplate::updateOrCreate(
                [
                    'user_id' => $userId,
                    'type'    => $tpl['type'],
                    'channel' => $tpl['channel'],
                    'locale'  => $tpl['locale'],
                ],
                array_merge($tpl, ['is_active' => true, 'user_id' => $userId])
            );
        }
    }

    private function emailBody(string $type): string
    {
        $headline = $type === 'back_in_stock'
            ? 'Good news, {{customer_first_name}}!'
            : 'Great news, {{customer_first_name}}!';

        $lead = $type === 'back_in_stock'
            ? '{{product_title}} {{variant_title}} is now available at {{store_name}}.'
            : 'The price just dropped on {{product_title}} {{variant_title}} at {{store_name}}.';

        return <<<HTML
        <div style="font-family:Inter,Arial,sans-serif;max-width:560px;margin:auto;padding:24px;color:#111">
          <h2 style="margin:0 0 12px">$headline</h2>
          <p style="margin:0 0 16px">$lead</p>
          <p><img src="{{product_image}}" alt="{{product_title}}" style="max-width:100%;border-radius:8px"></p>
          <p style="margin:20px 0">
            <a href="{{cta_link}}" style="background:#111;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;display:inline-block">Shop now</a>
          </p>
          <p style="font-size:12px;color:#777">Don't want these? <a href="{{unsubscribe_link}}">Unsubscribe</a>.</p>
        </div>
        HTML;
    }
}
