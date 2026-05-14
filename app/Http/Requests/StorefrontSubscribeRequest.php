<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorefrontSubscribeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'shop'           => ['required', 'string', 'max:255'],
            'type'           => ['required', 'in:back_in_stock,price_drop'],
            'product_id'     => ['required', 'integer'],
            'variant_id'     => ['nullable', 'integer'],
            'product_handle' => ['nullable', 'string', 'max:255'],
            'product_title'  => ['required', 'string', 'max:255'],
            'variant_title'  => ['nullable', 'string', 'max:255'],
            'image_url'      => ['nullable', 'string', 'max:512'],
            'price'          => ['nullable', 'numeric'],
            'currency'       => ['nullable', 'string', 'max:6'],
            'email'          => ['nullable', 'email', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:32'],
            'locale'         => ['nullable', 'string', 'max:10'],
            'channel'        => ['nullable', 'in:email,sms,both'],
            'consent_email'  => ['nullable', 'boolean'],
            'consent_sms'    => ['nullable', 'boolean'],

            // Customer context passed from the theme's Liquid scope (no
            // Admin API scope required). Used for VIP-first sorting.
            'shopify_customer_id' => ['nullable', 'integer'],
            'tags'                => ['nullable', 'array'],
            'tags.*'              => ['string', 'max:60'],

            // From Liquid — used when targeting_mode=collections to validate signup.
            'product_collection_ids' => ['nullable', 'array'],
            'product_collection_ids.*' => ['integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (!$this->input('email') && !$this->input('phone')) {
                $v->errors()->add('email', 'Provide email or phone.');
            }
        });
    }
}
