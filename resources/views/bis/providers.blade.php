@extends('bis._layouts.app')

@section('bis-content')
    <div class="bis-header">
        <div>
            <h1 class="bis-title">Integrations</h1>
            <div class="bis-subtitle">SMTP, Twilio, or MessageBird — so restock and scarcity notifications send from your brand.</div>
        </div>
        <button class="bis-btn bis-btn-primary" id="bisSaveProviders"><i class="bi bi-save"></i> Save changes</button>
    </div>

    <form id="bisProvidersForm" class="bis-form">
        <div class="bis-card">
            <div class="bis-card-title">Email delivery</div>
            <p class="bis-hint" style="margin-bottom:14px;">
                Use the app's default mailer to get started, or plug in your own SMTP credentials for branded, authenticated delivery (SPF/DKIM from your own domain).
            </p>

            <div class="row">
                <div>
                    <label>Driver</label>
                    <select name="email[driver]" id="emailDriver">
                        <option value="default" @selected(($providers['email']['driver'] ?? 'default')==='default')>Default (app mailer)</option>
                        <option value="smtp"    @selected(($providers['email']['driver'] ?? '')==='smtp')>Custom SMTP</option>
                    </select>
                </div>
                <div>
                    <label>From address</label>
                    <input type="email" name="email[from_address]" value="{{ $providers['email']['from_address'] ?? '' }}" placeholder="hello@your-shop.com">
                </div>
                <div>
                    <label>From name</label>
                    <input type="text" name="email[from_name]" value="{{ $providers['email']['from_name'] ?? '' }}" placeholder="Your Shop">
                </div>
            </div>

            <div id="smtpFields" style="display:none;">
                <div class="bis-section-title">SMTP credentials</div>
                <div class="row">
                    <div>
                        <label>SMTP host</label>
                        <input type="text" name="email[smtp_host]" value="{{ $providers['email']['smtp_host'] ?? '' }}" placeholder="smtp.mailgun.org">
                    </div>
                    <div>
                        <label>Port</label>
                        <input type="number" name="email[smtp_port]" value="{{ $providers['email']['smtp_port'] ?? 587 }}">
                    </div>
                    <div>
                        <label>Encryption</label>
                        <select name="email[smtp_encryption]">
                            <option value="tls"  @selected(($providers['email']['smtp_encryption'] ?? 'tls')==='tls')>TLS</option>
                            <option value="ssl"  @selected(($providers['email']['smtp_encryption'] ?? '')==='ssl')>SSL</option>
                            <option value="none" @selected(($providers['email']['smtp_encryption'] ?? '')==='none')>None</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div>
                        <label>Username</label>
                        <input type="text" name="email[smtp_user]" value="{{ $providers['email']['smtp_user'] ?? '' }}" autocomplete="off">
                    </div>
                    <div>
                        <label>Password</label>
                        <input type="password" name="email[smtp_pass]" value="{{ $providers['email']['smtp_pass'] ?? '' }}" autocomplete="new-password">
                        <div class="bis-hint">Stored encrypted at rest. Leave as <code>********</code> to keep the existing password.</div>
                    </div>
                </div>
            </div>

            <div class="bis-section-title">Send a test email</div>
            <div class="row">
                <div>
                    <label>Recipient</label>
                    <input type="email" id="testEmailTo" placeholder="you@your-shop.com">
                </div>
                <div style="display:flex; align-items:flex-end;">
                    <button type="button" class="bis-btn" id="bisTestEmail"><i class="bi bi-send"></i> Send test</button>
                </div>
            </div>
        </div>

        <div class="bis-card">
            <div class="bis-card-title">SMS delivery</div>
            <p class="bis-hint" style="margin-bottom:14px;">
                SMS is opt-in only. Plug in Twilio or MessageBird; the app will never send SMS without a valid driver configured.
            </p>

            <div class="row">
                <div>
                    <label>Driver</label>
                    <select name="sms[driver]" id="smsDriver">
                        <option value="log"         @selected(($providers['sms']['driver'] ?? 'log')==='log')>SMS disabled (no messages sent)</option>
                        <option value="twilio"      @selected(($providers['sms']['driver'] ?? '')==='twilio')>Twilio</option>
                        <option value="messagebird" @selected(($providers['sms']['driver'] ?? '')==='messagebird')>MessageBird</option>
                    </select>
                    <div class="bis-hint">
                        Pick "SMS disabled" if you only want email alerts. With this driver, SMS subscriptions are
                        still captured into your waitlist (so you don't lose the opt-in if you connect Twilio later)
                        but no SMS goes out — the attempt is just recorded in <em>Delivery Logs</em> for your audit
                        trail. Switch to Twilio or MessageBird to start sending.
                    </div>
                </div>
            </div>

            <div id="twilioFields" style="display:none;">
                <div class="bis-section-title">Twilio credentials</div>
                <div class="row">
                    <div>
                        <label>Account SID</label>
                        <input type="text" name="sms[twilio][account_sid]" value="{{ $providers['sms']['twilio']['account_sid'] ?? '' }}" autocomplete="off">
                    </div>
                    <div>
                        <label>Auth token</label>
                        <input type="password" name="sms[twilio][auth_token]" value="{{ $providers['sms']['twilio']['auth_token'] ?? '' }}" autocomplete="new-password">
                        <div class="bis-hint">Stored encrypted. Leave as <code>********</code> to keep existing.</div>
                    </div>
                    <div>
                        <label>From number</label>
                        <input type="text" name="sms[twilio][from]" value="{{ $providers['sms']['twilio']['from'] ?? '' }}" placeholder="+15555551234">
                    </div>
                </div>
            </div>

            <div id="messagebirdFields" style="display:none;">
                <div class="bis-section-title">MessageBird credentials</div>
                <div class="row">
                    <div>
                        <label>API key</label>
                        <input type="password" name="sms[messagebird][api_key]" value="{{ $providers['sms']['messagebird']['api_key'] ?? '' }}" autocomplete="new-password">
                        <div class="bis-hint">Stored encrypted. Leave as <code>********</code> to keep existing.</div>
                    </div>
                    <div>
                        <label>Originator</label>
                        <input type="text" name="sms[messagebird][originator]" value="{{ $providers['sms']['messagebird']['originator'] ?? '' }}" placeholder="YourShop">
                    </div>
                </div>
            </div>

            <div class="bis-section-title">Send a test SMS</div>
            <div class="row">
                <div>
                    <label>Recipient (E.164)</label>
                    <input type="text" id="testSmsTo" placeholder="+15555551234">
                </div>
                <div style="display:flex; align-items:flex-end;">
                    <button type="button" class="bis-btn" id="bisTestSms"><i class="bi bi-send"></i> Send test</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function togglePanels() {
        document.getElementById('smtpFields').style.display =
            document.getElementById('emailDriver').value === 'smtp' ? 'block' : 'none';

        const sms = document.getElementById('smsDriver').value;
        document.getElementById('twilioFields').style.display      = sms === 'twilio'      ? 'block' : 'none';
        document.getElementById('messagebirdFields').style.display = sms === 'messagebird' ? 'block' : 'none';
    }
    document.getElementById('emailDriver').addEventListener('change', togglePanels);
    document.getElementById('smsDriver').addEventListener('change', togglePanels);
    togglePanels();

    function serializeProvidersForm(form) {
        const data = { email: {}, sms: { twilio: {}, messagebird: {} } };
        new FormData(form).forEach((value, key) => {
            // email[field]
            let m = key.match(/^email\[(\w+)\]$/);
            if (m) { data.email[m[1]] = value; return; }
            // sms[driver]
            m = key.match(/^sms\[driver\]$/);
            if (m) { data.sms.driver = value; return; }
            // sms[twilio][field] / sms[messagebird][field]
            m = key.match(/^sms\[(\w+)\]\[(\w+)\]$/);
            if (m) { data.sms[m[1]][m[2]] = value; return; }
        });
        if (data.email.smtp_port) data.email.smtp_port = Number(data.email.smtp_port);
        return data;
    }

    document.getElementById('bisSaveProviders').addEventListener('click', async () => {
        const form = document.getElementById('bisProvidersForm');
        clearFormErrors(form);
        const payload = serializeProvidersForm(form);
        try {
            await apiPost('{{ route('bis.providers.update') }}', payload, '{{ csrf_token() }}');
            showToast(app, 'Saved');
        } catch (e) {
            const shown = showFormErrors(form, e);
            showToast(app, shown ? 'Please fix the highlighted fields' : (e.message || 'Save failed'), true);
        }
    });

    async function sendTest(channel, to) {
        if (!to) { showToast(app, 'Recipient required', true); return; }
        // Ship the form's LIVE values so the test uses whatever the merchant
        // has typed in right now — saved or not. Otherwise a new From address
        // in the form wouldn't take effect until they clicked Save first.
        const config = serializeProvidersForm(document.getElementById('bisProvidersForm'));
        try {
            const res = await apiPost('{{ route('bis.providers.test') }}', { channel, to, config }, '{{ csrf_token() }}');
            if (res && res.ok) {
                showToast(app, 'Test sent via ' + (res.provider || channel));
            } else {
                showToast(app, 'Test failed: ' + ((res && res.error) || 'unknown'), true);
            }
        } catch (e) {
            showToast(app, 'Test failed: ' + (e.message || 'unknown'), true);
        }
    }
    document.getElementById('bisTestEmail').addEventListener('click', () => {
        sendTest('email', document.getElementById('testEmailTo').value.trim());
    });
    document.getElementById('bisTestSms').addEventListener('click', () => {
        sendTest('sms', document.getElementById('testSmsTo').value.trim());
    });
</script>
@endpush
