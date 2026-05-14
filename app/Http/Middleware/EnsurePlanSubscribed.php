<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Util;

/**
 * Gate every BIS admin page behind an active Shopify plan. When the merchant
 * has no active charge, send them to our merchant-friendly /plans picker
 * (rather than Kyon147's stock billing UI).
 */
class EnsurePlanSubscribed
{
    public function handle(Request $request, Closure $next)
    {
        // Honor the package's master switch — if billing is disabled globally
        // the app is free for everyone, so let the request through.
        if (Util::getShopifyConfig('billing_enabled') !== true) {
            return $next($request);
        }

        $shop = Auth::user();

        // Already paying, on freemium, or grandfathered = let the request through.
        if (! $shop || $shop->plan || $shop->isFreemium() || $shop->isGrandfathered()) {
            return $next($request);
        }

        $args = [
            'host'   => $request->query('host'),
            'shop'   => $shop->getDomain()?->toNative(),
            'locale' => $request->query('locale'),
        ];

        // AJAX requests get the App-Bridge force-redirect contract.
        if ($request->ajax()) {
            return response()->json(
                ['forceRedirectUrl' => route('bis.plans', $args)],
                402
            );
        }

        return Redirect::route('bis.plans', $args);
    }
}
