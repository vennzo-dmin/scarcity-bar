<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWidgetSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'widget'                 => ['nullable', 'array'],
            'widget.mode'            => ['nullable', 'in:button,inline,popup'],
            'widget.button_color'    => ['nullable', 'string', 'max:20'],
            'widget.button_text_color' => ['nullable', 'string', 'max:20'],
            'widget.border_radius'   => ['nullable', 'integer', 'min:0', 'max:40'],
            'widget.collect_phone'   => ['nullable', 'boolean'],
            'widget.show_on_price_drop' => ['nullable', 'boolean'],
            'widget.position'        => ['nullable', 'in:after_buy_button,before_buy_button,floating'],
            'widget.font_family'     => ['nullable', 'string', 'max:120'],
            'widget.show_icon'       => ['nullable', 'boolean'],
            'widget.button_full_width' => ['nullable', 'boolean'],
            'widget.buy_button_selector'      => ['nullable', 'string', 'max:255'],
            'widget.hide_sold_out_button'     => ['nullable', 'boolean'],
            'widget.sold_out_button_selector' => ['nullable', 'string', 'max:255'],

            // Scarcity Bar
            'widget.scarcity_max_stock'        => ['nullable', 'integer', 'min:1', 'max:999999'],
            'widget.scarcity_low_threshold'  => ['nullable', 'integer', 'min:1', 'max:999999'],
            'widget.scarcity_bar_radius'      => ['nullable', 'integer', 'min:0', 'max:9999'],
            'widget.scarcity_track_color'     => ['nullable', 'string', 'max:20'],
            'widget.scarcity_color_in_stock'  => ['nullable', 'string', 'max:20'],
            'widget.scarcity_color_low_stock' => ['nullable', 'string', 'max:20'],
            'widget.scarcity_color_sold_out'  => ['nullable', 'string', 'max:20'],
            'widget.scarcity_animate_bar'     => ['nullable', 'boolean'],
            'widget.scarcity_show_pulse'      => ['nullable', 'boolean'],

            'widget.page_product'    => ['nullable', 'boolean'],
            'widget.page_collection' => ['nullable', 'boolean'],
            'widget.page_index'      => ['nullable', 'boolean'],
            'widget.page_search'     => ['nullable', 'boolean'],

            'widget.targeting_mode' => ['nullable', 'in:all,products,collections'],
            // Stored as parsed int[] in controller — accept loose string from textarea.
            'widget.target_product_ids'    => ['nullable'],
            'widget.target_collection_ids' => ['nullable'],
            'widget.target_product_labels'    => ['nullable', 'string', 'max:20000'],
            'widget.target_collection_labels' => ['nullable', 'string', 'max:20000'],

            'inventory'              => ['nullable', 'array'],
            'inventory.rule'         => ['nullable', 'in:any_location,sellable,online_only'],
            'inventory.min_threshold'=> ['nullable', 'integer', 'min:1'],
            'inventory.debounce_seconds' => ['nullable', 'integer', 'min:0'],

            'pricing'                => ['nullable', 'array'],
            'pricing.min_abs_drop'   => ['nullable', 'numeric', 'min:0'],
            'pricing.min_pct_drop'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pricing.use_compare_at' => ['nullable', 'boolean'],

            'consent'                => ['nullable', 'array'],
            'consent.require_email_consent' => ['nullable', 'boolean'],
            'consent.require_sms_consent'   => ['nullable', 'boolean'],
            'consent.consent_copy'   => ['nullable', 'string', 'max:500'],

            'sending'                => ['nullable', 'array'],
            'sending.mode'           => ['nullable', 'in:immediate,staggered,best_window'],
            'sending.batch_size'     => ['nullable', 'integer', 'min:1', 'max:5000'],
            'sending.stagger_ms'     => ['nullable', 'integer', 'min:0', 'max:10000'],
            'sending.vip_first'      => ['nullable', 'boolean'],
            'sending.vip_tags'       => ['nullable', 'array'],
            'sending.vip_tags.*'     => ['string', 'max:60'],
        ];
    }
}
