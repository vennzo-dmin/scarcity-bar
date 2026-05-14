@extends('bis._layouts.app')

@section('bis-content')
    <div class="bis-header">
        <div>
            <h1 class="bis-title">Dashboard</h1>
            <div class="bis-subtitle">Live demand, notification sends, and revenue tied to your restock emails.</div>
        </div>
        <div class="bis-header-actions">
            <a class="bis-btn" onclick="navigation('/user-guide')">
                <i class="bi bi-book"></i> User guide
            </a>
            <div class="bis-meta-toggle">
                <span>Storefront widget</span>
                <label class="bis-switch">
                    <input type="checkbox" id="bisEnabledToggle" {{ $settings->is_enabled ? 'checked' : '' }}>
                    <span class="bis-slider"></span>
                </label>
            </div>
        </div>
    </div>

    <div class="bis-card bis-card--spotlight">
        <div class="bis-card-spotlight-row">
            <div class="bis-card-spotlight-body">
                <div class="bis-card-title">
                    <i class="bi bi-lightning-charge-fill"></i>
                    Activate on your storefront
                </div>
                <p class="bis-subtitle bis-mb-0">
                    Turn on the <strong>Scarcity Bar</strong> app embed in your live theme so shoppers see per-variant stock,
                    urgency messaging, and the sold-out email capture. Nothing appears until the embed is enabled.
                </p>
            </div>
            <div class="bis-header-actions bis-header-actions--center">
                @if($themeEmbedUrl)
                    <a class="bis-btn bis-btn-primary" target="_blank" rel="noopener noreferrer" href="{{ $themeEmbedUrl }}">
                        <i class="bi bi-box-arrow-up-right"></i> Open theme editor
                    </a>
                @else
                    @php $storeHandle = preg_replace('/\.myshopify\.com$/i', '', (string) Auth::user()->name); @endphp
                    <a class="bis-btn bis-btn-primary" target="_blank" rel="noopener noreferrer" href="https://admin.shopify.com/store/{{ $storeHandle }}/themes">
                        <i class="bi bi-box-arrow-up-right"></i> Open themes
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="bis-kpis">
        <div class="bis-kpi">
            <div class="bis-kpi-label">Subscribers</div>
            <div class="bis-kpi-value">{{ number_format($stats['subscribers']) }}</div>
        </div>
        <div class="bis-kpi">
            <div class="bis-kpi-label">Active alerts</div>
            <div class="bis-kpi-value">{{ number_format($stats['active_alerts']) }}</div>
        </div>
        <div class="bis-kpi">
            <div class="bis-kpi-label">Sent (30d)</div>
            <div class="bis-kpi-value">{{ number_format($stats['sent_30d']) }}</div>
        </div>
        <div class="bis-kpi">
            <div class="bis-kpi-label">Recovered revenue (30d)</div>
            <div class="bis-kpi-value">${{ number_format((float)$stats['recovered_30d'], 2) }}</div>
        </div>
    </div>

    <div class="bis-card">
        <div class="bis-card-title">Top demanded products &amp; variants</div>
        @if($stats['top_products']->isEmpty())
            <p class="bis-subtitle bis-mb-0">No demand data yet. Enable the widget to start capturing signups.</p>
        @else
            <div class="bis-table-wrap">
                <table class="bis-table">
                    <thead>
                        <tr><th>Product</th><th>Variant</th><th>Demand</th></tr>
                    </thead>
                    <tbody>
                    @foreach($stats['top_products'] as $row)
                        <tr>
                            <td>{{ $row->product_title }}</td>
                            <td>{{ $row->variant_title && $row->variant_title !== 'Default Title' ? $row->variant_title : '—' }}</td>
                            <td><span class="bis-badge bis-badge-active">{{ $row->demand }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="bis-card">
        <div class="bis-card-title">Quick start</div>
        <ol class="bis-list-prose">
            <li>Activate the app embed in your theme (card above).</li>
            <li>Toggle <strong>Storefront widget</strong> on in the header.</li>
            <li>Fine-tune the bar, colors, and targeting under <strong>Widget</strong>.</li>
            <li>Edit restock email copy under <strong>Templates</strong> / <strong>Translations</strong>.</li>
            <li>Test a multi-variant product — the bar should update as you change options.</li>
            <li>Sold-out variants should show the email capture form automatically.</li>
        </ol>
        <div class="bis-mt-step">
            <a class="bis-btn bis-btn-primary" onclick="navigation('/onboarding')">
                <i class="bi bi-rocket-takeoff"></i> Onboarding walkthrough
            </a>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('bisEnabledToggle').addEventListener('change', async (e) => {
        try {
            await apiPost('{{ route('bis.widget.toggle') }}', { enabled: e.target.checked }, '{{ csrf_token() }}');
            showToast(app, e.target.checked ? 'Widget enabled' : 'Widget disabled');
        } catch (err) {
            showToast(app, 'Failed to update', true);
            e.target.checked = !e.target.checked;
        }
    });
</script>
@endpush
