<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Notifications\NotificationEngine;
use App\Domain\Notifications\NotificationPayload;
use App\Domain\Notifications\ProviderResolver;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProvidersRequest;
use App\Models\BisShopSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProvidersController extends Controller
{
    public function index(Request $request)
    {
        $shop = Auth::user();
        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $shop->id],
            array_merge(['user_id' => $shop->id], BisShopSetting::defaults())
        );

        $providers = array_replace_recursive(
            BisShopSetting::defaults()['providers'],
            $settings->providers ?? []
        );

        // Mask secrets before handing to the view so they don't leak into the DOM.
        $masked = $providers;
        if (!empty($masked['email']['smtp_pass'])) {
            $masked['email']['smtp_pass'] = '********';
        }
        if (!empty($masked['sms']['twilio']['auth_token'])) {
            $masked['sms']['twilio']['auth_token'] = '********';
        }
        if (!empty($masked['sms']['messagebird']['api_key'])) {
            $masked['sms']['messagebird']['api_key'] = '********';
        }

        return view('bis.providers', [
            'settings'  => $settings,
            'providers' => $masked,
            'host'      => $request->query('host'),
        ]);
    }

    public function update(UpdateProvidersRequest $request): JsonResponse
    {
        $shop = Auth::user();
        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $shop->id],
            array_merge(['user_id' => $shop->id], BisShopSetting::defaults())
        );

        $current = array_replace_recursive(
            BisShopSetting::defaults()['providers'],
            $settings->providers ?? []
        );

        $incomingEmail = $request->input('email', []);
        $incomingSms   = $request->input('sms', []);

        // Preserve existing secrets when submitted value is the mask placeholder or empty-string blank-out.
        if (($incomingEmail['smtp_pass'] ?? null) === '********') {
            unset($incomingEmail['smtp_pass']);
        }
        if (($incomingSms['twilio']['auth_token'] ?? null) === '********') {
            unset($incomingSms['twilio']['auth_token']);
        }
        if (($incomingSms['messagebird']['api_key'] ?? null) === '********') {
            unset($incomingSms['messagebird']['api_key']);
        }

        $merged = $current;
        $merged['email'] = array_replace($current['email'] ?? [], $incomingEmail);
        $merged['sms']   = array_replace_recursive($current['sms'] ?? [], $incomingSms);

        $settings->providers = $merged;
        $settings->save();

        return response()->json(['ok' => true]);
    }

    public function test(Request $request, ProviderResolver $resolver): JsonResponse
    {
        $request->validate([
            'channel' => ['required', 'in:email,sms'],
            'to'      => ['required', 'string', 'max:190'],
            'config'  => ['nullable', 'array'],
        ]);

        $shop = Auth::user();
        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $shop->id],
            array_merge(['user_id' => $shop->id], BisShopSetting::defaults())
        );

        // Let the merchant test the LIVE form values without having to
        // click Save first. The front-end ships the serialized form as
        // `config`; we fold it into a throwaway settings clone, merge the
        // masked secrets back from the saved record (so re-entering the
        // password isn't required just to run a test), then resolve a
        // provider from the merged result.
        if ($request->filled('config')) {
            $live  = (array) $request->input('config');
            $saved = (array) ($settings->providers ?? []);

            $live = $this->unmaskSecrets($live, $saved);

            $ephemeral = clone $settings;
            $ephemeral->providers = array_replace_recursive($saved, $live);
            $provider = $resolver->resolve($ephemeral, $request->input('channel'));
        } else {
            $provider = $resolver->resolve($settings, $request->input('channel'));
        }

        if (!$provider) {
            return response()->json(['ok' => false, 'error' => 'no_provider_registered'], 422);
        }

        $payload = new NotificationPayload(
            channel: $request->input('channel'),
            to: $request->input('to'),
            subject: 'Test message from Back-in-Stock Notifier',
            body: 'This is a test notification from your Back-in-Stock Notifier app. If you received this, your provider is configured correctly.',
            context: ['test' => true],
        );

        $result = $provider->send($payload);

        if (!$result->ok) {
            // Provider-side rejections (550 sender suspended, auth denied,
            // SPF/DKIM verification required, etc.) aren't bugs in our
            // code — log the full message so the merchant / support can
            // paste it to their ESP.
            Log::channel(config('bis.log_channel', 'stack'))
                ->warning('[BIS][Test] provider rejected', [
                    'shop_id'  => $shop->id,
                    'channel'  => $request->input('channel'),
                    'provider' => $provider->name(),
                    'error'    => $result->error,
                ]);
        }

        return response()->json([
            'ok'       => $result->ok,
            'provider' => $provider->name(),
            'error'    => $result->error,
            'id'       => $result->providerMessageId,
        ], $result->ok ? 200 : 422);
    }

    /**
     * Reuse the mask-preserving logic from update() for the test endpoint:
     * if the merchant didn't change a masked secret, fall back to the saved
     * value so they don't have to paste the SMTP password / API token just
     * to run a test.
     */
    private function unmaskSecrets(array $live, array $saved): array
    {
        $maskMap = [
            ['email', 'smtp_pass'],
            ['sms', 'twilio', 'auth_token'],
            ['sms', 'messagebird', 'api_key'],
        ];
        foreach ($maskMap as $path) {
            if (data_get($live, implode('.', $path)) === '********') {
                data_set($live, implode('.', $path), data_get($saved, implode('.', $path)));
            }
        }
        return $live;
    }
}
