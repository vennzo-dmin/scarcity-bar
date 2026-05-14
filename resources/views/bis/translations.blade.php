@extends('bis._layouts.app')

@php
    $tabs = [
        'scarcity' => [
            'label' => 'Scarcity bar',
            'fields' => [
                ['scarcity_label_in_stock', 'In-stock line', 'Use <code>{count}</code> for quantity.', false],
                ['scarcity_label_low_stock', 'Low-stock line', 'Use <code>{count}</code>.', false],
                ['scarcity_label_unknown', 'Unknown quantity', 'When quantity is hidden but item is purchasable.', false],
                ['scarcity_label_low_header', 'Low-stock header', 'Small label (e.g. “Low stock”).', false],
                ['scarcity_label_listing_sold_out', 'Collection / search — sold out', 'Short line on product cards when no purchasable variant (grids do not show the email form).', false],
            ],
        ],
        'notify' => [
            'label' => 'Sold out — signup',
            'fields' => [
                ['scarcity_notify_heading', 'Heading', 'Above the email form when sold out.', false],
                ['scarcity_notify_sub', 'Subtitle', 'Supporting line under the heading.', true],
                ['modal_heading_back_in_stock', 'Dialog title (if used)', 'For popup-style flows tied to restock.', false],
                ['button_label_back_in_stock', 'Primary button label', 'Shown on the restock call-to-action.', false],
                ['label_email', 'Email label', '', false],
                ['placeholder_email', 'Email placeholder', '', false],
                ['label_phone', 'Phone label', 'Only if “collect phone” is on.', false],
                ['placeholder_phone', 'Phone placeholder', '', false],
                ['consent_text', 'Consent text', 'Next to the consent checkbox.', true],
                ['submit_label', 'Submit button', '', false],
                ['footer_note', 'Footer note', 'Small text under the form.', false],
                ['success_message', 'Success message', 'After a successful signup.', true],
                ['error_message', 'Error message', 'Generic failure message.', true],
            ],
        ],
        'pricedrop' => [
            'label' => 'Price-drop signup',
            'fields' => [
                ['modal_heading_price_drop', 'Dialog / section title', 'When compare-at is above current price.', false],
                ['button_label_price_drop', 'Button / link label', 'Opens the dialog in button or link mode.', false],
                ['price_drop_notify_sub', 'Supporting copy', 'Short line under the price-drop title.', true],
            ],
        ],
        'validation' => [
            'label' => 'Validation',
            'fields' => [
                ['validation_email_required', 'Email required', '', false],
                ['validation_email_invalid', 'Email invalid', '', false],
                ['validation_phone_invalid', 'Phone invalid', '', false],
                ['validation_consent_required', 'Consent required', '', false],
            ],
        ],
    ];
@endphp

@section('bis-content')
    <div class="bis-header">
        <div>
            <h1 class="bis-title">Storefront copy</h1>
            <div class="bis-subtitle">Customer-facing text grouped by where it appears. Empty fields keep the default in the right column.</div>
        </div>
        <button type="button" class="bis-btn bis-btn-primary" id="bisSaveTranslations">
            <i class="bi bi-save"></i> Save
        </button>
    </div>

    <p class="bis-page-lead">Edit one group at a time so you do not lose context — strings still save together in one dictionary.</p>

    <form id="bisTranslationsForm" class="bis-form">
        <div class="bis-tabs" role="tablist" aria-label="Translation groups">
            @foreach($tabs as $tid => $tab)
                <button type="button" class="bis-tab {{ $loop->first ? 'active' : '' }}" data-bis-tab="{{ $tid }}">{{ $tab['label'] }}</button>
            @endforeach
        </div>

        <div class="bis-tab-panels">
            @foreach($tabs as $tid => $tab)
                <div class="bis-tab-panel {{ $loop->first ? 'active' : '' }}" data-bis-panel="{{ $tid }}">
                    <div class="bis-card">
                        <div class="bis-card-title">{{ $tab['label'] }}</div>
                        @foreach($tab['fields'] as [$name, $label, $hint, $multiline])
                            <div class="bis-translation-row">
                                <div>
                                    <label>{{ $label }}</label>
                                    @if($multiline)
                                        <textarea name="translations[{{ $name }}]" rows="2">{{ $translations[$name] ?? '' }}</textarea>
                                    @else
                                        <input type="text" name="translations[{{ $name }}]" value="{{ $translations[$name] ?? '' }}">
                                    @endif
                                    @if($hint !== '')
                                        <div class="bis-hint">{!! $hint !!}</div>
                                    @endif
                                </div>
                                <div>
                                    <label class="bis-label-muted">Default</label>
                                    <div class="bis-default-box">
                                        {{ $defaults[$name] ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('#bisTranslationsForm .bis-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-bis-tab');
            document.querySelectorAll('#bisTranslationsForm .bis-tab').forEach(function (b) {
                b.classList.toggle('active', b.getAttribute('data-bis-tab') === id);
            });
            document.querySelectorAll('#bisTranslationsForm .bis-tab-panel').forEach(function (p) {
                p.classList.toggle('active', p.getAttribute('data-bis-panel') === id);
            });
        });
    });

    document.getElementById('bisSaveTranslations').addEventListener('click', async function () {
        var form = document.getElementById('bisTranslationsForm');
        clearFormErrors(form);

        var payload = { translations: {} };
        new FormData(form).forEach(function (value, key) {
            var m = key.match(/^translations\[(\w+)\]$/);
            if (m) payload.translations[m[1]] = value;
        });

        try {
            await apiPost('{{ route('bis.translations.update') }}', payload, '{{ csrf_token() }}');
            showToast(app, 'Saved');
        } catch (e) {
            var shown = showFormErrors(form, e);
            showToast(app, shown ? 'Please fix the highlighted fields' : (e.message || 'Save failed'), true);
        }
    });
})();
</script>
@endpush
