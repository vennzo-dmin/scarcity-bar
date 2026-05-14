<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BisShopSetting extends Model
{
    protected $table = 'bis_shop_settings';

    protected $fillable = [
        'user_id', 'is_enabled', 'onboarded', 'default_locale',
        'widget', 'inventory', 'pricing', 'consent', 'sending', 'providers', 'locales',
        'translations',
    ];

    protected $casts = [
        'is_enabled'   => 'boolean',
        'onboarded'    => 'boolean',
        'widget'       => 'array',
        'inventory'    => 'array',
        'pricing'      => 'array',
        'consent'      => 'array',
        'sending'      => 'array',
        'providers'    => 'encrypted:array',
        'locales'      => 'array',
        'translations' => 'array',
    ];

    public static function defaults(): array
    {
        return [
            'is_enabled'     => false,
            'onboarded'      => false,
            'default_locale' => 'en',
            'widget' => [
                // Legacy notify modes (price-drop still uses these where applicable).
                'mode'             => 'inline',
                'button_color'     => '#111827',
                'button_text_color'=> '#FFFFFF',
                'border_radius'    => 8,
                'collect_phone'    => false,
                'show_on_price_drop' => true,
                'font_family'      => 'inherit',
                'position'         => 'before_buy_button',
                'show_icon'        => true,
                'button_full_width'=> true,

                // Theme integration overrides — let merchants on heavily
                // customized themes pin the widget exactly where they want
                // it, and optionally hide the native "Sold out" button so
                // the storefront only shows our "Notify Me" CTA.
                'buy_button_selector'      => null,
                'hide_sold_out_button'     => false,
                'sold_out_button_selector' => null,

                // ── Scarcity Bar (storefront) ─────────────────────────────
                'scarcity_max_stock'        => 50,
                'scarcity_low_threshold'    => 10,
                'scarcity_bar_radius'       => 999,
                'scarcity_track_color'      => '#E5E7EB',
                'scarcity_color_in_stock'   => '#16A34A',
                'scarcity_color_low_stock'  => '#D97706',
                'scarcity_color_sold_out'   => '#DC2626',
                'scarcity_animate_bar'      => true,
                'scarcity_show_pulse'       => true,

                // Per-page embed (merchant toggles).
                'page_product'    => true,
                'page_collection' => true,
                'page_index'      => true,
                'page_search'     => true,

                // all | products | collections
                'targeting_mode'        => 'all',
                'target_product_ids'    => [],
                'target_collection_ids' => [],
            ],
            'inventory' => [
                'rule'               => 'any_location', // any_location | sellable | online_only
                'min_threshold'      => 1,
                'ignore_locations'   => [],
                'debounce_seconds'   => 60,
            ],
            'pricing' => [
                'min_abs_drop'       => 0.50,
                'min_pct_drop'       => 5,
                'use_compare_at'     => true,
                'ignore_if_cheaper_than' => null,
            ],
            'consent' => [
                'require_email_consent' => true,
                'require_sms_consent'   => true,
                'consent_copy'          => 'I agree to receive notifications about this product.',
            ],
            'sending' => [
                'mode'           => 'immediate', // immediate | staggered | best_window
                'batch_size'     => 200,
                'stagger_ms'     => 150,
                'vip_first'      => false,
                'vip_tags'       => [],
                'timezone_aware' => false,
            ],
            'locales' => [
                'enabled'  => ['en'],
                'fallback' => 'en',
            ],
            'providers' => [
                'email' => [
                    'driver'       => 'default',    // default | smtp
                    'from_address' => null,
                    'from_name'    => null,
                    'smtp_host'    => null,
                    'smtp_port'    => 587,
                    'smtp_user'    => null,
                    'smtp_pass'    => null,
                    'smtp_encryption' => 'tls',    // tls | ssl | null
                ],
                'sms' => [
                    'driver' => 'log',             // log | twilio | messagebird
                    'twilio' => [
                        'account_sid' => null,
                        'auth_token'  => null,
                        'from'        => null,
                    ],
                    'messagebird' => [
                        'api_key'   => null,
                        'originator' => null,
                    ],
                ],
            ],
            // Storefront copy — every customer-facing string the widget
            // renders. Single dictionary, used everywhere (the merchant
            // writes their copy once in whatever language their store
            // primarily sells in; multi-language stores typically rely on
            // Shopify's native translation system on the theme side).
            'translations' => self::translationDefaults(),
        ];
    }

    /**
     * Single source of truth for storefront copy defaults.
     */
    public static function translationDefaults(): array
    {
        return [
            'modal_heading_back_in_stock' => 'Get notified when back in stock',
            'modal_heading_price_drop'    => 'Get notified on price drops',
            'button_label_back_in_stock'  => 'Notify Me When Available',
            'button_label_price_drop'     => 'Notify Me on Price Drop',
            'label_email'                 => 'Email',
            'placeholder_email'           => 'you@example.com',
            'label_phone'                 => 'Phone (optional)',
            'placeholder_phone'           => '+15555550123',
            'consent_text'                => 'I agree to receive notifications about this product.',
            'submit_label'                => 'Notify Me',
            'footer_note'                 => 'You can unsubscribe anytime.',
            'success_message'             => "You're on the list. We'll email you when it's back.",
            'error_message'               => 'Something went wrong. Please try again.',
            'validation_email_required'   => 'Please enter your email address.',
            'validation_email_invalid'    => "That doesn't look like a valid email address.",
            'validation_phone_invalid'    => 'Please enter a valid phone number, or leave it empty.',
            'validation_consent_required' => 'Please accept the consent to continue.',

            // Scarcity Bar — use {count} as a placeholder for inventory quantity.
            'scarcity_label_in_stock'   => 'In stock — {count} available',
            'scarcity_label_low_stock'  => 'Only {count} left in stock',
            'scarcity_label_unknown'    => 'Available in stock',
            'scarcity_label_low_header' => 'Low stock',
            'scarcity_label_listing_sold_out' => 'Sold out',
            'scarcity_notify_heading'   => 'Get notified when this option is back',
            'scarcity_notify_sub'       => 'Leave your email and we will let you know as soon as it returns.',
            'price_drop_notify_sub'     => 'We will email you if the price drops on this option.',
        ];
    }

    /**
     * Whether the scarcity / notify widget should render for this catalog product.
     *
     * @param  int|null  $productId  Shopify product numeric id
     * @param  int[]  $collectionNumericIds  Collections the product belongs to (from Liquid)
     */
    public function passesProductTargeting(?int $productId, array $collectionNumericIds = []): bool
    {
        $w = $this->widget ?? [];
        $mode = (string)($w['targeting_mode'] ?? 'all');
        if ($mode === 'all') {
            return true;
        }
        if (!$productId) {
            return false;
        }
        if ($mode === 'products') {
            $ids = array_map('intval', (array)($w['target_product_ids'] ?? []));

            return in_array($productId, $ids, true);
        }
        if ($mode === 'collections') {
            $ids = array_map('intval', (array)($w['target_collection_ids'] ?? []));
            if ($ids === []) {
                return false;
            }
            foreach ($collectionNumericIds as $cid) {
                if (in_array((int) $cid, $ids, true)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    public function get(string $group, string $key = null, $default = null)
    {
        $data = $this->{$group} ?? [];
        if ($key === null) return $data;
        return data_get($data, $key, $default);
    }
}
