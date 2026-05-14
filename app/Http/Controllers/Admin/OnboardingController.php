<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ShopifyHelper;
use App\Http\Controllers\Controller;
use App\Models\BisShopSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    public function index(Request $request)
    {
        $shop = Auth::user();
        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $shop->id],
            array_merge(['user_id' => $shop->id], BisShopSetting::defaults())
        );

        $details = ShopifyHelper::getShopifyStoreDetails();

        return view('bis.onboarding', [
            'settings' => $settings,
            'theme'    => $details,
            'host'     => $request->query('host'),
            'appHandle'=> config('bis.extension_handle', 'backinstock-widget'),
            'shopDomain' => $shop->name,
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $shop = Auth::user();
        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $shop->id],
            array_merge(['user_id' => $shop->id], BisShopSetting::defaults())
        );
        $settings->update(['onboarded' => true, 'is_enabled' => true]);
        return response()->json(['ok' => true]);
    }
}
