<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BisNotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $shop = Auth::user();
        $templates = BisNotificationTemplate::where('user_id', $shop->id)->get();

        return view('bis.templates', [
            'templates' => $templates,
            'host'      => $request->query('host'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validate($request, [
            'type'    => ['required', 'in:back_in_stock,price_drop'],
            'channel' => ['required', 'in:email,sms'],
            'locale'  => ['required', 'string', 'max:10'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body'    => ['required', 'string', 'max:20000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $shop = Auth::user();

        $tpl = BisNotificationTemplate::updateOrCreate(
            [
                'user_id' => $shop->id,
                'type'    => $data['type'],
                'channel' => $data['channel'],
                'locale'  => $data['locale'],
            ],
            [
                'subject'   => $data['subject'] ?? null,
                'body'      => $data['body'],
                'is_active' => (bool)($data['is_active'] ?? true),
            ]
        );

        return response()->json(['ok' => true, 'id' => $tpl->id]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $shop = Auth::user();
        BisNotificationTemplate::where('user_id', $shop->id)->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
