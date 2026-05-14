@extends('bis._layouts.app')

@section('bis-content')
    <div class="bis-header">
        <div>
            <h1 class="bis-title">Plans &amp; billing</h1>
            <div class="bis-subtitle">Same feature set — choose monthly flexibility or annual savings. Billed securely through Shopify.</div>
        </div>
    </div>

    <div class="bis-pricing-grid">
        @forelse($plans as $plan)
            @php
                $isCurrent = $currentPlan && (int) $currentPlan === (int) $plan->id;
                $intervalLabel = strtoupper((string) $plan->interval) === 'ANNUAL' ? '/year' : '/month';

                $features = [];

                if (!empty($plan->features)) {
                    $decoded = json_decode($plan->features, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                        $features = $decoded;
                    }
                }

                if (empty($features)) {
                    $features = [
                        'Per-variant scarcity bar + live counts',
                        'Sold-out “Notify me” capture & auto restock emails',
                        'Collection, search & homepage surfaces',
                        'Email + SMS via your own provider',
                        'Templates, translations & analytics',
                    ];

                    if (strtoupper((string) $plan->interval) === 'ANNUAL') {
                        $features[] = 'Lower effective rate vs monthly';
                    }
                }
            @endphp

            <div class="bis-plan-card">
                <div>
                    <div class="bis-kpi-label">{{ strtoupper($plan->name) }}</div>
                    <div class="bis-kpi-value">
                        ${{ number_format((float) $plan->price, 2) }}
                        <span class="bis-price-interval">{{ $intervalLabel }}</span>
                    </div>
                </div>

                @if($plan->terms)
                    <div class="bis-subtitle" style="margin:0;">{{ $plan->terms }}</div>
                @endif

                <ul class="bis-plan-features">
                    @foreach($features as $feature)
                        @if(is_array($feature))
                            <li>{{ $feature['label'] ?? $feature['name'] ?? json_encode($feature) }}</li>
                        @else
                            <li>{{ $feature }}</li>
                        @endif
                    @endforeach
                </ul>

                <div class="bis-plan-footer">
                    @if($isCurrent)
                        <span class="bis-badge bis-badge-active">Current plan</span>
                    @else
                        <a class="bis-btn bis-btn-primary"
                           href="{{ URL::tokenRoute('billing', ['plan' => $plan->id, 'shop' => Auth::user()->name]) }}">
                            <i class="bi bi-credit-card"></i>
                            {{ $currentPlan ? 'Switch to this plan' : 'Start ' . ($plan->trial_days ? $plan->trial_days . '-day free trial' : 'subscription') }}
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="bis-card">
                <div class="bis-subtitle" style="margin:0;">No plans configured. Seed the <code>plans</code> table to enable billing.</div>
            </div>
        @endforelse
    </div>

    <div class="bis-card">
        <div class="bis-card-title">What you get</div>
        <p class="bis-subtitle" style="margin:0;">
            Every tier includes the full Scarcity Bar experience on the storefront plus restock automation in the admin.
            Cancel any time; your subscription ends after the current billing period.
        </p>
    </div>
@endsection
