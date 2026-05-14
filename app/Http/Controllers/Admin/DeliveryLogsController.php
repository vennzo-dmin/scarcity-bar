<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BisDeliveryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryLogsController extends Controller
{
    public function index(Request $request)
    {
        $shop = Auth::user();

        $q = BisDeliveryLog::where('user_id', $shop->id);
        if ($status = $request->query('status'))   $q->where('status', $status);
        if ($channel = $request->query('channel')) $q->where('channel', $channel);

        $rows = $q->orderByDesc('id')->paginate(50)->withQueryString();

        return view('bis.delivery-logs', [
            'rows' => $rows,
            'host' => $request->query('host'),
        ]);
    }
}
