<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTranslationsRequest;
use App\Models\BisShopSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TranslationsController extends Controller
{
    public function index(Request $request)
    {
        $shop = Auth::user();
        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $shop->id],
            array_merge(['user_id' => $shop->id], BisShopSetting::defaults())
        );

        $defaults = BisShopSetting::translationDefaults();

        // Merge defaults beneath the saved values so newly added keys still
        // render with placeholder copy until the merchant overrides them.
        $current = array_replace($defaults, (array) ($settings->translations ?? []));

        return view('bis.translations', [
            'settings'     => $settings,
            'translations' => $current,
            'defaults'     => $defaults,
            'host'         => $request->query('host'),
        ]);
    }

    public function update(UpdateTranslationsRequest $request): JsonResponse
    {
        $shop = Auth::user();
        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $shop->id],
            array_merge(['user_id' => $shop->id], BisShopSetting::defaults())
        );

        $payload = (array) $request->input('translations', []);

        // Drop empty strings so a blank field falls back to the default copy
        // rather than rendering an empty label on the storefront.
        $payload = array_filter($payload, fn ($v) => $v !== null && $v !== '');

        $settings->translations = array_replace(
            (array) ($settings->translations ?? []),
            $payload
        );
        $settings->save();

        return response()->json(['ok' => true]);
    }
}
