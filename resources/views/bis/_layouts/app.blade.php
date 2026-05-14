@extends('shopify-app::layouts.default')

@php
    $bisNav = [
        ['name' => 'Dashboard',     'route' => 'bis.dashboard',      'url' => '/dashboard',      'icon' => 'speedometer2'],
        ['name' => 'Widget',        'route' => 'bis.widget',         'url' => '/widget',         'icon' => 'ui-checks-grid'],
        ['name' => 'Templates',     'route' => 'bis.templates',      'url' => '/templates',      'icon' => 'envelope-paper'],
        ['name' => 'Translations',  'route' => 'bis.translations',   'url' => '/translations',   'icon' => 'translate'],
        ['name' => 'Notify list',   'route' => 'bis.subscriptions',  'url' => '/subscriptions',  'icon' => 'people'],
        ['name' => 'Automation',    'route' => 'bis.automation',     'url' => '/automation',     'icon' => 'magic'],
        ['name' => 'Integrations',  'route' => 'bis.providers',      'url' => '/providers',      'icon' => 'plug'],
        ['name' => 'Plans',         'route' => 'bis.plans',          'url' => '/plans',          'icon' => 'gem'],
        ['name' => 'User Guide',    'route' => 'bis.user_guide',     'url' => '/user-guide',     'icon' => 'book'],
        ['name' => 'Analytics',     'route' => 'bis.analytics',      'url' => '/analytics',      'icon' => 'graph-up'],
        ['name' => 'Delivery Logs', 'route' => 'bis.delivery_logs',  'url' => '/delivery-logs',  'icon' => 'list-ul'],
    ];
@endphp

@section('content')
<div class="bis-shell">
    <aside class="bis-sidebar" aria-label="Main navigation">
        <div class="bis-brand">
            <div class="bis-brand-mark" aria-hidden="true">
                <span class="bis-brand-glow"></span>
            </div>
            <div class="bis-brand-text">
                <span class="bis-brand-name">Scarcity Bar</span>
                <span class="bis-brand-tagline">Inventory &amp; restock</span>
            </div>
        </div>
        <nav class="bis-nav">
            @foreach($bisNav as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a class="bis-nav-item {{ $active ? 'active' : '' }}"
                   href="{{ $item['url'] }}"
                   data-bis-nav="1">
                    <span class="bis-nav-ico"><i class="bi bi-{{ $item['icon'] }}"></i></span>
                    <span class="bis-nav-label">{{ $item['name'] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="bis-sidebar-meta">
            <span class="bis-sidebar-meta-dot"></span>
            Embedded admin
        </div>
    </aside>
    <div class="bis-workspace">
        <main class="bis-main">
            @yield('bis-content')
        </main>
    </div>
</div>
@endsection

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<style>
:root {
    --sb-font: "Plus Jakarta Sans", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    --sb-ink: #0c111c;
    --sb-ink-soft: #3d4659;
    --sb-muted: #6b7380;
    --sb-line: rgba(15, 23, 42, 0.08);
    --sb-surface: #ffffff;
    --sb-canvas: #eef1f7;
    --sb-canvas2: #e4e9f4;
    --sb-accent: #6366f1;
    --sb-accent2: #8b5cf6;
    --sb-accent-hot: #f97316;
    --sb-accent-hot2: #ea580c;
    --sb-danger: #e11d48;
    --sb-success: #059669;
    --sb-sidebar: #0a0e17;
    --sb-sidebar2: #121a2a;
    --sb-sidebar-line: rgba(255,255,255,.06);
    --sb-sidebar-text: #e8ecf8;
    --sb-sidebar-muted: #8b95ae;
    --sb-radius: 16px;
    --sb-radius-sm: 10px;
    --sb-shadow: 0 4px 24px rgba(12, 17, 28, 0.06);
    --sb-shadow-lg: 0 20px 50px rgba(12, 17, 28, 0.12);

    /* Legacy token names — mapped so existing blades keep working */
    --bis-bg: var(--sb-canvas);
    --bis-surface: var(--sb-surface);
    --bis-border: var(--sb-line);
    --bis-text: var(--sb-ink);
    --bis-muted: var(--sb-muted);
    --bis-primary: var(--sb-accent);
    --bis-primary-hover: #4f46e5;
    --bis-danger: var(--sb-danger);
    --bis-radius: var(--sb-radius);
    --bis-shadow: var(--sb-shadow);
}

html, body { font-family: var(--sb-font); }
body { background: var(--sb-canvas); color: var(--sb-ink); }
.app-wrapper, .app-content, main[role="main"] { background: transparent !important; }

.bis-shell {
    display: flex;
    min-height: 100vh;
    color: var(--sb-ink);
}

.bis-sidebar {
    width: 268px;
    flex-shrink: 0;
    background: linear-gradient(180deg, var(--sb-sidebar) 0%, var(--sb-sidebar2) 100%);
    border-right: 1px solid var(--sb-sidebar-line);
    padding: 22px 14px 20px;
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 0;
    align-self: flex-start;
    min-height: 100vh;
}

.bis-brand {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 6px 10px 22px;
    margin-bottom: 8px;
    border-bottom: 1px solid var(--sb-sidebar-line);
}

.bis-brand-mark {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--sb-accent) 0%, var(--sb-accent2) 55%, var(--sb-accent-hot) 100%);
    position: relative;
    flex-shrink: 0;
    box-shadow: 0 8px 28px rgba(99, 102, 241, 0.45);
}
.bis-brand-glow {
    position: absolute;
    inset: 10px;
    border-radius: 10px;
    background: rgba(255,255,255,.18);
    backdrop-filter: blur(4px);
}

.bis-brand-text { min-width: 0; }
.bis-brand-name {
    display: block;
    font-weight: 700;
    font-size: 17px;
    letter-spacing: -0.03em;
    color: #fff;
    line-height: 1.2;
}
.bis-brand-tagline {
    display: block;
    font-size: 11px;
    font-weight: 500;
    color: var(--sb-sidebar-muted);
    margin-top: 3px;
    letter-spacing: 0.02em;
}

.bis-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}

.bis-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 12px;
    border-radius: 12px;
    color: var(--sb-sidebar-text);
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid transparent;
    transition: background .15s, border-color .15s, color .15s;
}
.bis-nav-item:hover {
    background: rgba(255,255,255,.06);
    color: #fff;
}
.bis-nav-item.active {
    background: rgba(99, 102, 241, 0.22);
    border-color: rgba(129, 140, 248, 0.35);
    color: #fff;
    font-weight: 600;
}
.bis-nav-ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,.06);
    color: #c7d2fe;
    flex-shrink: 0;
}
.bis-nav-item.active .bis-nav-ico {
    background: rgba(99, 102, 241, 0.35);
    color: #eef2ff;
}
.bis-nav-ico i { font-size: 16px; }
.bis-nav-label { line-height: 1.25; }

.bis-sidebar-meta {
    margin-top: auto;
    padding: 14px 12px 4px;
    font-size: 11px;
    color: var(--sb-sidebar-muted);
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    font-weight: 600;
}
.bis-sidebar-meta-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--sb-success);
    box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.25);
}

.bis-workspace {
    flex: 1;
    min-width: 0;
    background:
        radial-gradient(900px 420px at 12% -10%, rgba(99, 102, 241, 0.09), transparent 55%),
        radial-gradient(700px 380px at 88% 0%, rgba(249, 115, 22, 0.07), transparent 50%),
        linear-gradient(180deg, var(--sb-canvas) 0%, var(--sb-canvas2) 100%);
}

.bis-main {
    max-width: 1180px;
    margin: 0 auto;
    padding: 28px 28px 48px;
}

.bis-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 26px;
    flex-wrap: wrap;
}
.bis-title {
    font-size: 28px;
    font-weight: 700;
    letter-spacing: -0.04em;
    margin: 0;
    color: var(--sb-ink);
    line-height: 1.15;
}
.bis-subtitle {
    color: var(--sb-muted);
    font-size: 14px;
    margin-top: 8px;
    line-height: 1.55;
    max-width: 640px;
}

.bis-header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.bis-header-actions--center { align-items: center; }
.bis-meta-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 14px;
    background: var(--sb-surface);
    border: 1px solid var(--sb-line);
    border-radius: 999px;
    box-shadow: var(--sb-shadow);
}
.bis-meta-toggle span {
    font-size: 13px;
    font-weight: 600;
    color: var(--sb-ink-soft);
}

.bis-card {
    background: var(--sb-surface);
    border-radius: var(--sb-radius);
    border: 1px solid var(--sb-line);
    box-shadow: var(--sb-shadow);
    padding: 22px 24px;
    margin-bottom: 20px;
}
.bis-card-title {
    font-size: 16px;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0 0 14px;
    color: var(--sb-ink);
}

.bis-card--spotlight {
    border-color: rgba(99, 102, 241, 0.35);
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.06) 0%, #fff 42%, #fff 100%);
    box-shadow: var(--sb-shadow-lg);
}
.bis-card--spotlight .bis-card-title {
    display: flex;
    align-items: center;
    gap: 10px;
}
.bis-card--spotlight .bis-card-title i {
    color: var(--sb-accent);
    font-size: 1.15em;
}
.bis-card-spotlight-row {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: flex-start;
    flex-wrap: wrap;
}
.bis-card-spotlight-body { flex: 1; min-width: 240px; }

.bis-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 22px;
}
.bis-kpi {
    background: var(--sb-surface);
    border-radius: var(--sb-radius);
    border: 1px solid var(--sb-line);
    padding: 18px 20px;
    box-shadow: var(--sb-shadow);
    position: relative;
    overflow: hidden;
}
.bis-kpi::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    border-radius: 4px 0 0 4px;
    background: linear-gradient(180deg, var(--sb-accent), var(--sb-accent2));
}
.bis-kpi-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--sb-muted);
}
.bis-kpi-value {
    font-size: 30px;
    font-weight: 700;
    margin-top: 8px;
    letter-spacing: -0.03em;
    color: var(--sb-ink);
    line-height: 1.1;
}

.bis-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    border: 1px solid var(--sb-line);
    background: var(--sb-surface);
    color: var(--sb-ink);
    text-decoration: none;
    transition: transform .12s, box-shadow .15s, border-color .15s, background .15s;
}
.bis-btn:hover {
    background: #f8fafc;
    border-color: rgba(99, 102, 241, 0.25);
    box-shadow: 0 4px 14px rgba(12, 17, 28, 0.06);
}
.bis-btn:active { transform: translateY(1px); }

.bis-btn-primary {
    border: none;
    color: #fff;
    background: linear-gradient(135deg, var(--sb-accent) 0%, #4f46e5 50%, var(--sb-accent-hot) 160%);
    box-shadow: 0 8px 22px rgba(99, 102, 241, 0.35);
}
.bis-btn-primary:hover {
    color: #fff;
    background: linear-gradient(135deg, #4f46e5 0%, var(--sb-accent) 55%, #6366f1 100%);
    box-shadow: 0 10px 28px rgba(99, 102, 241, 0.42);
}
.bis-btn-primary:hover i {
    color: #fff;
}
.bis-btn-danger {
    color: var(--sb-danger);
    border-color: rgba(225, 29, 72, 0.35);
    background: rgba(225, 29, 72, 0.06);
}
.bis-btn-danger:hover {
    background: rgba(225, 29, 72, 0.1);
    color: #be123c;
}

.bis-btn-sm {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 10px;
    gap: 6px;
}

.bis-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    align-items: flex-end;
}
.bis-toolbar > div { flex: 1; min-width: 160px; }
.bis-toolbar .bis-btn { flex-shrink: 0; }

.bis-form label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--sb-muted);
    margin-bottom: 6px;
}
.bis-form input,
.bis-form select,
.bis-form textarea {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid var(--sb-line);
    border-radius: var(--sb-radius-sm);
    font-size: 14px;
    background: #fafbfe;
    color: var(--sb-ink);
    transition: border-color .15s, box-shadow .15s, background .15s;
}
.bis-form input[type="color"] {
    height: 44px;
    padding: 4px 6px;
    cursor: pointer;
    max-width: 100%;
}
.bis-form input:focus,
.bis-form select:focus,
.bis-form textarea:focus {
    outline: none;
    border-color: rgba(99, 102, 241, 0.55);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
}
.bis-form .row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}
.bis-form .bis-form-full {
    grid-column: 1 / -1;
}
.bis-form input.is-invalid,
.bis-form select.is-invalid,
.bis-form textarea.is-invalid {
    border-color: var(--sb-danger);
    box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.1);
}

.bis-field-error { color: var(--sb-danger); font-size: 12px; margin-top: 4px; line-height: 1.4; }

.bis-table-wrap { overflow-x: auto; margin: 0 -4px; }
.bis-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13px;
}
.bis-table th,
.bis-table td {
    padding: 13px 14px;
    text-align: left;
    border-bottom: 1px solid var(--sb-line);
    vertical-align: middle;
}
.bis-table th {
    font-weight: 700;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--sb-muted);
    background: #f4f6fb;
}
.bis-table tbody tr:hover td { background: rgba(99, 102, 241, 0.04); }
.bis-table a {
    color: var(--sb-accent);
    font-weight: 600;
    text-decoration: none;
}
.bis-table a:hover { text-decoration: underline; }

.bis-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.bis-badge-active { background: rgba(99, 102, 241, 0.14); color: #4338ca; }
.bis-badge-sent { background: rgba(14, 165, 233, 0.14); color: #0369a1; }
.bis-badge-queued { background: rgba(249, 115, 22, 0.15); color: #c2410c; }
.bis-badge-failed { background: rgba(225, 29, 72, 0.12); color: #be123c; }
.bis-badge-bounced { background: rgba(234, 179, 8, 0.18); color: #a16207; }
.bis-badge-cancelled { background: rgba(100, 116, 139, 0.15); color: #475569; }

.bis-text-danger { color: var(--sb-danger); }
.bis-text-small { font-size: 12px; }

.bis-switch { position: relative; display: inline-block; width: 46px; height: 26px; }
.bis-switch input { opacity: 0; width: 0; height: 0; }
.bis-slider {
    position: absolute;
    inset: 0;
    background: #cbd5e1;
    border-radius: 999px;
    transition: .22s;
    cursor: pointer;
}
.bis-slider:before {
    content: "";
    position: absolute;
    height: 20px;
    width: 20px;
    left: 3px;
    top: 3px;
    background: #fff;
    border-radius: 50%;
    transition: .22s;
    box-shadow: 0 2px 6px rgba(0,0,0,.12);
}
.bis-switch input:checked + .bis-slider {
    background: linear-gradient(90deg, var(--sb-accent), var(--sb-accent2));
}
.bis-switch input:checked + .bis-slider:before { transform: translateX(20px); }

.bis-hint { font-size: 13px; color: var(--sb-muted); margin-top: 6px; line-height: 1.5; }
.bis-hint-spaced { margin-bottom: 14px; margin-top: 0; }
.bis-section-title {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--sb-muted);
    margin: 22px 0 10px;
}

.bis-inline-field {
    display: flex;
    gap: 10px;
    align-items: center;
}
.bis-inline-field select {
    width: auto;
    min-width: 160px;
}
.bis-form.bis-inline-field {
    flex-direction: row;
    flex-wrap: wrap;
    align-items: center;
    margin: 0;
}
.bis-form.bis-inline-field select {
    min-width: 200px;
}

.bis-sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.bis-pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 18px;
}
.bis-plan-card {
    display: flex;
    flex-direction: column;
    gap: 14px;
    background: var(--sb-surface);
    border-radius: var(--sb-radius);
    border: 1px solid var(--sb-line);
    padding: 22px 22px 20px;
    box-shadow: var(--sb-shadow);
    position: relative;
    overflow: hidden;
}
.bis-plan-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 5px;
    background: linear-gradient(90deg, var(--sb-accent), var(--sb-accent-hot));
}
.bis-plan-card ul.bis-plan-features { margin: 0; padding-left: 18px; color: var(--sb-muted); line-height: 1.75; font-size: 13px; }
.bis-plan-card .bis-kpi-value { font-size: 32px; }
.bis-plan-card .bis-kpi-label { font-size: 12px; letter-spacing: 0.06em; }
.bis-price-interval {
    font-size: 15px;
    color: var(--sb-muted);
    font-weight: 600;
    margin-left: 4px;
}
.bis-plan-footer { margin-top: auto; padding-top: 4px; }

.bis-pagination { margin-top: 18px; }
.bis-pagination nav,
.bis-pagination ul {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
    list-style: none;
    padding: 0;
    margin: 0;
}
.bis-pagination a,
.bis-pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    height: 38px;
    padding: 0 10px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid var(--sb-line);
    background: var(--sb-surface);
    color: var(--sb-ink);
    text-decoration: none;
}
.bis-pagination a:hover { border-color: var(--sb-accent); color: var(--sb-accent); }
.bis-pagination span[aria-current="page"] {
    background: var(--sb-accent);
    color: #fff;
    border-color: transparent;
}

.bis-page-lead {
    font-size: 15px;
    color: var(--sb-muted);
    max-width: 720px;
    line-height: 1.6;
    margin: -8px 0 22px;
}

.bis-translation-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
    margin-bottom: 22px;
}
.bis-label-muted {
    color: var(--sb-muted) !important;
}
.bis-default-box {
    padding: 11px 14px;
    border: 1px dashed var(--sb-line);
    border-radius: var(--sb-radius-sm);
    background: #f4f6fb;
    font-size: 13px;
    color: var(--sb-muted);
    min-height: 44px;
    line-height: 1.45;
}

.bis-hint-tight { margin-top: 14px; }
.bis-mb-0 { margin-bottom: 0 !important; }
.bis-mt-step { margin-top: 18px; }
.bis-list-prose {
    margin: 0;
    padding-left: 22px;
    line-height: 1.85;
    color: var(--sb-muted);
    font-size: 14px;
}
.bis-list-prose li { margin-bottom: 0.35em; }

@media (max-width: 800px) {
    .bis-translation-row { grid-template-columns: 1fr; }
}

@media (max-width: 960px) {
    .bis-shell { flex-direction: column; }
    .bis-sidebar {
        width: 100%;
        min-height: 0;
        position: relative;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        padding: 16px;
    }
    .bis-brand { border-bottom: none; margin-bottom: 0; padding: 0 8px 0 0; flex: 1; min-width: 200px; }
    .bis-nav {
        flex: 1 1 100%;
        flex-direction: row;
        flex-wrap: wrap;
        margin-top: 12px;
        gap: 6px;
    }
    .bis-nav-item { flex: 1 1 auto; justify-content: center; min-width: 120px; }
    .bis-sidebar-meta { display: none; }
    .bis-main { padding: 20px 16px 40px; }
}

.bis-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
}
.bis-tab {
    border: 1px solid var(--sb-line);
    background: var(--sb-surface);
    color: var(--sb-muted);
    padding: 8px 14px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: color .15s, border-color .15s, background .15s;
}
.bis-tab:hover {
    color: var(--sb-ink);
    border-color: rgba(99, 102, 241, 0.35);
}
.bis-tab.active {
    color: #fff;
    background: linear-gradient(135deg, var(--sb-accent) 0%, #4f46e5 100%);
    border-color: transparent;
}
.bis-tab.active:hover {
    color: #fff;
}
.bis-tab-panels > .bis-tab-panel {
    display: none;
}
.bis-tab-panels > .bis-tab-panel.active {
    display: block;
}
.bis-target-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
}
.bis-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-height: 28px;
    margin-top: 8px;
}
.bis-chip {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    background: rgba(99, 102, 241, 0.12);
    color: var(--sb-ink);
    border: 1px solid rgba(99, 102, 241, 0.2);
    max-width: min(100%, 380px);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.bis-details-toggle {
    margin-top: 10px;
    font-size: 13px;
    font-weight: 600;
    color: var(--sb-accent);
    cursor: pointer;
    user-select: none;
}
.bis-details-toggle-btn {
    border: none;
    background: transparent;
    padding: 0;
    width: 100%;
    text-align: left;
    font: inherit;
    cursor: pointer;
}
.bis-manual-ids {
    margin-top: 12px;
    display: none;
}
.bis-manual-ids.is-open {
    display: block;
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    document.querySelectorAll('a[data-bis-nav="1"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var href = a.getAttribute('href');
            if (typeof navigation === 'function') {
                navigation(href);
            } else {
                window.top.location.href = href;
            }
        });
    });
})();
</script>
@endpush
