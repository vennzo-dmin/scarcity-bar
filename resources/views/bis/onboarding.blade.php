@extends('bis._layouts.app')

@section('bis-content')
    <div class="bis-header">
        <div>
            <h1 class="bis-title">Welcome</h1>
            <div class="bis-subtitle">Let's get your restock alerts live in a few steps.</div>
        </div>
    </div>

    <div class="bis-card">
        <div class="bis-card-title">1. Enable the app embed in your theme</div>
        <p class="bis-subtitle">Open your theme editor and turn on the <strong>Scarcity Bar</strong> app embed. This delivers the widget on your storefront without editing theme code.</p>
        @php $storeHandle = preg_replace('/\.myshopify\.com$/i', '', (string) $shopDomain); @endphp
        <a class="bis-btn bis-btn-primary" target="_blank" rel="noopener noreferrer"
           href="https://admin.shopify.com/store/{{ $storeHandle }}/themes/{{ $theme['themeId'] ?? 'current' }}/editor?context=apps">
            <i class="bi bi-box-arrow-up-right"></i> Open theme editor
        </a>
    </div>

    <div class="bis-card">
        <div class="bis-card-title">2. Customize your widget</div>
        <p class="bis-subtitle">Configure the scarcity bar, thresholds, colors, and where it appears (product, collection, home, search).</p>
        <a class="bis-btn"
           onclick="navigation('/widget')">Configure widget</a>
    </div>

    <div class="bis-card">
        <div class="bis-card-title">3. Review your templates</div>
        <p class="bis-subtitle">Restock and price-drop emails ship with defaults — edit anytime.</p>
        <a class="bis-btn"
           onclick="navigation('/templates')">Edit templates</a>
    </div>

    <div class="bis-card">
        <div class="bis-card-title">4. Go live</div>
        <p class="bis-subtitle">Mark onboarding complete and open the dashboard.</p>
        <button class="bis-btn bis-btn-primary" id="bisCompleteOnboarding">Finish onboarding</button>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('bisCompleteOnboarding').addEventListener('click', async () => {
        try {
            await apiPost('{{ route('bis.onboarding.complete') }}', {}, '{{ csrf_token() }}');
            showToast(app, 'Onboarding complete!');
            navigation('');
        } catch (e) {
            showToast(app, 'Could not complete', true);
        }
    });
</script>
@endpush
