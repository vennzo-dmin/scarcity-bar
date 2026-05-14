<?php

namespace App\Domain\Subscriptions;

use App\Models\BisAlertSubscription;
use App\Models\BisSubscriber;
use Illuminate\Support\Facades\DB;

class SubscribeAction
{
    public function execute(int $userId, array $input): BisAlertSubscription
    {
        return DB::transaction(function () use ($userId, $input) {
            $subscriber = $this->upsertSubscriber($userId, $input);

            $existing = BisAlertSubscription::where('user_id', $userId)
                ->where('subscriber_id', $subscriber->id)
                ->where('type', $input['type'])
                ->where('variant_id', $input['variant_id'] ?? null)
                ->where('product_id', $input['product_id'])
                ->whereIn('status', [BisAlertSubscription::STATUS_ACTIVE, BisAlertSubscription::STATUS_QUEUED])
                ->first();

            if ($existing) {
                return $existing;
            }

            return BisAlertSubscription::create([
                'user_id'               => $userId,
                'subscriber_id'         => $subscriber->id,
                'type'                  => $input['type'],
                'channel'               => $input['channel'] ?? 'email',
                'product_id'            => $input['product_id'],
                'variant_id'            => $input['variant_id'] ?? null,
                'product_handle'        => $input['product_handle'] ?? null,
                'product_title'         => $input['product_title'] ?? '',
                'variant_title'         => $input['variant_title'] ?? null,
                'image_url'             => $input['image_url'] ?? null,
                'price_at_subscription' => $input['price'] ?? null,
                'currency'              => $input['currency'] ?? null,
                'status'                => BisAlertSubscription::STATUS_ACTIVE,
                'metadata'              => $input['metadata'] ?? null,
            ]);
        });
    }

    private function upsertSubscriber(int $userId, array $input): BisSubscriber
    {
        $email = $input['email'] ?? null;
        $phone = $input['phone'] ?? null;

        $query = BisSubscriber::where('user_id', $userId);
        if ($email) $query->where('email', $email);
        elseif ($phone) $query->where('phone', $phone);
        else abort(422, 'email_or_phone_required');

        $subscriber = $query->first();

        // Customer context passed from the storefront's Liquid scope. Only
        // present when the shopper was logged in — used for VIP-first
        // sending. No Admin API scope is required.
        $tags       = array_values(array_unique(array_filter((array)($input['tags'] ?? []))));
        $customerId = $input['shopify_customer_id'] ?? null;

        if (!$subscriber) {
            $subscriber = BisSubscriber::create([
                'user_id'             => $userId,
                'email'               => $email,
                'phone'               => $phone,
                'locale'              => $input['locale'] ?? null,
                'consent_email'       => $email ? (bool)($input['consent_email'] ?? true) : false,
                'consent_sms'         => $phone ? (bool)($input['consent_sms']   ?? false) : false,
                'shopify_customer_id' => $customerId,
                'tags'                => $tags ?: null,
            ]);
        } else {
            $dirty = false;
            if ($phone && !$subscriber->phone) { $subscriber->phone = $phone; $dirty = true; }
            if ($email && !$subscriber->email) { $subscriber->email = $email; $dirty = true; }
            if (isset($input['consent_email']) && $input['consent_email']) {
                $subscriber->consent_email = true;
                $subscriber->email_unsubscribed_at = null;
                $dirty = true;
            }
            if (isset($input['consent_sms']) && $input['consent_sms']) {
                $subscriber->consent_sms = true;
                $subscriber->sms_unsubscribed_at = null;
                $dirty = true;
            }
            if ($customerId && !$subscriber->shopify_customer_id) {
                $subscriber->shopify_customer_id = $customerId;
                $dirty = true;
            }
            if (!empty($tags)) {
                // Merge with any existing tags — the theme only exposes the
                // customer's current tags, so each signup refreshes them.
                $merged = array_values(array_unique(array_merge((array)$subscriber->tags, $tags)));
                if ($merged !== (array)$subscriber->tags) {
                    $subscriber->tags = $merged;
                    $dirty = true;
                }
            }
            if ($dirty) $subscriber->save();
        }

        return $subscriber;
    }
}
