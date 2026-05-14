(function () {
    'use strict';

    var data = window.sbPageData || {};
    if (!data.apiBase || !String(data.apiBase).trim() || !data.shopDomain) return;

    var CONFIG = null;

    /** Theme embed selectors + optional window.sbThemeSelectorsOverride (same keys) for support hotfixes. */
    function sbSelectors() {
        var s = (data && data.selectors) ? Object.assign({}, data.selectors) : {};
        if (typeof window !== 'undefined' && window.sbThemeSelectorsOverride && typeof window.sbThemeSelectorsOverride === 'object') {
            Object.assign(s, window.sbThemeSelectorsOverride);
        }
        return {
            listingCardRoots: String(s.listingCardRoots || '').trim(),
            listingBarContainer: String(s.listingBarContainer || '').trim(),
            listingBarBefore: String(s.listingBarBefore || '').trim(),
            listingSkipLinkWithin: String(s.listingSkipLinkWithin || '').trim(),
            productAddToCartExtra: String(s.productAddToCartExtra || '').trim(),
            productVariantInput: String(s.productVariantInput || '').trim(),
            productCartForm: String(s.productCartForm || '').trim()
        };
    }

    function splitSelectors(csv) {
        return String(csv || '').split(',').map(function (x) { return x.trim(); }).filter(Boolean);
    }

    function closestFirstMatch(anchor, csv) {
        if (!anchor || !csv) return null;
        var parts = splitSelectors(csv);
        for (var i = 0; i < parts.length; i++) {
            try {
                var el = anchor.closest(parts[i]);
                if (el) return el;
            } catch (e) { /* invalid selector */ }
        }
        return null;
    }

    function queryFirstMatch(root, csv) {
        if (!root || !csv) return null;
        var parts = splitSelectors(csv);
        for (var i = 0; i < parts.length; i++) {
            try {
                var el = root.querySelector(parts[i]);
                if (el) return el;
            } catch (e) { /* invalid selector */ }
        }
        return null;
    }

    /** True if this product link should be ignored when picking an anchor (e.g. duplicate image link). */
    function listingLinkSkippable(a) {
        var custom = sbSelectors().listingSkipLinkWithin;
        if (custom) {
            var parts = splitSelectors(custom);
            for (var i = 0; i < parts.length; i++) {
                try {
                    if (a.closest(parts[i])) return true;
                } catch (e) { /* */ }
            }
            return false;
        }
        return !!(a.closest && a.closest('.card__inner'));
    }

    function fetchConfig() {
        var url = data.apiBase + '/api/storefront/widget-config?shop=' + encodeURIComponent(data.shopDomain);
        return fetch(url, { credentials: 'omit' }).then(function (r) { return r.json(); });
    }

    function t(key, fallback) {
        var v = CONFIG && CONFIG.translations && CONFIG.translations[key];
        return (v && String(v).length) ? v : (fallback != null ? fallback : '');
    }

    /** Shopify IDs are numeric; Liquid/JSON may use string or number — indexOf is strict. */
    function sbNumericId(v) {
        if (v == null || v === '') return null;
        if (typeof v === 'number' && isFinite(v)) {
            var r = Math.round(v);
            return r > 0 ? r : null;
        }
        var s = String(v).trim();
        if (/^\d+$/.test(s)) {
            var n = parseInt(s, 10);
            return n > 0 ? n : null;
        }
        var m = s.match(/(\d+)\s*$/);
        if (m) {
            n = parseInt(m[1], 10);
            return n > 0 ? n : null;
        }
        return null;
    }

    function passesTargeting(entry) {
        var tg = (CONFIG && CONFIG.targeting) || {};
        var mode = (tg.mode || 'all');
        if (mode === 'all') return true;
        var pid = sbNumericId(entry && entry.id);
        var cids = (entry && entry.collections) || [];
        if (mode === 'products') {
            var pids = tg.product_ids || [];
            var i;
            for (i = 0; i < pids.length; i++) {
                if (sbNumericId(pids[i]) === pid) return true;
            }
            return false;
        }
        if (mode === 'collections') {
            var want = tg.collection_ids || [];
            var wantSet = {};
            for (i = 0; i < want.length; i++) {
                var wn = sbNumericId(want[i]);
                if (wn != null) wantSet[wn] = true;
            }
            for (i = 0; i < cids.length; i++) {
                var cid = sbNumericId(cids[i]);
                if (cid != null && wantSet[cid]) return true;
            }
            return false;
        }
        return true;
    }

    function applyLabel(template, count) {
        var s = String(template || '');
        s = s.replace(/\{\{\s*count\s*\}\}/gi, String(count));
        s = s.replace(/\{count\}/gi, String(count));
        return s;
    }

    function scarcityCfg() {
        return (CONFIG && CONFIG.scarcity) || {};
    }

    function pricingCfg() {
        return (CONFIG && CONFIG.pricing) || {};
    }

    function eligiblePriceDrop(v) {
        if (!CONFIG || !CONFIG.features || !CONFIG.features.price_drop) return false;
        if (!v || v.available === false) return false;
        var pr = pricingCfg();
        if (!pr.use_compare_at) return false;
        var cap = v.compare_at_price;
        var price = v.price;
        if (cap == null || price == null || !(cap > price)) return false;
        var absDrop = (cap - price) / 100;
        var pct = cap > 0 ? ((cap - price) / cap) * 100 : 0;
        var minAbs = parseFloat(pr.min_abs_drop);
        var minPct = parseFloat(pr.min_pct_drop);
        if (!isFinite(minAbs)) minAbs = 0.5;
        if (!isFinite(minPct)) minPct = 5;
        return absDrop >= minAbs && pct >= minPct;
    }

    function productCollectionIds() {
        var p = data.product;
        if (!p) return [];
        if (Array.isArray(p.collections)) return p.collections;
        if (Array.isArray(p.collection_ids)) return p.collection_ids;
        return [];
    }

    function getMount() {
        var selectors = [];
        var fromConfig = CONFIG && CONFIG.widget && CONFIG.widget.buy_button_selector;
        if (fromConfig) selectors.push(fromConfig);
        if (data.mount && data.mount.selectorBuyButton) selectors.push(data.mount.selectorBuyButton);
        var extra = sbSelectors().productAddToCartExtra;
        if (extra) {
            splitSelectors(extra).forEach(function (sel) {
                if (sel) selectors.push(sel);
            });
        }
        selectors.push("form[action*='/cart/add']");

        for (var i = 0; i < selectors.length; i++) {
            var parts = selectors[i].split(',').map(function (s) { return s.trim(); }).filter(Boolean);
            for (var j = 0; j < parts.length; j++) {
                var el = document.querySelector(parts[j]);
                if (el) return el;
            }
        }
        return null;
    }

    function placeAtBuyButton(node, position) {
        var mount = getMount();
        if (!mount) { document.body.appendChild(node); return; }
        if (position === 'before_buy_button') {
            mount.parentNode.insertBefore(node, mount);
        } else if (position === 'floating') {
            node.style.position = 'fixed';
            node.style.right = '16px';
            node.style.bottom = '16px';
            node.style.zIndex = '9998';
            document.body.appendChild(node);
        } else {
            if (mount.nextSibling) mount.parentNode.insertBefore(node, mount.nextSibling);
            else mount.parentNode.appendChild(node);
        }
    }

    function hideSoldOutButton() {
        var w = (CONFIG && CONFIG.widget) || {};
        if (!w.hide_sold_out_button) return;

        var mount = getMount();
        if (mount) {
            mount.querySelectorAll("button[type='submit'], input[type='submit'], button[name='add']")
                .forEach(function (b) { b.style.setProperty('display', 'none', 'important'); });
        }
        [
            w.sold_out_button_selector,
            "button[name='add']",
            "[data-add-to-cart]",
            ".product-form__submit",
            ".btn--add-to-cart",
            ".shopify-payment-button"
        ].filter(Boolean).forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (el) {
                el.style.setProperty('display', 'none', 'important');
            });
        });
    }

    function emitOnHistoryChange() {
        ['pushState', 'replaceState'].forEach(function (m) {
            var orig = history[m];
            if (!orig || orig._sbPatched) return;
            history[m] = function () {
                var ret = orig.apply(this, arguments);
                window.dispatchEvent(new Event('sb:locationchange'));
                return ret;
            };
            history[m]._sbPatched = true;
        });
        window.addEventListener('popstate', function () {
            window.dispatchEvent(new Event('sb:locationchange'));
        });
    }

    function getVariantIdFromDom() {
        var s = sbSelectors();
        var vidParts = splitSelectors(s.productVariantInput);
        for (var i = 0; i < vidParts.length; i++) {
            try {
                var el = document.querySelector(vidParts[i]);
                if (el && el.value) {
                    var nv = parseInt(el.value, 10);
                    if (nv) return nv;
                }
            } catch (e) { /* invalid selector */ }
        }
        var formParts = splitSelectors(s.productCartForm);
        for (var f = 0; f < formParts.length; f++) {
            try {
                var form = document.querySelector(formParts[f]);
                if (form) {
                    var sel = form.querySelector('[name="id"]') || form.querySelector('[name="variant"]');
                    if (sel && sel.value) {
                        var n2 = parseInt(sel.value, 10);
                        if (n2) return n2;
                    }
                }
            } catch (e2) { /* */ }
        }
        var form = document.querySelector("form[action*='/cart/add']");
        if (form) {
            var sel2 = form.querySelector('[name="id"]') || form.querySelector('[name="variant"]');
            if (sel2 && sel2.value) {
                var n3 = parseInt(sel2.value, 10);
                if (n3) return n3;
            }
        }
        var params = new URLSearchParams(window.location.search);
        var v = parseInt(params.get('variant'), 10);
        return v || null;
    }

    function findVariantInProduct(vid) {
        if (!data.product || !data.product.variants || !data.product.variants.length) return null;
        if (vid) {
            for (var i = 0; i < data.product.variants.length; i++) {
                if (data.product.variants[i].id === vid) return data.product.variants[i];
            }
        }
        return data.product.variants[0];
    }

    function mergeVariantDetail(base, ext) {
        if (!base && ext) return ext;
        if (!ext) return base;
        var out = Object.assign({}, base);
        if (typeof ext.available === 'boolean') out.available = ext.available;
        if (typeof ext.available_for_sale === 'boolean') out.available = ext.available_for_sale;
        if (ext.inventory_quantity != null) out.inventory_quantity = ext.inventory_quantity;
        if (ext.title) out.title = ext.title;
        if (ext.id) out.id = ext.id;
        if (ext.compare_at_price != null) out.compare_at_price = ext.compare_at_price;
        return out;
    }

    function parseVariantFromEvent(ev) {
        var d = ev && ev.detail;
        if (!d) return null;
        return d.variant || (d.data && d.data.variant) || null;
    }

    function fontFamily() {
        var w = (CONFIG && CONFIG.widget) || {};
        var ff = w.font_family;
        if (ff && ff !== 'inherit') return ff;
        return '';
    }

    function barPercent(qty, maxStock, available, qtyKnown) {
        if (!available) return 0;
        if (!qtyKnown) return 100;
        var n = Math.max(0, parseInt(qty, 10) || 0);
        var cap = Math.max(1, maxStock);
        return Math.min(100, Math.round((n / cap) * 100));
    }

    function stateForVariant(v) {
        var sc = scarcityCfg();
        var lowTh = Math.max(1, parseInt(sc.low_threshold, 10) || 10);
        var maxStock = Math.max(1, parseInt(sc.max_stock, 10) || 50);

        var available = !!(v && (v.available !== false));
        var q = v && v.inventory_quantity;
        var qtyKnown = q !== null && q !== undefined && typeof q !== 'undefined';

        if (!available) {
            return { kind: 'sold_out', percent: 0, label: '', header: '' };
        }
        if (!qtyKnown) {
            return {
                kind: 'in_stock_unknown',
                percent: 100,
                label: t('scarcity_label_unknown', 'Available in stock'),
                header: ''
            };
        }
        var qty = parseInt(q, 10);
        if (!isFinite(qty) || qty < 0) qty = 0;
        if (qty <= 0) {
            return { kind: 'sold_out', percent: 0, label: '', header: '' };
        }
        var isLow = qty < lowTh;
        var pct = barPercent(qty, maxStock, true, true);
        if (isLow) {
            return {
                kind: 'low',
                percent: pct,
                label: applyLabel(t('scarcity_label_low_stock', 'Only {count} left in stock'), qty),
                header: t('scarcity_label_low_header', 'Low stock')
            };
        }
        return {
            kind: 'in_stock',
            percent: pct,
            label: applyLabel(t('scarcity_label_in_stock', 'In stock — {count} available'), qty),
            header: ''
        };
    }

    function applyScarcityColors(root, st) {
        var sc = scarcityCfg();
        root.style.setProperty('--sb-track', sc.track_color || '#E5E7EB');
        if (st.kind === 'low') {
            root.style.setProperty('--sb-fill', sc.color_low_stock || '#D97706');
        } else if (st.kind === 'in_stock' || st.kind === 'in_stock_unknown') {
            root.style.setProperty('--sb-fill', sc.color_in_stock || '#16A34A');
        } else {
            root.style.setProperty('--sb-fill', sc.color_sold_out || '#DC2626');
        }
        paintBarTrackAndFill(root, st);
    }

    /**
     * Themes often force nested div backgrounds (e.g. .product div { background: transparent !important }).
     * Inline !important survives that. Colors come from CONFIG + state so we never rely on computed
     * custom properties (some engines/themes return empty on the same frame as setProperty).
     */
    function paintBarTrackAndFill(root, st) {
        if (!root || typeof root.querySelector !== 'function') return;
        var trackEl = root.querySelector('.sb-track');
        var fillEl = root.querySelector('.sb-fill');
        if (!trackEl && !fillEl) return;
        try {
            var sc = scarcityCfg() || {};
            var track = String(sc.track_color || '#E5E7EB').trim() || '#E5E7EB';
            var fill;
            if (st && st.kind) {
                if (st.kind === 'low') fill = String(sc.color_low_stock || '#D97706').trim();
                else if (st.kind === 'in_stock' || st.kind === 'in_stock_unknown') {
                    fill = String(sc.color_in_stock || '#16A34A').trim();
                } else fill = String(sc.color_sold_out || '#DC2626').trim();
            }
            if (!fill) {
                var cs = window.getComputedStyle(root);
                fill = (cs.getPropertyValue('--sb-fill').trim() || '#16a34a');
                track = (cs.getPropertyValue('--sb-track').trim() || track);
            }
            if (trackEl) {
                trackEl.style.setProperty('position', 'relative', 'important');
                trackEl.style.setProperty('overflow', 'hidden', 'important');
                trackEl.style.setProperty('display', 'block', 'important');
                trackEl.style.setProperty('background-color', track, 'important');
                trackEl.style.setProperty('background-image', 'none', 'important');
            }
            if (fillEl) {
                var trackH = 0;
                if (trackEl) {
                    trackH = trackEl.offsetHeight
                        || parseInt(window.getComputedStyle(trackEl).height, 10)
                        || 0;
                }
                if (!trackH) trackH = 10;
                /* Layout must be inline: themes often reset nested div position/height/flex (fill collapses → gray track only). */
                fillEl.style.setProperty('box-sizing', 'border-box', 'important');
                fillEl.style.setProperty('display', 'block', 'important');
                fillEl.style.setProperty('visibility', 'visible', 'important');
                fillEl.style.setProperty('opacity', '1', 'important');
                fillEl.style.setProperty('mix-blend-mode', 'normal', 'important');
                fillEl.style.setProperty('max-height', 'none', 'important');
                fillEl.style.setProperty('transform', 'none', 'important');
                fillEl.style.setProperty('clip-path', 'none', 'important');
                fillEl.style.setProperty('flex-shrink', '0', 'important');
                fillEl.style.setProperty('position', 'absolute', 'important');
                fillEl.style.setProperty('left', '0', 'important');
                fillEl.style.setProperty('top', '0', 'important');
                fillEl.style.setProperty('bottom', '0', 'important');
                fillEl.style.setProperty('right', 'auto', 'important');
                fillEl.style.setProperty('height', 'auto', 'important');
                fillEl.style.setProperty('min-height', trackH + 'px', 'important');
                fillEl.style.setProperty('margin', '0', 'important');
                fillEl.style.setProperty('padding', '0', 'important');
                fillEl.style.setProperty('border', 'none', 'important');
                fillEl.style.setProperty('z-index', '1', 'important');
                fillEl.style.setProperty('background-color', fill, 'important');
                fillEl.style.setProperty(
                    'background-image',
                    'linear-gradient(90deg, rgba(0,0,0,0.14), transparent)',
                    'important'
                );
            }
        } catch (e) { /* ignore */ }
    }

    function buildProductRoot() {
        var w = (CONFIG && CONFIG.widget) || {};
        var pos = w.position || 'before_buy_button';

        var root = document.createElement('div');
        root.className = 'sb-root';
        root.id = 'sb-widget-root';
        var ff = fontFamily();
        if (ff) root.style.fontFamily = ff;

        root.innerHTML =
            '<div class="sb-stack">' +
              '<div class="sb-stock">' +
                '<div class="sb-row-top">' +
                  '<div class="sb-status">' +
                    '<span class="sb-dot" aria-hidden="true"></span>' +
                    '<span class="sb-label"></span>' +
                  '</div>' +
                  '<div class="sb-sub"></div>' +
                '</div>' +
                '<div class="sb-track"><div class="sb-fill"></div></div>' +
              '</div>' +
              '<div class="sb-pd sb-hidden">' +
                '<div class="sb-pd-inner"></div>' +
              '</div>' +
              '<div class="sb-notify sb-hidden">' +
                '<div class="sb-notify-title"></div>' +
                '<div class="sb-notify-sub"></div>' +
                '<form class="sb-form sb-form-stock" novalidate>' +
                  '<div class="sb-field">' +
                    '<label>' + t('label_email', 'Email') + '</label>' +
                    '<input type="email" name="email" autocomplete="email" placeholder="' + t('placeholder_email', 'you@example.com') + '">' +
                    '<div class="sb-field-err" data-for="email"></div>' +
                  '</div>' +
                  (w.collect_phone
                    ? '<div class="sb-field">' +
                        '<label>' + t('label_phone', 'Phone (optional)') + '</label>' +
                        '<input type="tel" name="phone" autocomplete="tel" placeholder="' + t('placeholder_phone', '+15555550123') + '">' +
                        '<div class="sb-field-err" data-for="phone"></div>' +
                      '</div>'
                    : '') +
                  '<label class="sb-consent"><input type="checkbox" name="consent_email" checked><span>' +
                    ((CONFIG.consent && CONFIG.consent.consent_copy) ? CONFIG.consent.consent_copy : t('consent_text', 'I agree to receive notifications about this product.')) +
                  '</span></label>' +
                  '<button type="submit" class="sb-submit">' + t('submit_label', 'Notify Me') + '</button>' +
                  '<div class="sb-msg"></div>' +
                '</form>' +
              '</div>' +
            '</div>' +
            '<div class="sb-modal sb-hidden" role="dialog" aria-modal="true">' +
              '<div class="sb-modal-backdrop" tabindex="-1"></div>' +
              '<div class="sb-modal-panel">' +
                '<button type="button" class="sb-modal-close" aria-label="Close">&times;</button>' +
                '<div class="sb-modal-title"></div>' +
                '<div class="sb-modal-body"></div>' +
              '</div>' +
            '</div>';

        placeAtBuyButton(root, pos);
        root.addEventListener('click', function (e) {
            var modal = root.querySelector('.sb-modal');
            if (!modal) return;
            if (e.target.closest && e.target.closest('.sb-pd-open')) {
                modal.classList.remove('sb-hidden');
            }
            if (e.target.closest && e.target.closest('.sb-modal-close')) {
                modal.classList.add('sb-hidden');
            }
            if (e.target.classList && e.target.classList.contains('sb-modal-backdrop')) {
                modal.classList.add('sb-hidden');
            }
        });
        return root;
    }

    function buildPriceDropFormMarkup() {
        var w = (CONFIG && CONFIG.widget) || {};
        return (
            '<form class="sb-form sb-form-pd" novalidate>' +
              '<div class="sb-field">' +
                '<label>' + t('label_email', 'Email') + '</label>' +
                '<input type="email" name="email" autocomplete="email" placeholder="' + t('placeholder_email', 'you@example.com') + '">' +
                '<div class="sb-field-err" data-for="email"></div>' +
              '</div>' +
              (w.collect_phone
                ? '<div class="sb-field">' +
                    '<label>' + t('label_phone', 'Phone (optional)') + '</label>' +
                    '<input type="tel" name="phone" autocomplete="tel" placeholder="' + t('placeholder_phone', '+15555550123') + '">' +
                    '<div class="sb-field-err" data-for="phone"></div>' +
                  '</div>'
                : '') +
              '<label class="sb-consent"><input type="checkbox" name="consent_email" checked><span>' +
                ((CONFIG.consent && CONFIG.consent.consent_copy) ? CONFIG.consent.consent_copy : t('consent_text', 'I agree to receive notifications about this product.')) +
              '</span></label>' +
              '<button type="submit" class="sb-submit">' + t('submit_label', 'Notify Me') + '</button>' +
              '<div class="sb-msg"></div>' +
            '</form>'
        );
    }

    function updatePriceDrop(root, variant) {
        var pdWrap = root.querySelector('.sb-pd');
        var inner = root.querySelector('.sb-pd-inner');
        var modal = root.querySelector('.sb-modal');
        if (!pdWrap || !inner || !modal) return;

        if (!eligiblePriceDrop(variant)) {
            pdWrap.classList.add('sb-hidden');
            modal.classList.add('sb-hidden');
            inner.innerHTML = '';
            var mbodyClear = modal.querySelector('.sb-modal-body');
            if (mbodyClear) mbodyClear.innerHTML = '';
            return;
        }

        var mode = ((CONFIG && CONFIG.widget) || {}).mode || 'inline';
        var title = t('modal_heading_price_drop', 'Get notified on price drops');
        var btnLabel = t('button_label_price_drop', 'Notify me on price drop');
        var sub = t('price_drop_notify_sub', 'We will email you if the price drops on this option.');

        var mtitle = modal.querySelector('.sb-modal-title');
        var mbody = modal.querySelector('.sb-modal-body');
        if (!mtitle || !mbody) {
            pdWrap.classList.add('sb-hidden');
            return;
        }

        pdWrap.classList.remove('sb-hidden');
        modal.classList.add('sb-hidden');
        mtitle.textContent = title;

        if (mode === 'inline') {
            inner.innerHTML =
                '<div class="sb-pd-card">' +
                  '<div class="sb-pd-title">' + title + '</div>' +
                  '<div class="sb-pd-sub">' + sub + '</div>' +
                  buildPriceDropFormMarkup() +
                '</div>';
            bindAlertForm(inner.querySelector('.sb-form-pd'), 'price_drop', function () { return variant; }, function () {
                modal.classList.add('sb-hidden');
            });
            mbody.innerHTML = '';
        } else if (mode === 'button') {
            inner.innerHTML = '<div class="sb-pd-card"><button type="button" class="sb-pd-open sb-pd-open--btn">' + btnLabel + '</button></div>';
            mbody.innerHTML = '<div class="sb-pd-sub">' + sub + '</div>' + buildPriceDropFormMarkup();
            bindAlertForm(modal.querySelector('.sb-form-pd'), 'price_drop', function () { return variant; }, function () {
                modal.classList.add('sb-hidden');
            });
        } else {
            inner.innerHTML = '<div class="sb-pd-card"><button type="button" class="sb-pd-open sb-pd-open--link">' + btnLabel + '</button></div>';
            mbody.innerHTML = '<div class="sb-pd-sub">' + sub + '</div>' + buildPriceDropFormMarkup();
            bindAlertForm(modal.querySelector('.sb-form-pd'), 'price_drop', function () { return variant; }, function () {
                modal.classList.add('sb-hidden');
            });
        }
    }

    function setProductUI(root, st, variant) {
        var stock = root.querySelector('.sb-stock');
        var notify = root.querySelector('.sb-notify');
        var pdWrap = root.querySelector('.sb-pd');
        var modal = root.querySelector('.sb-modal');
        var dot = root.querySelector('.sb-dot');
        var label = root.querySelector('.sb-label');
        var sub = root.querySelector('.sb-sub');
        var fill = root.querySelector('.sb-fill');
        var sc = scarcityCfg();

        applyScarcityColors(root, st);

        if (st.kind === 'sold_out') {
            stock.classList.add('sb-hidden');
            if (pdWrap) pdWrap.classList.add('sb-hidden');
            if (modal) modal.classList.add('sb-hidden');
            notify.classList.remove('sb-hidden');
            notify.querySelector('.sb-notify-title').textContent = t('scarcity_notify_heading', 'Get notified when this option is back');
            notify.querySelector('.sb-notify-sub').textContent = t('scarcity_notify_sub', 'Leave your email and we will let you know as soon as it returns.');
            hideSoldOutButton();
            return;
        }

        notify.classList.add('sb-hidden');
        stock.classList.remove('sb-hidden');

        sub.textContent = st.header || '';
        label.textContent = st.label || '';

        if (sc.show_pulse) dot.classList.add('sb-pulse');
        else dot.classList.remove('sb-pulse');

        fill.style.width = String(st.percent || 0) + '%';
        if (sc.animate_bar) fill.classList.add('sb-animated');
        else fill.classList.remove('sb-animated');

        var br = parseInt(sc.bar_radius, 10);
        if (isFinite(br)) {
            root.querySelectorAll('.sb-track').forEach(function (el) { el.style.borderRadius = br + 'px'; });
        }

        paintBarTrackAndFill(root, st);

        updatePriceDrop(root, variant);
    }

    function clearFieldErrors(form) {
        form.querySelectorAll('.sb-field-err').forEach(function (el) { el.textContent = ''; });
        form.querySelectorAll('input.sb-invalid').forEach(function (el) { el.classList.remove('sb-invalid'); });
    }

    function setFieldError(form, fieldName, message) {
        var input = form.querySelector('[name="' + fieldName + '"]');
        var note = form.querySelector('.sb-field-err[data-for="' + fieldName + '"]');
        if (input) input.classList.add('sb-invalid');
        if (note) note.textContent = message;
    }

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function validateForm(form) {
        clearFieldErrors(form);
        var ok = true;
        var email = (form.email && form.email.value || '').trim();
        var phone = (form.phone && form.phone.value || '').trim();
        var w = (CONFIG && CONFIG.widget) || {};
        var requireConsent = !!(CONFIG.consent && CONFIG.consent.require_email_consent);

        if (!email) {
            setFieldError(form, 'email', t('validation_email_required', 'Please enter your email address.'));
            ok = false;
        } else if (!isValidEmail(email)) {
            setFieldError(form, 'email', t('validation_email_invalid', "That doesn't look like a valid email address."));
            ok = false;
        }

        if (form.phone && phone && !/^[+\d][\d\s\-().]{5,}$/.test(phone)) {
            setFieldError(form, 'phone', t('validation_phone_invalid', 'Please enter a valid phone number, or leave it empty.'));
            ok = false;
        }

        if (requireConsent && form.consent_email && !form.consent_email.checked) {
            setFieldError(form, 'email', t('validation_consent_required', 'Please accept the consent to continue.'));
            ok = false;
        }

        return ok;
    }

    function bindAlertForm(form, alertType, getVariant, afterSuccess) {
        if (!form || form._sbAlertBound) return;
        form._sbAlertBound = alertType;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var submit = form.querySelector('.sb-submit');
            var msg = form.querySelector('.sb-msg');
            if (!submit || !msg) return;
            msg.className = 'sb-msg';
            msg.textContent = '';

            if (!validateForm(form)) return;

            submit.disabled = true;
            var variant = getVariant();

            var payload = {
                shop: data.shopDomain,
                type: alertType,
                product_id: data.product.id,
                product_handle: data.product.handle,
                product_title: data.product.title,
                variant_id: variant ? variant.id : null,
                variant_title: variant ? variant.title : null,
                image_url: data.product.featured_image || null,
                price: variant ? (variant.price / 100) : null,
                currency: data.currency,
                email: form.email.value,
                phone: form.phone ? form.phone.value : null,
                consent_email: form.consent_email && form.consent_email.checked,
                locale: document.documentElement.lang || 'en',
                channel: (form.phone && form.phone.value) ? 'both' : 'email',
                tags: Array.isArray(data.customerTags) ? data.customerTags : [],
                shopify_customer_id: data.customerId || null,
                product_collection_ids: productCollectionIds()
            };

            fetch(data.apiBase + '/api/storefront/subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(function (r) { return r.json().then(function (b) { return { ok: r.ok, status: r.status, body: b }; }); })
                .then(function (res) {
                    if (res.ok && res.body && res.body.ok) {
                        msg.className = 'sb-msg sb-success';
                        msg.textContent = res.body.message || t('success_message', "You're on the list!");
                        if (typeof afterSuccess === 'function') afterSuccess();
                        return;
                    }
                    submit.disabled = false;
                    msg.className = 'sb-msg sb-error';
                    msg.textContent = (res.body && (res.body.message || res.body.error))
                        || t('error_message', 'Something went wrong. Please try again.');
                })
                .catch(function () {
                    submit.disabled = false;
                    msg.className = 'sb-msg sb-error';
                    msg.textContent = 'Network error — please check your connection and try again.';
                });
        });
    }

    function bindNotifyForm(root, getVariant) {
        bindAlertForm(root.querySelector('.sb-form-stock'), 'back_in_stock', getVariant, function () {});
    }

    function refreshProductPage() {
        var root = document.getElementById('sb-widget-root');
        if (!root || !data.product) return;

        var merged = findVariantInProduct(getVariantIdFromDom());
        var st = stateForVariant(merged);
        setProductUI(root, st, merged);

        if (st.kind === 'sold_out') {
            bindNotifyForm(root, function () { return findVariantInProduct(getVariantIdFromDom()); });
        }
    }

    function initProductPage() {
        if (!data.product || !passesTargeting(data.product)) return;
        if (document.getElementById('sb-widget-root')) return;

        var root = buildProductRoot();
        bindNotifyForm(root, function () { return findVariantInProduct(getVariantIdFromDom()); });

        function onVariantEvent(ev) {
            var ext = parseVariantFromEvent(ev);
            var merged = mergeVariantDetail(findVariantInProduct(getVariantIdFromDom()), ext);
            var st = stateForVariant(merged);
            setProductUI(root, st, merged);
            if (st.kind === 'sold_out') {
                bindNotifyForm(root, function () {
                    return mergeVariantDetail(findVariantInProduct(getVariantIdFromDom()), ext);
                });
            }
        }

        document.addEventListener('variant:change', onVariantEvent);
        document.addEventListener('shopify:section:load', function () { setTimeout(refreshProductPage, 50); });

        document.addEventListener('change', function (e) {
            if (!e.target) return;
            if (e.target.name === 'id' || e.target.name === 'variant') {
                refreshProductPage();
            }
        });

        window.addEventListener('sb:locationchange', refreshProductPage);

        refreshProductPage();
    }

    function listingCardRoot(anchor) {
        if (!anchor) return null;
        var custom = closestFirstMatch(anchor, sbSelectors().listingCardRoots);
        if (custom) return custom;
        return anchor.closest('.card-wrapper')
            || anchor.closest('.grid__item')
            || anchor.closest('.product-card')
            || anchor.closest('.product-item')
            || anchor.closest('[data-product-id]')
            || anchor.closest('article')
            || anchor.closest('li');
    }

    /**
     * Prefer the main product title link (Dawn duplicates a link on the image card).
     * Skip cards that already have this product's widget mounted.
     */
    function findProductAnchor(handle, productId) {
        var esc = String(handle).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var re = new RegExp('/products/' + esc + '(\\?|#|$)', 'i');
        var links = document.querySelectorAll('a[href*="/products/"]');
        var candidates = [];
        for (var i = 0; i < links.length; i++) {
            var href = links[i].getAttribute('href') || '';
            if (re.test(href)) candidates.push(links[i]);
        }
        if (!candidates.length) return null;

        var pid = productId != null ? String(productId) : '';

        for (var pass = 0; pass < 2; pass++) {
            for (var j = 0; j < candidates.length; j++) {
                var a = candidates[j];
                var root = listingCardRoot(a);
                if (pid && root && root.querySelector('[data-sb-card="' + pid + '"]')) continue;
                if (pass === 0 && listingLinkSkippable(a)) continue;
                return a;
            }
        }
        return candidates[candidates.length - 1];
    }

    /**
     * Dawn / OS 2.0: place the bar inside the main .card__content (with price), after .card-information,
     * not inside the media overlay column or after .card on .card-wrapper.
     */
    function resolveListingInsert(anchor) {
        if (!anchor) return null;
        var sel = sbSelectors();
        var customParent = closestFirstMatch(anchor, sel.listingBarContainer);
        if (customParent) {
            var beforeEl = queryFirstMatch(customParent, sel.listingBarBefore);
            if (beforeEl && customParent.contains(beforeEl)) {
                return { parent: customParent, before: beforeEl };
            }
            return { parent: customParent, before: null };
        }
        var card = anchor.closest('.card') || anchor.closest('.product-card');
        if (card) {
            var contents = card.querySelectorAll('.card__content');
            var content = null;
            for (var i = 0; i < contents.length; i++) {
                if (contents[i].querySelector('.card-information')) {
                    content = contents[i];
                    break;
                }
            }
            if (!content && contents.length) {
                content = contents[contents.length - 1];
            }
            if (content) {
                var directInfo = null;
                var ch = content.children;
                for (var j = 0; j < ch.length; j++) {
                    if (ch[j].classList && ch[j].classList.contains('card-information')) {
                        directInfo = ch[j];
                        break;
                    }
                }
                if (directInfo) {
                    return { parent: content, before: directInfo.nextSibling };
                }
                var nested = content.querySelector('.card-information');
                if (nested && nested.parentNode) {
                    return { parent: nested.parentNode, before: nested.nextSibling };
                }
                return { parent: content, before: null };
            }
        }
        var wrap = anchor.closest('.card-wrapper');
        if (wrap) return { parent: wrap, before: null };
        var tile = anchor.closest('.product-item, .grid-product__card, .spf-product-card, [data-product-item]');
        if (tile) return { parent: tile, before: null };
        var cell = listingCardRoot(anchor);
        if (cell) return { parent: cell, before: null };
        return { parent: anchor.parentElement, before: anchor.nextSibling };
    }

    function pickListingVariant(productLike) {
        var vars = (productLike && productLike.variants) ? productLike.variants : [];
        if (!vars.length) return null;
        var i;
        for (i = 0; i < vars.length; i++) {
            if (vars[i].available === false) continue;
            var q = vars[i].inventory_quantity;
            if (q === null || q === undefined) continue;
            var n = parseInt(q, 10);
            if (isFinite(n) && n > 0) return vars[i];
        }
        for (i = 0; i < vars.length; i++) {
            if (vars[i].available === false) continue;
            var q2 = vars[i].inventory_quantity;
            if (q2 === null || q2 === undefined) return vars[i];
        }
        for (i = 0; i < vars.length; i++) {
            if (vars[i].available !== false) return vars[i];
        }
        return vars[0];
    }

    /** Grids only show .sb-stock — never empty labels like product-page sold_out. */
    function stateForListingProduct(productLike) {
        var v = pickListingVariant(productLike);
        var st = stateForVariant(v);
        if (st.kind === 'sold_out') {
            return {
                kind: 'sold_out',
                percent: 0,
                label: t('scarcity_label_listing_sold_out', 'Sold out'),
                header: ''
            };
        }
        return st;
    }

    function mountListingWidget(anchor, productLike) {
        var insert = resolveListingInsert(anchor);
        if (!insert || !insert.parent) return;
        if (insert.parent.querySelector('[data-sb-card="' + String(productLike.id) + '"]')) return;

        var wrap = document.createElement('div');
        wrap.setAttribute('data-sb-card', String(productLike.id));
        wrap.className = 'sb-root sb-listing';
        var ff = fontFamily();
        if (ff) wrap.style.fontFamily = ff;

        wrap.innerHTML =
            '<div class="sb-stock">' +
              '<div class="sb-row-top">' +
                '<div class="sb-status"><span class="sb-dot sb-pulse"></span><span class="sb-label"></span></div>' +
                '<div class="sb-sub"></div>' +
              '</div>' +
              '<div class="sb-track"><div class="sb-fill"></div></div>' +
            '</div>';

        if (insert.before) {
            insert.parent.insertBefore(wrap, insert.before);
        } else {
            insert.parent.appendChild(wrap);
        }

        var st = stateForListingProduct(productLike);
        applyScarcityColors(wrap, st);
        wrap.querySelector('.sb-label').textContent = st.label;
        wrap.querySelector('.sb-sub').textContent = st.header || '';
        wrap.querySelector('.sb-fill').style.width = String(st.percent || 0) + '%';

        var br = parseInt((scarcityCfg().bar_radius != null ? scarcityCfg().bar_radius : 999), 10);
        if (isFinite(br)) {
            wrap.querySelectorAll('.sb-track').forEach(function (el) { el.style.borderRadius = br + 'px'; });
        }
        paintBarTrackAndFill(wrap, st);
        requestAnimationFrame(function () {
            paintBarTrackAndFill(wrap, st);
        });
    }

    /** Handle (lowercase) → product payload for listing cards (SSR + API); used to remount after faceted DOM swaps / load more. */
    var listingProductByHandle = {};
    var discoverTimer = null;
    var sbListingSectionBound = false;
    var sbListingMo = null;

    function rememberListingProduct(pl) {
        if (!pl || pl.handle == null) return;
        listingProductByHandle[String(pl.handle).toLowerCase()] = pl;
    }

    function discoverAllowsTemplate() {
        if (!CONFIG || !CONFIG.pages) return false;
        var tpl = data.templateName || '';
        if (tpl === 'index' && CONFIG.pages.index) return true;
        if (tpl === 'collection' && CONFIG.pages.collection) return true;
        if (tpl === 'search' && CONFIG.pages.search) return true;
        return false;
    }

    /** Mount every visible card for this product (facets / infinite scroll can add multiple passes). */
    function syncCachedProductToDom(pl) {
        if (!pl || !pl.handle) return;
        if (!passesTargeting(pl)) return;
        var step;
        for (step = 0; step < 40; step++) {
            var a = findProductAnchor(pl.handle, pl.id);
            if (!a) break;
            mountListingWidget(a, pl);
        }
    }

    function initListingFromLiquid() {
        var rows = data.listingProducts;
        if (!rows || !rows.length) return;
        for (var i = 0; i < rows.length; i++) {
            var p = rows[i];
            rememberListingProduct(p);
            if (!passesTargeting(p)) continue;
            var a = findProductAnchor(p.handle, p.id);
            if (a) mountListingWidget(a, p);
        }
    }

    function discoverFromDom() {
        if (!discoverAllowsTemplate()) return;

        var links = document.querySelectorAll('a[href*="/products/"]');
        var re = /\/products\/([^/?#]+)/i;
        var visibleHandles = [];
        var seen = {};
        var i;
        for (i = 0; i < links.length; i++) {
            var href = links[i].getAttribute('href') || '';
            var m = href.match(re);
            if (!m) continue;
            var h = decodeURIComponent(m[1]).toLowerCase();
            if (!h || seen[h]) continue;
            seen[h] = true;
            visibleHandles.push(h);
            if (visibleHandles.length >= 48) break;
        }

        for (i = 0; i < visibleHandles.length; i++) {
            var pl = listingProductByHandle[visibleHandles[i]];
            if (pl) syncCachedProductToDom(pl);
        }

        var needFetch = [];
        for (i = 0; i < visibleHandles.length; i++) {
            if (!listingProductByHandle[visibleHandles[i]]) needFetch.push(visibleHandles[i]);
        }
        if (!needFetch.length) return;

        var url = data.apiBase + '/api/storefront/listing-inventory?shop=' + encodeURIComponent(data.shopDomain)
            + '&handles=' + encodeURIComponent(needFetch.join(','));
        fetch(url, { credentials: 'omit' })
            .then(function (r) { return r.json(); })
            .then(function (body) {
                if (!body || !body.ok || !body.products) return;
                Object.keys(body.products).forEach(function (h) {
                    var row = body.products[h];
                    var pl = {
                        id: row.id,
                        handle: row.handle,
                        title: row.title,
                        collections: row.collection_ids || [],
                        variants: (row.variants || []).map(function (v) {
                            return {
                                id: v.id,
                                title: v.title,
                                available: !!v.available,
                                inventory_quantity: v.inventory_quantity,
                                price: 0
                            };
                        })
                    };
                    rememberListingProduct(pl);
                    syncCachedProductToDom(pl);
                });
            })
            .catch(function () { /* ignore */ });
    }

    function scheduleDiscover() {
        if (discoverTimer) clearTimeout(discoverTimer);
        discoverTimer = setTimeout(discoverFromDom, 350);
    }

    function bindListingDomRefresh() {
        if (sbListingSectionBound) return;
        sbListingSectionBound = true;
        document.addEventListener('shopify:section:load', function () { scheduleDiscover(); });
        document.addEventListener('shopify:section:unload', function () { scheduleDiscover(); });
        /* Dawn replaces the grid via fetch (no section:load); back/forward after filters. */
        window.addEventListener('popstate', function () { scheduleDiscover(); });
    }

    function initListing() {
        initListingFromLiquid();
        if (!discoverAllowsTemplate()) return;
        bindListingDomRefresh();
        scheduleDiscover();
        try {
            if (!sbListingMo) {
                sbListingMo = new MutationObserver(scheduleDiscover);
                sbListingMo.observe(document.body, { childList: true, subtree: true });
            }
        } catch (_) {}
    }

    function boot() {
        fetchConfig().then(function (config) {
            if (!config || !config.ok || !config.enabled) return;
            CONFIG = config;

            var name = data.templateName || '';
            var pages = CONFIG.pages || {};

            if (name === 'product') {
                if (!pages.product) return;
                emitOnHistoryChange();
                initProductPage();
            } else if (name === 'collection') {
                if (!pages.collection) return;
                initListing();
            } else if (name === 'search') {
                if (!pages.search) return;
                initListing();
            } else if (name === 'index') {
                if (!pages.index) return;
                initListing();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
