<?php

namespace App\Domain\Notifications;

use App\Models\BisAlertSubscription;
use App\Models\BisNotificationTemplate;
use App\Models\BisShopSetting;
use App\Models\User;
use Illuminate\Support\Facades\URL;

class TemplateRenderer
{
    public function renderForAlert(
        User $shop,
        BisShopSetting $settings,
        BisAlertSubscription $alert,
        string $channel
    ): array {
        $template = $this->findTemplate($alert->user_id, $alert->type, $channel, $alert->subscriber?->locale, $settings);

        $subject = $template->subject ?? null;
        $body    = $template->body;

        $variables = $this->buildVariables($shop, $alert);

        return [
            'subject' => $subject ? $this->applyVariables($subject, $variables) : null,
            'body'    => $this->applyVariables($body, $variables),
        ];
    }

    private function findTemplate(
        int $userId,
        string $type,
        string $channel,
        ?string $locale,
        BisShopSetting $settings
    ): BisNotificationTemplate {
        $fallback = $settings->default_locale ?: 'en';
        $locale   = $locale ?: $fallback;

        $tpl = BisNotificationTemplate::where('user_id', $userId)
            ->where('type', $type)->where('channel', $channel)
            ->where('locale', $locale)->where('is_active', true)->first();

        if (!$tpl) {
            $tpl = BisNotificationTemplate::where('user_id', $userId)
                ->where('type', $type)->where('channel', $channel)
                ->where('locale', $fallback)->where('is_active', true)->first();
        }

        if (!$tpl) {
            $tpl = new BisNotificationTemplate([
                'user_id' => $userId,
                'type'    => $type,
                'channel' => $channel,
                'locale'  => $fallback,
                'subject' => $this->defaultSubject($type),
                'body'    => $this->defaultBody($type, $channel),
                'is_active' => true,
            ]);
        }

        return $tpl;
    }

    private function buildVariables(User $shop, BisAlertSubscription $alert): array
    {
        $sub = $alert->subscriber;
        $firstName = null;
        if ($sub && $sub->email) {
            $firstName = ucfirst(explode('@', $sub->email)[0]);
        }

        $productUrl = 'https://' . $shop->name . '/products/' . ($alert->product_handle ?? '');

        $trackedUrl = URL::signedRoute('bis.track.click', [
            'alert' => $alert->id,
            'token' => substr(hash_hmac('sha256', $alert->id . ':' . $alert->user_id, config('app.key')), 0, 16),
        ]);

        $unsubscribeUrl = $sub?->unsubscribe_token
            ? URL::signedRoute('bis.unsubscribe', ['token' => $sub->unsubscribe_token])
            : '#';

        $imgSrc = $this->normalizeImageUrl($alert->image_url ?? '', (string) $shop->name);
        $safeTitle = htmlspecialchars((string) $alert->product_title, ENT_QUOTES, 'UTF-8');
        $imgBlock = $imgSrc !== ''
            ? '<p style="margin:0 0 16px"><img src="' . htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') . '" alt="' . $safeTitle . '" style="max-width:100%;border-radius:8px"></p>'
            : '';

        return [
            '{{customer_first_name}}'   => $firstName ?? 'there',
            '{{product_title}}'         => $alert->product_title,
            '{{variant_title}}'         => $alert->variant_title ?? '',
            '{{product_url}}'           => $productUrl,
            '{{product_image}}'         => htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'),
            '{{product_image_block}}'   => $imgBlock,
            '{{price}}'                 => $alert->price_at_subscription ?? '',
            '{{currency}}'              => $alert->currency ?? '',
            '{{store_name}}'            => $shop->name,
            '{{cta_link}}'              => $trackedUrl,
            '{{unsubscribe_link}}'      => $unsubscribeUrl,
        ];
    }

    /**
     * Email clients need absolute https URLs; Shopify often returns protocol-relative CDN links.
     */
    private function normalizeImageUrl(?string $url, string $shopDomain): string
    {
        $u = trim((string) $url);
        if ($u === '') {
            return '';
        }
        if (str_starts_with($u, '//')) {
            return 'https:' . $u;
        }
        if (str_starts_with($u, 'http://') || str_starts_with($u, 'https://')) {
            return $u;
        }
        if (str_starts_with($u, '/')) {
            return 'https://' . $shopDomain . $u;
        }

        return 'https://' . $u;
    }

    private function applyVariables(string $text, array $vars): string
    {
        return strtr($text, $vars);
    }

    private function defaultSubject(string $type): string
    {
        return match ($type) {
            'back_in_stock' => '{{product_title}} is back in stock',
            'price_drop'    => 'Price drop on {{product_title}}',
            default         => 'Update from {{store_name}}',
        };
    }

    private function defaultBody(string $type, string $channel): string
    {
        if ($channel === 'sms') {
            return match ($type) {
                'back_in_stock' => 'Hi {{customer_first_name}}, {{product_title}} is back in stock. Shop: {{cta_link}}',
                'price_drop'    => 'Hi {{customer_first_name}}, price dropped on {{product_title}}. Shop: {{cta_link}}',
                default         => 'Update from {{store_name}}: {{cta_link}}',
            };
        }

        return <<<'HTML'
        <div style="font-family:Inter,Arial,sans-serif;max-width:560px;margin:auto;padding:24px;color:#111">
          <h2 style="margin:0 0 12px">Good news, {{customer_first_name}}!</h2>
          <p style="margin:0 0 16px">{{product_title}} {{variant_title}} is now available at {{store_name}}.</p>
          {{product_image_block}}
          <p style="margin:20px 0">
            <a href="{{cta_link}}" style="background:#111;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;display:inline-block">Shop now</a>
          </p>
          <p style="font-size:12px;color:#777">Don't want these? <a href="{{unsubscribe_link}}">Unsubscribe</a>.</p>
        </div>
        HTML;
    }
}
