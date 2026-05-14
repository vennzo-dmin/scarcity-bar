<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlansController extends Controller
{
    public function index(Request $request)
    {
        $shop = Auth::user();

        $plans = DB::table('plans')
            ->orderBy('id')
            ->get();

        $currentPlan = $shop?->plan_id;

        return view('bis.plans', [
            'plans'       => $plans,
            'currentPlan' => $currentPlan,
            'shopDomain'  => $shop?->getDomain()?->toNative(),
            'host'        => $request->query('host'),
        ]);
    }
}
