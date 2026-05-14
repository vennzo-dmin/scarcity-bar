<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BisAutomationRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutomationController extends Controller
{
    public function index(Request $request)
    {
        $shop = Auth::user();
        $rules = BisAutomationRule::where('user_id', $shop->id)->orderBy('id')->get();

        return view('bis.automation', [
            'rules' => $rules,
            'host'  => $request->query('host'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validate($request, [
            'id'        => ['nullable', 'integer'],
            'name'      => ['required', 'string', 'max:120'],
            'type'      => ['required', 'in:low_stock,high_demand,internal_notify,export'],
            'is_active' => ['nullable', 'boolean'],
            'config'    => ['nullable', 'array'],
        ]);

        $shop = Auth::user();
        $payload = [
            'user_id'   => $shop->id,
            'name'      => $data['name'],
            'type'      => $data['type'],
            'is_active' => (bool)($data['is_active'] ?? true),
            'config'    => $data['config'] ?? [],
        ];

        if (!empty($data['id'])) {
            $rule = BisAutomationRule::where('user_id', $shop->id)->where('id', $data['id'])->first();
            if ($rule) {
                $rule->update($payload);
            } else {
                $rule = BisAutomationRule::create($payload);
            }
        } else {
            $rule = BisAutomationRule::create($payload);
        }

        return response()->json(['ok' => true, 'id' => $rule->id]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $shop = Auth::user();
        BisAutomationRule::where('user_id', $shop->id)->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
