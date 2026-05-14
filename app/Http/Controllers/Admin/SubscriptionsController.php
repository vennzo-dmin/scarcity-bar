<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BisAlertSubscription;
use App\Models\BisAttributionEvent;
use App\Models\BisDeliveryLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionsController extends Controller
{
    public function index(Request $request)
    {
        $shop = Auth::user();

        $q = BisAlertSubscription::with('subscriber')
            ->where('user_id', $shop->id);

        if ($type = $request->query('type'))     $q->where('type', $type);
        if ($status = $request->query('status')) $q->where('status', $status);
        if ($search = $request->query('q'))      $q->where('product_title', 'like', "%{$search}%");

        $rows = $q->orderByDesc('id')->paginate(25)->withQueryString();

        return view('bis.subscriptions', [
            'rows' => $rows,
            'host' => $request->query('host'),
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $shop = Auth::user();
        BisAlertSubscription::where('user_id', $shop->id)->where('id', $id)
            ->update(['status' => 'cancelled']);
        return response()->json(['ok' => true]);
    }

    /**
     * Merchant-useful CSV: every row has product context, customer contact,
     * consent state, alert lifecycle, last delivery outcome, and recovered
     * revenue attribution. Designed so a merchant can drop the file into
     * Klaviyo / Mailchimp / Excel for follow-up campaigns.
     */
    public function export(Request $request): StreamedResponse
    {
        $shop = Auth::user();
        $userId = $shop->id;
        $filename = 'waitlist-' . date('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($userId) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'subscription_id',
                'created_at',
                'alert_type',          // back_in_stock | price_drop
                'channel',             // email | sms | both
                'status',              // active | sent | cancelled | failed
                'product_title',
                'product_handle',
                'variant_title',
                'product_id',
                'variant_id',
                'price_at_subscription',
                'currency',
                'subscriber_email',
                'subscriber_phone',
                'shopify_customer_id',
                'email_consent',
                'sms_consent',
                'email_unsubscribed_at',
                'sms_unsubscribed_at',
                'locale',
                'unsubscribe_token',
                'last_delivery_status',
                'last_delivery_provider',
                'last_delivery_at',
                'last_delivery_error',
                'attributed_orders',
                'attributed_revenue',
            ]);

            BisAlertSubscription::with('subscriber')
                ->where('user_id', $userId)
                ->orderBy('id')
                ->chunk(500, function ($chunk) use ($out, $userId) {
                    $alertIds = $chunk->pluck('id')->all();

                    // Latest delivery per alert subscription, in one query.
                    $latestDeliveries = BisDeliveryLog::where('user_id', $userId)
                        ->whereIn('alert_subscription_id', $alertIds)
                        ->orderByDesc('id')
                        ->get()
                        ->groupBy('alert_subscription_id')
                        ->map->first();

                    // Attribution rollups per subscriber, scoped to chunk.
                    $subscriberIds = $chunk->pluck('subscriber_id')->filter()->unique()->all();
                    $attribution = BisAttributionEvent::where('user_id', $userId)
                        ->where('event', 'order')
                        ->whereIn('subscriber_id', $subscriberIds)
                        ->selectRaw('subscriber_id, COUNT(*) as orders, SUM(amount) as revenue')
                        ->groupBy('subscriber_id')
                        ->get()
                        ->keyBy('subscriber_id');

                    foreach ($chunk as $row) {
                        $sub = $row->subscriber;
                        $delivery = $latestDeliveries[$row->id] ?? null;
                        $attr = $sub ? ($attribution[$sub->id] ?? null) : null;

                        fputcsv($out, [
                            $row->id,
                            optional($row->created_at)->toDateTimeString(),
                            $row->type,
                            $row->channel,
                            $row->status,
                            $row->product_title,
                            $row->product_handle,
                            $row->variant_title,
                            $row->product_id,
                            $row->variant_id,
                            $row->price_at_subscription,
                            $row->currency,
                            $sub?->email,
                            $sub?->phone,
                            $sub?->shopify_customer_id,
                            $sub?->consent_email ? 'yes' : 'no',
                            $sub?->consent_sms ? 'yes' : 'no',
                            optional($sub?->email_unsubscribed_at)->toDateTimeString(),
                            optional($sub?->sms_unsubscribed_at)->toDateTimeString(),
                            $sub?->locale,
                            $sub?->unsubscribe_token,
                            $delivery?->status,
                            $delivery?->provider,
                            optional($delivery?->sent_at)->toDateTimeString(),
                            $delivery?->error,
                            (int) ($attr->orders ?? 0),
                            number_format((float) ($attr->revenue ?? 0), 2, '.', ''),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
