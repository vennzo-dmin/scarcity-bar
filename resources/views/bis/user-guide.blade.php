@extends('bis._layouts.app')

@section('bis-content')
    <div class="bis-header">
        <div>
            <h1 class="bis-title">User guide</h1>
            <div class="bis-subtitle">Set up Scarcity Bar: live stock bar, sold-out capture, and restock emails.</div>
        </div>
    </div>

    {{-- ─────────────────────────────────────  Quick start  ───────────────────────────────────── --}}
    <div class="bis-card bis-card--spotlight">
        <div class="bis-card-title">
            <i class="bi bi-rocket-takeoff"></i>
            5-minute setup
        </div>
        <ol class="bis-list-prose">
            <li><strong>Pick a plan.</strong> Open <em>Plans</em>, choose monthly or annual. Both unlock the full storefront + automation stack.</li>
            <li><strong>Activate the app embed.</strong> Use <em>Open theme editor</em> on the Dashboard, enable <strong>Scarcity Bar</strong> under App embeds, and save. Set the embed’s <strong>App URL</strong> to this app’s public domain.</li>
            <li><strong>Tune the bar &amp; rules.</strong> Open <em>Widget</em> — max stock, low-stock threshold, colors, which templates show the bar (product, collection, home, search), and targeting.</li>
            <li><strong>Templates &amp; copy.</strong> Edit restock email bodies under <em>Templates</em>; customer-facing strings under <em>Translations</em>.</li>
            <li><strong>Integrations.</strong> Optional SMTP / SMS providers under <em>Integrations</em> for branded delivery.</li>
        </ol>
        <p class="bis-hint bis-hint-tight">
            <strong>Test:</strong> open a product with variants, switch options — the bar should move. Mark a variant OOS — the email form should replace the bar.
        </p>
    </div>

    {{-- ─────────────────────────────────────  How it works  ───────────────────────────────────── --}}
    <div class="bis-card">
        <div class="bis-card-title">How the app works (the 30-second version)</div>
        <ol class="bis-subtitle" style="padding-left:20px;line-height:1.9;margin:0;">
            <li>A shopper visits an out-of-stock product page on your store. Our widget shows them a "Notify Me" form.</li>
            <li>They enter their email (and optionally phone). The subscription is saved to your <em>Waitlist</em>.</li>
            <li>When you restock the product, Shopify pings us via webhook. We check your inventory rules — if conditions are met, we queue alerts.</li>
            <li>Alerts go out via your email + SMS providers, batched according to your sending strategy. Each click is signed and tracked.</li>
            <li>If a recipient places an order within the attribution window, the order is credited to the alert in <em>Analytics</em> as recovered revenue.</li>
        </ol>
    </div>

    {{-- ─────────────────────────────────────  Feature reference  ───────────────────────────────────── --}}
    <div class="bis-card">
        <div class="bis-card-title">What each section does</div>

        <div class="bis-section-title"><i class="bi bi-ui-checks-grid"></i> Widget</div>
        <p class="bis-subtitle" style="margin:0 0 10px 0;">
            Controls how the storefront widget looks (button label, color, position) and the rules that decide
            when alerts actually fire:
        </p>
        <ul class="bis-subtitle" style="padding-left:20px;line-height:1.7;margin:0 0 14px 0;">
            <li><strong>Inventory rules</strong> — when does "back in stock" count? Choose if you want to fire on
                <em>any location</em>, only sellable locations, or only when Online Store can sell it. Use the threshold
                to ignore "1 unit available" if items typically sell out instantly.</li>
            <li><strong>Price-drop rules</strong> — minimum dollar / percent drop needed before a price-alert is sent.
                Stops customers from being notified about $0.04 currency rounding.</li>
            <li><strong>Sending strategy</strong> — when 500 people are waiting for the same restock, do you blast
                everyone at once (instant but stampedes inventory) or stagger them in small batches with a delay?
                "VIPs first" lets tagged customers get the alert ahead of the queue.</li>
        </ul>

        <div class="bis-section-title"><i class="bi bi-envelope-paper"></i> Templates</div>
        <p class="bis-subtitle" style="margin:0 0 14px 0;">
            The actual message sent to subscribers. Variables you can use in subject and body:
            <code>@{{product_title}}</code>, <code>@{{variant_title}}</code>,
            <code>@{{customer_first_name}}</code>, <code>@{{cta_link}}</code>,
            <code>@{{unsubscribe_link}}</code>. You can have separate templates per locale (e.g. <code>en</code>, <code>fr</code>).
        </p>

        <div class="bis-section-title"><i class="bi bi-people"></i> Waitlist</div>
        <p class="bis-subtitle" style="margin:0 0 14px 0;">
            Every signup. Filter by alert type, status, or product. <strong>Export CSV</strong> downloads a
            full breakdown including last delivery outcome and per-subscriber attributed revenue — drop it
            straight into Klaviyo, Mailchimp, or Excel for follow-up campaigns.
        </p>

        <div class="bis-section-title"><i class="bi bi-magic"></i> Automation</div>
        <p class="bis-subtitle" style="margin:0 0 14px 0;">
            Rules that run automatically when conditions match — e.g. "if 50+ subscribers are waiting on a product,
            tag the product so my purchasing team sees it" or "auto-cancel waitlist signups older than 90 days."
        </p>

        <div class="bis-section-title"><i class="bi bi-graph-up"></i> Analytics</div>
        <p class="bis-subtitle" style="margin:0 0 14px 0;">
            How the app is performing. Headline numbers: signups captured, alerts sent, click-through rate, and
            <strong>recovered revenue</strong> (orders attributed to alerts within the attribution window).
        </p>

        <div class="bis-section-title"><i class="bi bi-list-ul"></i> Delivery Logs</div>
        <p class="bis-subtitle" style="margin:0 0 14px 0;">
            Every send attempt with status (sent / failed), provider used, and the error if it failed.
            Use this to debug if a customer says they didn't receive their alert.
        </p>

        <div class="bis-section-title"><i class="bi bi-plug"></i> Integrations</div>
        <p class="bis-subtitle" style="margin:0 0 14px 0;">
            Connect your own email/SMS sender. <strong>Email:</strong> use the default app mailer to start, or paste
            SMTP credentials from your provider (Mailgun, SendGrid, AWS SES, Postmark, your shop's Google Workspace, etc.)
            so alerts go from your own domain — better deliverability, better trust, your branding in the inbox.
            <strong>SMS:</strong> add Twilio or MessageBird credentials. Until SMS credentials are filled in, SMS
            alerts are recorded but not sent.
        </p>

        <div class="bis-section-title"><i class="bi bi-gem"></i> Plans</div>
        <p class="bis-subtitle" style="margin:0 0 0 0;">
            Switch between monthly and annual at any time. Charges are processed by Shopify and appear on your
            regular Shopify bill. Cancel anytime — your subscription stops at the end of the current period.
        </p>
    </div>

    {{-- ─────────────────────────────────────  Tips  ───────────────────────────────────── --}}
    <div class="bis-card">
        <div class="bis-card-title">Pro tips</div>
        <ul class="bis-subtitle" style="padding-left:20px;line-height:1.8;margin:0;">
            <li><strong>Always send a test before going live.</strong> Open <em>Integrations</em> → <em>Send a test email/SMS</em>
                to confirm credentials work end-to-end.</li>
            <li><strong>If you sell from a warehouse that doesn't ship online</strong> (pickup-only, wholesale-only, etc.),
                set the inventory rule to <em>Stock is available to the Online Store</em> — otherwise you'll fire alerts
                for stock customers can't actually buy.</li>
            <li><strong>For hot drops with limited stock,</strong> set sending mode to <em>Staggered</em> with a small
                batch size. This prevents the entire alert list from clicking at once and the same fast clickers
                buying everything in 30 seconds.</li>
            <li><strong>Tag your VIP customers in Shopify</strong> (e.g. tag <code>vip</code> or <code>top-spender</code>),
                add the same tag in Widget → Sending → VIP tags, and turn on <em>VIPs first</em>. They'll get alerts
                ahead of the queue.</li>
            <li><strong>Treat the CSV export as your source of truth for re-engagement.</strong> Filter by
                <em>active</em> + <em>back_in_stock</em>, export, and use it to send targeted campaigns from your ESP
                for products you're permanently discontinuing.</li>
        </ul>
    </div>

    {{-- ─────────────────────────────────────  Troubleshooting  ───────────────────────────────────── --}}
    <div class="bis-card">
        <div class="bis-card-title">Troubleshooting</div>

        <div class="bis-section-title">"Notify Me" button doesn't appear on my storefront</div>
        <ul class="bis-subtitle" style="padding-left:20px;line-height:1.7;margin:0 0 14px 0;">
            <li>Confirm the BackInStock app embed is toggled <strong>on</strong> in your live theme (Dashboard → Open theme editor).</li>
            <li>Confirm the product is actually out of stock — the widget hides itself when stock is available.</li>
            <li>Open the product page, view source, and search for <code>scarcity-bar.js</code>. If it's missing, the embed isn't active.</li>
        </ul>

        <div class="bis-section-title">Customers signed up but no email arrived</div>
        <ul class="bis-subtitle" style="padding-left:20px;line-height:1.7;margin:0 0 14px 0;">
            <li>Open <em>Delivery Logs</em>. If status is <em>failed</em>, the error will tell you why
                (auth rejected, sender unverified, etc.).</li>
            <li>Open <em>Integrations</em> and click <em>Send a test email</em> to confirm your SMTP works.</li>
            <li>If using your own SMTP: most providers require the From-address to be verified
                (Mailgun, SendGrid, SES). Check your provider dashboard.</li>
        </ul>

        <div class="bis-section-title">Stock is back but no alert fired</div>
        <ul class="bis-subtitle" style="padding-left:20px;line-height:1.7;margin:0 0 14px 0;">
            <li>Check Widget → Inventory rules. The default minimum threshold is 1 — if your rule is set to a
                higher number, alerts wait until stock crosses it.</li>
            <li>Check the location rule — if you only restocked at a non-sellable location and your rule is
                "sellable only," the app will (correctly) not fire.</li>
            <li>Check that <code>products/update</code> and <code>inventory_levels/update</code> webhooks were
                installed (the queue worker logs this on install).</li>
        </ul>
    </div>

    {{-- ─────────────────────────────────────  GDPR / Compliance  ───────────────────────────────────── --}}
    <div class="bis-card">
        <div class="bis-card-title">Privacy &amp; compliance</div>
        <p class="bis-subtitle" style="margin:0;">
            All subscriber data is stored on your tenant only and is never shared. Customers can unsubscribe at any
            time via the link in every email. We honor Shopify's mandatory GDPR webhooks
            (<code>customers/data_request</code>, <code>customers/redact</code>, <code>shop/redact</code>) — when a
            customer requests deletion or you uninstall the app, their data is purged automatically. Provider
            credentials (SMTP password, Twilio token, MessageBird key) are encrypted at rest using Laravel's
            encrypted-cast on the database column.
        </p>
    </div>
@endsection
