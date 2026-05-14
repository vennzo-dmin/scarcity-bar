<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWidgetSettingsRequest;
use App\Models\BisShopSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WidgetSettingsController extends Controller
{
    public function index(Request $request)
    {
        $shop = Auth::user();
        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $shop->id],
            array_merge(['user_id' => $shop->id], BisShopSetting::defaults())
        );

        return view('bis.widget-settings', [
            'settings' => $settings,
            'host'     => $request->query('host'),
        ]);
    }

    public function update(UpdateWidgetSettingsRequest $request): JsonResponse
    {
        $shop = Auth::user();
        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $shop->id],
            array_merge(['user_id' => $shop->id], BisShopSetting::defaults())
        );

        $widget = $request->input('widget', []);
        foreach (['target_product_ids', 'target_collection_ids'] as $idKey) {
            if (!array_key_exists($idKey, $widget)) {
                continue;
            }
            $raw = $widget[$idKey];
            if (is_int($raw) || is_float($raw)) {
                $widget[$idKey] = array_values(array_unique(array_filter([(int) $raw])));
            } elseif (is_string($raw)) {
                $widget[$idKey] = array_values(array_unique(array_filter(array_map(
                    static fn ($v) => (int) $v,
                    preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: []
                ))));
            } elseif (is_array($raw)) {
                $widget[$idKey] = array_values(array_unique(array_filter(array_map('intval', $raw))));
            }
        }

        $merged = array_merge($settings->widget ?? [], $widget);
        $merged['target_product_labels'] = $this->normalizeTargetLabels(
            $merged['target_product_labels'] ?? [],
            array_map('intval', (array) ($merged['target_product_ids'] ?? []))
        );
        $merged['target_collection_labels'] = $this->normalizeTargetLabels(
            $merged['target_collection_labels'] ?? [],
            array_map('intval', (array) ($merged['target_collection_ids'] ?? []))
        );
        $settings->widget = $merged;
        $settings->inventory = array_merge($settings->inventory ?? [], $request->input('inventory', []));
        $settings->pricing   = array_merge($settings->pricing ?? [], $request->input('pricing', []));
        $settings->consent   = array_merge($settings->consent ?? [], $request->input('consent', []));
        $settings->sending   = array_merge($settings->sending ?? [], $request->input('sending', []));
        $settings->save();

        return response()->json(['ok' => true]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $shop = Auth::user();
        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $shop->id],
            array_merge(['user_id' => $shop->id], BisShopSetting::defaults())
        );

        $settings->is_enabled = (bool)$request->boolean('enabled');
        $settings->save();

        return response()->json(['ok' => true, 'enabled' => $settings->is_enabled]);
    }

    /**
     * @param  mixed  $raw  JSON string or array from the admin form
     * @param  int[]  $allowedIds
     * @return array<string, string>
     */
    private function normalizeTargetLabels(mixed $raw, array $allowedIds): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            return [];
        }
        $allowed = array_flip(array_map('intval', $allowedIds));
        $out = [];
        foreach ($raw as $k => $v) {
            $id = (int) $k;
            if (! isset($allowed[$id])) {
                continue;
            }
            $title = is_string($v) ? trim($v) : '';
            if ($title === '') {
                continue;
            }
            $out[(string) $id] = mb_substr($title, 0, 200);
        }

        return $out;
    }
}
