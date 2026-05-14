<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BisAlertSubscription;
use App\Models\BisAttributionEvent;
use App\Models\BisDeliveryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $shop = Auth::user();
        $userId = $shop->id;
        $days = max(7, min(365, (int)$request->query('days', 30)));

        $base = now()->subDays($days);

        $deliveries = BisDeliveryLog::selectRaw('status, COUNT(*) as c')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $base)
            ->groupBy('status')->pluck('c', 'status');

        $recovered = BisAttributionEvent::where('user_id', $userId)
            ->where('event', 'order')
            ->where('created_at', '>=', $base)
            ->sum('amount');

        $topProducts = BisAlertSubscription::selectRaw('product_id, product_title, COUNT(*) as demand')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->groupBy('product_id', 'product_title')
            ->orderByDesc('demand')->limit(10)->get();

        $sentByType = BisDeliveryLog::selectRaw('type, COUNT(*) as c')
            ->where('user_id', $userId)
            ->where('status', 'sent')
            ->where('created_at', '>=', $base)
            ->groupBy('type')->pluck('c', 'type');

        $timeline = BisDeliveryLog::selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->where('user_id', $userId)
            ->where('status', 'sent')
            ->where('created_at', '>=', $base)
            ->groupBy('d')->orderBy('d')->get();

        return view('bis.analytics', [
            'days'         => $days,
            'deliveries'   => $deliveries,
            'recovered'    => $recovered,
            'top_products' => $topProducts,
            'sent_by_type' => $sentByType,
            'timeline'     => $timeline,
            'host'         => $request->query('host'),
        ]);
    }
}
