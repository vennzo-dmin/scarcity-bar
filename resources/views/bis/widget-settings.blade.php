@extends('bis._layouts.app')

@php
    $w = $settings->widget ?? [];
    $inv = $settings->inventory ?? [];
    $send = $settings->sending ?? [];
    $pids = isset($w['target_product_ids']) && is_array($w['target_product_ids']) ? $w['target_product_ids'] : [];
    $cids = isset($w['target_collection_ids']) && is_array($w['target_collection_ids']) ? $w['target_collection_ids'] : [];
    $productIdCsv = implode(',', $pids);
    $collectionIdCsv = implode(',', $cids);
    $productLabels = [];
    if (! empty($w['target_product_labels']) && is_array($w['target_product_labels'])) {
        foreach ($w['target_product_labels'] as $k => $v) {
            if (! is_scalar($v)) {
                continue;
            }
            $productLabels[(string) (int) $k] = (string) $v;
        }
    }
    $collectionLabels = [];
    if (! empty($w['target_collection_labels']) && is_array($w['target_collection_labels'])) {
        foreach ($w['target_collection_labels'] as $k => $v) {
            if (! is_scalar($v)) {
                continue;
            }
            $collectionLabels[(string) (int) $k] = (string) $v;
        }
    }
    $productLabelsAttr = json_encode($productLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?: '{}';
    $collectionLabelsAttr = json_encode($collectionLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?: '{}';
@endphp

@section('bis-content')
    <div class="bis-header">
        <div>
            <h1 class="bis-title">Widget</h1>
            <div class="bis-subtitle">Scarcity bar, restock signups, and optional price-drop alerts. Saved settings apply on the next storefront load.</div>
        </div>
        <button type="button" class="bis-btn bis-btn-primary" id="bisSaveWidget"><i class="bi bi-save"></i> Save</button>
    </div>

    <p class="bis-page-lead">Use the tabs below to move quickly. Pick products and collections from Shopify when targeting is set to specific items — no Admin URL hunting.</p>

    <form id="bisWidgetForm" class="bis-form">
        <div class="bis-tabs" role="tablist" aria-label="Widget settings sections">
            <button type="button" class="bis-tab active" data-bis-tab="look">Bar &amp; placement</button>
            <button type="button" class="bis-tab" data-bis-tab="target">Targeting</button>
            <button type="button" class="bis-tab" data-bis-tab="notify">Restock signup</button>
            <button type="button" class="bis-tab" data-bis-tab="inventory">Inventory webhooks</button>
            <button type="button" class="bis-tab" data-bis-tab="sending">Email batches</button>
            <button type="button" class="bis-tab" data-bis-tab="pricedrop">Price-drop alerts</button>
        </div>

        <div class="bis-tab-panels">
            <div class="bis-tab-panel active" data-bis-panel="look">
                <div class="bis-card">
                    <div class="bis-card-title">Bar &amp; colors</div>
                    <p class="bis-hint bis-hint-spaced">
                        <strong>How the bar works:</strong> when Shopify exposes a quantity for the selected variant, fill width is <strong>quantity ÷ “Bar fills at this quantity”</strong> (max 100%). When quantity is hidden, the bar uses full width and the “unknown quantity” copy with your in-stock color.
                    </p>
                    <div class="row">
                        <div>
                            <label>Bar fills at this quantity (100% width)</label>
                            <input type="number" name="widget[scarcity_max_stock]" min="1" value="{{ (int)($w['scarcity_max_stock'] ?? 50) }}">
                            <div class="bis-hint">Typical “fully stocked” level (often 20–200). Example: <strong>50</strong> → 25 units = half bar. <strong>1</strong> → any counted in-stock qty shows a full bar only (no partial fill).</div>
                        </div>
                        <div>
                            <label>Low-stock starts below this quantity</label>
                            <input type="number" name="widget[scarcity_low_threshold]" min="1" value="{{ (int)($w['scarcity_low_threshold'] ?? 10) }}">
                            <div class="bis-hint"><strong>Strictly less than</strong> this number (not equal). Example: <strong>10</strong> → 0–9 = low-stock color; 10+ = in-stock.</div>
                        </div>
                        <div>
                            <label>Bar radius (px)</label>
                            <input type="number" name="widget[scarcity_bar_radius]" min="0" value="{{ (int)($w['scarcity_bar_radius'] ?? 999) }}">
                            <div class="bis-hint"><code>999</code> = pill shape.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div><label>Track</label><input type="color" name="widget[scarcity_track_color]" value="{{ $w['scarcity_track_color'] ?? '#E5E7EB' }}"></div>
                        <div><label>In stock</label><input type="color" name="widget[scarcity_color_in_stock]" value="{{ $w['scarcity_color_in_stock'] ?? '#16A34A' }}"></div>
                        <div><label>Low stock</label><input type="color" name="widget[scarcity_color_low_stock]" value="{{ $w['scarcity_color_low_stock'] ?? '#D97706' }}"></div>
                        <div><label>Sold out</label><input type="color" name="widget[scarcity_color_sold_out]" value="{{ $w['scarcity_color_sold_out'] ?? '#DC2626' }}"></div>
                    </div>
                    <div class="row">
                        <div>
                            <label>Shimmer on bar</label>
                            <select name="widget[scarcity_animate_bar]">
                                <option value="1" @selected(!empty($w['scarcity_animate_bar'] ?? true))>On</option>
                                <option value="0" @selected(empty($w['scarcity_animate_bar'] ?? true))>Off</option>
                            </select>
                        </div>
                        <div>
                            <label>Pulse status dot</label>
                            <select name="widget[scarcity_show_pulse]">
                                <option value="1" @selected(!empty($w['scarcity_show_pulse'] ?? true))>On</option>
                                <option value="0" @selected(empty($w['scarcity_show_pulse'] ?? true))>Off</option>
                            </select>
                        </div>
                        <div>
                            <label>Position</label>
                            <select name="widget[position]">
                                @foreach(['before_buy_button'=>'Above add to cart', 'after_buy_button'=>'Below add to cart', 'floating'=>'Floating corner'] as $k=>$v)
                                    <option value="{{ $k }}" @selected(($w['position'] ?? 'before_buy_button')===$k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bis-card bis-mt-step">
                    <div class="bis-card-title">Where it shows</div>
                    <p class="bis-hint bis-hint-spaced">The theme embed loads globally; these toggles limit which page types render the widget.</p>
                    <div class="row">
                        <div>
                            <label>Product pages</label>
                            <select name="widget[page_product]">
                                <option value="1" @selected(!empty($w['page_product'] ?? true))>Show</option>
                                <option value="0" @selected(empty($w['page_product'] ?? true))>Hide</option>
                            </select>
                        </div>
                        <div>
                            <label>Collection grids</label>
                            <select name="widget[page_collection]">
                                <option value="1" @selected(!empty($w['page_collection'] ?? true))>Show</option>
                                <option value="0" @selected(empty($w['page_collection'] ?? true))>Hide</option>
                            </select>
                        </div>
                        <div>
                            <label>Homepage</label>
                            <select name="widget[page_index]">
                                <option value="1" @selected(!empty($w['page_index'] ?? true))>Show</option>
                                <option value="0" @selected(empty($w['page_index'] ?? true))>Hide</option>
                            </select>
                        </div>
                        <div>
                            <label>Search</label>
                            <select name="widget[page_search]">
                                <option value="1" @selected(!empty($w['page_search'] ?? true))>Show</option>
                                <option value="0" @selected(empty($w['page_search'] ?? true))>Hide</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bis-tab-panel" data-bis-panel="target">
                <div class="bis-card">
                    <div class="bis-card-title">Who sees the widget</div>
                    <p class="bis-hint bis-hint-spaced">Choose “All products” or narrow to hand-picked products or collections. Targeting is by <strong>product</strong> (all variants on that product follow the same rule). The storefront still tracks <strong>variants</strong> for stock, scarcity bar, and signups.</p>
                    <div class="row">
                        <div class="bis-form-full">
                            <label>Mode</label>
                            <select name="widget[targeting_mode]" id="bisTargetingMode">
                                <option value="all" @selected(($w['targeting_mode'] ?? 'all')==='all')>All products</option>
                                <option value="products" @selected(($w['targeting_mode'] ?? '')==='products')>Only selected products</option>
                                <option value="collections" @selected(($w['targeting_mode'] ?? '')==='collections')>Only products in selected collections</option>
                            </select>
                        </div>
                    </div>

                    <div id="bisTargetProductsWrap" class="bis-mt-step">
                        <label>Products</label>
                        <input type="hidden" name="widget[target_product_ids]" id="bisTargetProducts" value="{{ $productIdCsv }}">
                        <input type="hidden" name="widget[target_product_labels]" id="bisTargetProductLabels" value="{{ $productLabelsAttr }}">
                        <div class="bis-hint bis-hint-tight">Names are saved with your IDs so you always see what you picked.</div>
                        <div class="bis-target-actions">
                            <button type="button" class="bis-btn bis-btn-primary" id="bisPickProducts"><i class="bi bi-box-seam"></i> Browse products</button>
                            <button type="button" class="bis-btn" id="bisClearProducts">Clear list</button>
                        </div>
                        <div id="bisProductChipSummary" class="bis-hint bis-mb-0"></div>
                        <div id="bisProductChips" class="bis-chip-row" aria-live="polite"></div>
                    </div>

                    <div id="bisTargetCollectionsWrap" class="bis-mt-step">
                        <label>Collections</label>
                        <input type="hidden" name="widget[target_collection_ids]" id="bisTargetCollections" value="{{ $collectionIdCsv }}">
                        <input type="hidden" name="widget[target_collection_labels]" id="bisTargetCollectionLabels" value="{{ $collectionLabelsAttr }}">
                        <div class="bis-target-actions">
                            <button type="button" class="bis-btn bis-btn-primary" id="bisPickCollections"><i class="bi bi-collection"></i> Browse collections</button>
                            <button type="button" class="bis-btn" id="bisClearCollections">Clear list</button>
                        </div>
                        <div id="bisCollectionChipSummary" class="bis-hint bis-mb-0"></div>
                        <div id="bisCollectionChips" class="bis-chip-row" aria-live="polite"></div>
                    </div>

                    <button type="button" class="bis-details-toggle bis-details-toggle-btn" id="bisToggleManualIds">Paste IDs manually instead</button>
                    <div class="bis-manual-ids" id="bisManualIds">
                        <div class="row">
                            <div>
                                <label>Product IDs</label>
                                <textarea id="bisManualProducts" rows="3" placeholder="Comma or line separated">{{ $productIdCsv }}</textarea>
                            </div>
                            <div>
                                <label>Collection IDs</label>
                                <textarea id="bisManualCollections" rows="3" placeholder="Comma or line separated">{{ $collectionIdCsv }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bis-tab-panel" data-bis-panel="notify">
                <div class="bis-card">
                    <div class="bis-card-title">Restock signup &amp; theme</div>
                    <div class="row">
                        <div>
                            <label>Ask for phone</label>
                            <select name="widget[collect_phone]">
                                <option value="0" @selected(empty($w['collect_phone']))>No</option>
                                <option value="1" @selected(!empty($w['collect_phone']))>Yes</option>
                            </select>
                        </div>
                        <div>
                            <label>Hide native sold-out / add button</label>
                            <select name="widget[hide_sold_out_button]">
                                <option value="0" @selected(empty($w['hide_sold_out_button']))>No</option>
                                <option value="1" @selected(!empty($w['hide_sold_out_button']))>Yes</option>
                            </select>
                        </div>
                        <div>
                            <label>Font</label>
                            <input type="text" name="widget[font_family]" value="{{ $w['font_family'] ?? 'inherit' }}" placeholder="inherit">
                        </div>
                    </div>
                    <div class="row">
                        <div class="bis-form-full">
                            <label>Buy button / form selector (advanced)</label>
                            <input type="text" name="widget[buy_button_selector]" value="{{ $w['buy_button_selector'] ?? '' }}" placeholder="form[action*='/cart/add']">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bis-tab-panel" data-bis-panel="inventory">
                <div class="bis-card">
                    <div class="bis-card-title">Restock detection</div>
                    <p class="bis-hint bis-hint-spaced">Controls when queued “Notify me” emails send after Shopify inventory changes.</p>
                    <div class="row">
                        <div>
                            <label>Inventory rule</label>
                            <select name="inventory[rule]">
                                <option value="any_location" @selected(($inv['rule'] ?? 'any_location')==='any_location')>Any location</option>
                                <option value="sellable" @selected(($inv['rule'] ?? '')==='sellable')>Sellable only</option>
                                <option value="online_only" @selected(($inv['rule'] ?? '')==='online_only')>Online Store</option>
                            </select>
                        </div>
                        <div>
                            <label>Min quantity = in stock</label>
                            <input type="number" name="inventory[min_threshold]" min="1" value="{{ (int)($inv['min_threshold'] ?? 1) }}">
                        </div>
                        <div>
                            <label>Debounce (seconds)</label>
                            <input type="number" name="inventory[debounce_seconds]" min="0" value="{{ (int)($inv['debounce_seconds'] ?? 60) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bis-tab-panel" data-bis-panel="sending">
                <div class="bis-card">
                    <div class="bis-card-title">High-volume sending</div>
                    <div class="row">
                        <div>
                            <label>Mode</label>
                            <select name="sending[mode]">
                                <option value="immediate" @selected(($send['mode'] ?? 'immediate')==='immediate')>Immediate</option>
                                <option value="staggered" @selected(($send['mode'] ?? '')==='staggered')>Staggered</option>
                                <option value="best_window" @selected(($send['mode'] ?? '')==='best_window')>Best window</option>
                            </select>
                        </div>
                        <div>
                            <label>Batch size</label>
                            <input type="number" name="sending[batch_size]" min="1" value="{{ (int)($send['batch_size'] ?? 200) }}">
                        </div>
                        <div>
                            <label>Delay between batches (ms)</label>
                            <input type="number" name="sending[stagger_ms]" min="0" value="{{ (int)($send['stagger_ms'] ?? 150) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bis-tab-panel" data-bis-panel="pricedrop">
                <div class="bis-card">
                    <div class="bis-card-title">Price-drop alerts (storefront)</div>
                    <p class="bis-hint bis-hint-spaced">When a variant is <strong>in stock</strong> and has a compare-at price above the current price (by your thresholds), shoppers can sign up for a price-drop email. Copy for headings and buttons lives under <strong>Translations</strong>.</p>
                    <div class="row">
                        <div>
                            <label>Enable on storefront</label>
                            <select name="widget[show_on_price_drop]">
                                <option value="1" @selected(!empty($w['show_on_price_drop'] ?? true))>Yes</option>
                                <option value="0" @selected(empty($w['show_on_price_drop'] ?? true))>No</option>
                            </select>
                        </div>
                        <div>
                            <label>Layout mode</label>
                            <select name="widget[mode]">
                                @foreach(['inline'=>'Inline form', 'button'=>'Button opens dialog', 'popup'=>'Text link opens dialog'] as $k=>$v)
                                    <option value="{{ $k }}" @selected(($w['mode'] ?? 'inline')===$k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row bis-mt-step">
                        <div>
                            <label>Minimum $ drop</label>
                            <input type="number" step="0.01" name="pricing[min_abs_drop]" value="{{ $settings->pricing['min_abs_drop'] ?? 0.5 }}">
                        </div>
                        <div>
                            <label>Minimum % drop</label>
                            <input type="number" step="0.01" name="pricing[min_pct_drop]" value="{{ $settings->pricing['min_pct_drop'] ?? 5 }}">
                        </div>
                        <div>
                            <label>Use compare-at price</label>
                            <select name="pricing[use_compare_at]">
                                <option value="1" @selected(!empty($settings->pricing['use_compare_at']))>Yes</option>
                                <option value="0" @selected(empty($settings->pricing['use_compare_at']))>No</option>
                            </select>
                            <div class="bis-hint">“No” hides the price-drop signup on the storefront (no baseline price).</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    function tabActivate(id) {
        document.querySelectorAll('#bisWidgetForm .bis-tab').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-bis-tab') === id);
        });
        document.querySelectorAll('#bisWidgetForm .bis-tab-panel').forEach(function (panel) {
            panel.classList.toggle('active', panel.getAttribute('data-bis-panel') === id);
        });
    }
    document.querySelectorAll('#bisWidgetForm .bis-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            tabActivate(btn.getAttribute('data-bis-tab'));
        });
    });

    function gidToNumeric(gid) {
        var m = String(gid || '').match(/(\d+)\s*$/);
        return m ? parseInt(m[1], 10) : null;
    }
    function parseCsvIds(str) {
        return String(str || '').split(/[\s,]+/).map(function (s) { return parseInt(s.trim(), 10); }).filter(function (n) { return n > 0; });
    }
    function uniqueIds(arr) {
        var o = {};
        arr.forEach(function (n) { o[n] = true; });
        return Object.keys(o).map(function (k) { return parseInt(k, 10); });
    }
    function parseLabelMap(jsonStr) {
        try {
            var o = JSON.parse(String(jsonStr || '{}'));
            return (o && typeof o === 'object' && !Array.isArray(o)) ? o : {};
        } catch (e) {
            return {};
        }
    }
    function stringifyLabelMap(obj) {
        try {
            return JSON.stringify(obj && typeof obj === 'object' ? obj : {});
        } catch (e) {
            return '{}';
        }
    }
    function pruneLabelMap(labels, ids) {
        var set = {};
        ids.forEach(function (id) { set[String(id)] = true; });
        var out = {};
        Object.keys(labels || {}).forEach(function (k) {
            if (set[k]) out[k] = labels[k];
        });
        return out;
    }
    function syncManualFromHidden() {
        var mp = document.getElementById('bisManualProducts');
        var mc = document.getElementById('bisManualCollections');
        var hp = document.getElementById('bisTargetProducts');
        var hc = document.getElementById('bisTargetCollections');
        if (mp && hp) mp.value = hp.value.split(/[\s,]+/).filter(Boolean).join(', ');
        if (mc && hc) mc.value = hc.value.split(/[\s,]+/).filter(Boolean).join(', ');
    }
    function syncHiddenFromManual() {
        var mp = document.getElementById('bisManualProducts');
        var mc = document.getElementById('bisManualCollections');
        var hp = document.getElementById('bisTargetProducts');
        var hc = document.getElementById('bisTargetCollections');
        if (mp && hp) hp.value = uniqueIds(parseCsvIds(mp.value)).join(',');
        if (mc && hc) hc.value = uniqueIds(parseCsvIds(mc.value)).join(',');
    }

    function renderTargetChips(container, ids, labels) {
        if (!container) return;
        container.innerHTML = '';
        var maxShow = 24;
        var slice = ids.slice(0, maxShow);
        labels = labels || {};
        slice.forEach(function (id) {
            var span = document.createElement('span');
            span.className = 'bis-chip';
            span.setAttribute('title', String(labels[String(id)] || ('#' + id)));
            var title = labels[String(id)];
            span.textContent = title ? (title + ' · #' + id) : ('#' + id);
            container.appendChild(span);
        });
        if (ids.length > maxShow) {
            var more = document.createElement('span');
            more.className = 'bis-chip';
            more.textContent = '+' + (ids.length - maxShow) + ' more';
            container.appendChild(more);
        }
    }
    function summarizeSelection(ids, labels, noun) {
        if (!ids.length) return 'No ' + noun + ' selected';
        labels = labels || {};
        var names = [];
        for (var i = 0; i < ids.length && names.length < 4; i++) {
            var t = labels[String(ids[i])];
            if (t) names.push(t);
        }
        if (!names.length) {
            return ids.length + ' ' + noun + (ids.length === 1 ? '' : 's') + ' (IDs only — use Browse for names)';
        }
        var tail = ids.length > names.length ? ' +' + (ids.length - names.length) + ' more' : '';
        return ids.length + ' ' + noun + (ids.length === 1 ? '' : 's') + ': ' + names.join(', ') + tail;
    }
    function refreshTargetingSummary() {
        var hp = document.getElementById('bisTargetProducts');
        var hc = document.getElementById('bisTargetCollections');
        var hpl = document.getElementById('bisTargetProductLabels');
        var hcl = document.getElementById('bisTargetCollectionLabels');
        var sp = document.getElementById('bisProductChipSummary');
        var sc = document.getElementById('bisCollectionChipSummary');
        var pids = uniqueIds(parseCsvIds(hp && hp.value));
        var cids = uniqueIds(parseCsvIds(hc && hc.value));
        if (hp) hp.value = pids.join(',');
        if (hc) hc.value = cids.join(',');
        var pl = pruneLabelMap(parseLabelMap(hpl && hpl.value), pids);
        var cl = pruneLabelMap(parseLabelMap(hcl && hcl.value), cids);
        if (hpl) hpl.value = stringifyLabelMap(pl);
        if (hcl) hcl.value = stringifyLabelMap(cl);
        if (sp) sp.textContent = summarizeSelection(pids, pl, 'product');
        if (sc) sc.textContent = summarizeSelection(cids, cl, 'collection');
        renderTargetChips(document.getElementById('bisProductChips'), pids, pl);
        renderTargetChips(document.getElementById('bisCollectionChips'), cids, cl);
    }

    var modeSel = document.getElementById('bisTargetingMode');
    var wrapP = document.getElementById('bisTargetProductsWrap');
    var wrapC = document.getElementById('bisTargetCollectionsWrap');
    function syncTargetingVisibility() {
        var m = modeSel ? modeSel.value : 'all';
        if (wrapP) wrapP.style.display = m === 'products' ? 'block' : 'none';
        if (wrapC) wrapC.style.display = m === 'collections' ? 'block' : 'none';
    }
    if (modeSel) modeSel.addEventListener('change', syncTargetingVisibility);
    syncTargetingVisibility();
    refreshTargetingSummary();

    var manualBox = document.getElementById('bisManualIds');
    var manualToggle = document.getElementById('bisToggleManualIds');
    if (manualToggle && manualBox) {
        manualToggle.addEventListener('click', function () {
            manualBox.classList.toggle('is-open');
            if (manualBox.classList.contains('is-open')) syncManualFromHidden();
        });
    }
    ['bisManualProducts', 'bisManualCollections'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', function () { syncHiddenFromManual(); refreshTargetingSummary(); });
    });

    function openResourcePicker(kind) {
        var A = window['app-bridge'];
        if (!A || !A.actions || typeof app === 'undefined') {
            if (typeof showToast === 'function') showToast(app, 'Open this screen from the Shopify admin app frame to use Browse.', true);
            return;
        }
        var RP = A.actions.ResourcePicker;
        if (!RP || typeof RP.create !== 'function') {
            if (typeof showToast === 'function') showToast(app, 'Resource picker is not available in this bridge version — use manual IDs.', true);
            return;
        }
        var isProduct = kind === 'product';
        var hidden = document.getElementById(isProduct ? 'bisTargetProducts' : 'bisTargetCollections');
        var labelsHidden = document.getElementById(isProduct ? 'bisTargetProductLabels' : 'bisTargetCollectionLabels');
        var prefix = isProduct ? 'gid://shopify/Product/' : 'gid://shopify/Collection/';
        var existing = uniqueIds(parseCsvIds(hidden && hidden.value));
        var initialSelectionIds = existing.map(function (id) { return { id: prefix + id }; });

        var RT = RP.ResourceType || {};
        var pickerResourceType = isProduct
            ? (RT.Product !== undefined ? RT.Product : 'Product')
            : (RT.Collection !== undefined ? RT.Collection : 'Collection');
        var picker = RP.create(app, {
            resourceType: pickerResourceType,
            options: {
                selectMultiple: true,
                initialSelectionIds: initialSelectionIds
            }
        });
        var unsub = function () {
            try {
                picker.unsubscribe(RP.Action.SELECT);
                picker.unsubscribe(RP.Action.CANCEL);
            } catch (e) { /* ignore */ }
        };
        picker.subscribe(RP.Action.SELECT, function (payload) {
            var sel = (payload && payload.selection) || [];
            var ids = uniqueIds(sel.map(function (r) { return gidToNumeric(r.id); }).filter(Boolean));
            if (hidden) hidden.value = ids.join(',');
            var labelMap = {};
            sel.forEach(function (r) {
                var id = gidToNumeric(r.id);
                if (!id) return;
                var title = r.title || r.displayName || r.name;
                if (title) labelMap[String(id)] = String(title).slice(0, 200);
            });
            if (labelsHidden) labelsHidden.value = stringifyLabelMap(labelMap);
            refreshTargetingSummary();
            unsub();
        });
        picker.subscribe(RP.Action.CANCEL, function () { unsub(); });
        picker.dispatch(RP.Action.OPEN);
    }

    var pickP = document.getElementById('bisPickProducts');
    var pickC = document.getElementById('bisPickCollections');
    if (pickP) pickP.addEventListener('click', function () { openResourcePicker('product'); });
    if (pickC) pickC.addEventListener('click', function () { openResourcePicker('collection'); });
    document.getElementById('bisClearProducts') && document.getElementById('bisClearProducts').addEventListener('click', function () {
        var h = document.getElementById('bisTargetProducts');
        var l = document.getElementById('bisTargetProductLabels');
        if (h) h.value = '';
        if (l) l.value = '{}';
        refreshTargetingSummary();
        syncManualFromHidden();
    });
    document.getElementById('bisClearCollections') && document.getElementById('bisClearCollections').addEventListener('click', function () {
        var h = document.getElementById('bisTargetCollections');
        var l = document.getElementById('bisTargetCollectionLabels');
        if (h) h.value = '';
        if (l) l.value = '{}';
        refreshTargetingSummary();
        syncManualFromHidden();
    });

    function serializeWidgetForm(form) {
        /* Only push manual textarea → hidden when the manual panel is open; otherwise empty
         * textareas (initial state) wipe Browse / resource-picker selections on every save. */
        var manualBox = document.getElementById('bisManualIds');
        var manualOpen = manualBox && manualBox.classList.contains('is-open');
        if (manualOpen) {
            syncHiddenFromManual();
        } else {
            syncManualFromHidden();
        }
        refreshTargetingSummary();
        var data = { widget: {}, inventory: {}, pricing: {}, consent: {}, sending: {} };
        new FormData(form).forEach(function (value, key) {
            var m = key.match(/^(widget|inventory|pricing|consent|sending)\[(\w+)\]$/);
            if (!m) return;
            var section = m[1], field = m[2];
            if (field === 'target_product_ids' || field === 'target_collection_ids') {
                data[section][field] = value;
            } else if (value === '0') data[section][field] = false;
            else if (value === '1') data[section][field] = true;
            else if (/^-?\d+(\.\d+)?$/.test(value)) data[section][field] = Number(value);
            else data[section][field] = value;
        });
        return data;
    }

    document.getElementById('bisSaveWidget').addEventListener('click', async function () {
        var form = document.getElementById('bisWidgetForm');
        if (typeof clearFormErrors === 'function') clearFormErrors(form);
        var payload = serializeWidgetForm(form);
        try {
            await apiPost('{{ route('bis.widget.update') }}', payload, '{{ csrf_token() }}');
            if (typeof showToast === 'function') showToast(app, 'Saved');
        } catch (e) {
            var shown = (typeof showFormErrors === 'function') ? showFormErrors(form, e) : false;
            if (typeof showToast === 'function') showToast(app, shown ? 'Please fix the highlighted fields' : (e.message || 'Save failed'), true);
        }
    });
})();
</script>
@endpush
