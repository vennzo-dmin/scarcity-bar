<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ShopifyHelper;
use App\Http\Controllers\Controller;
use App\Models\BisAlertSubscription;
use App\Models\BisAttributionEvent;
use App\Models\BisDeliveryLog;
use App\Models\BisShopSetting;
use App\Models\BisSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $shop = Auth::user();
        $userId = $shop->id;

        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $userId],
            array_merge(['user_id' => $userId], BisShopSetting::defaults())
        );

        $stats = [
            'subscribers'       => BisSubscriber::where('user_id', $userId)->count(),
            'active_alerts'     => BisAlertSubscription::where('user_id', $userId)->where('status', 'active')->count(),
            'sent_30d'          => BisDeliveryLog::where('user_id', $userId)->where('status', 'sent')->where('created_at', '>=', now()->subDays(30))->count(),
            'recovered_30d'     => BisAttributionEvent::where('user_id', $userId)->where('event', 'order')->where('created_at', '>=', now()->subDays(30))->sum('amount'),
            'top_products'      => BisAlertSubscription::selectRaw('product_id, variant_id, product_title, variant_title, COUNT(*) as demand')
                                    ->where('user_id', $userId)
                                    ->where('status', 'active')
                                    ->groupBy('product_id', 'variant_id', 'product_title', 'variant_title')
                                    ->orderByDesc('demand')
                                    ->limit(10)->get(),
        ];

        // Build the Theme Editor deep-link to the App-embeds drawer.
        // For an app *embed* (not an app block), `?context=apps` is enough —
        // the merchant lands on the embeds panel and can toggle our embed.
        // We use "themes/current" as a fallback so the link still works if
        // the GraphQL theme lookup ever fails.
        $themeEmbedUrl = null;
        if ($shop->name) {
            $themeId = 'current';
            try {
                $details = ShopifyHelper::getShopifyStoreDetails();
                if (!empty($details['themeId'])) {
                    $themeId = $details['themeId'];
                }
            } catch (\Throwable $e) {
                \Log::channel(config('bis.log_channel', 'stack'))
                    ->warning('[BIS] theme lookup failed; using "current"', ['err' => $e->getMessage()]);
            }

            $storeHandle = preg_replace('/\.myshopify\.com$/i', '', (string) $shop->name);

            $themeEmbedUrl = sprintf(
                'https://admin.shopify.com/store/%s/themes/%s/editor?context=apps',
                $storeHandle,
                $themeId
            );
        }

        return view('bis.dashboard', [
            'settings'      => $settings,
            'stats'         => $stats,
            'host'          => $request->query('host'),
            'themeEmbedUrl' => $themeEmbedUrl,
        ]);
    }
}
