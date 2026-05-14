@extends('bis._layouts.app')

@section('bis-content')
    <div class="bis-header">
        <div>
            <h1 class="bis-title">Notify list</h1>
            <div class="bis-subtitle">Shoppers waiting for restock or price-drop alerts. Export for your ESP or follow up manually.</div>
        </div>
        <a class="bis-btn bis-btn-primary" onclick="exportSubscriptionsCsv()">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>

    <div class="bis-card">
        <form method="GET" class="bis-form bis-toolbar">
            <input type="hidden" name="host" value="{{ $host }}">
            <div>
                <label>Search product</label>
                <input name="q" value="{{ request('q') }}" placeholder="Product title">
            </div>
            <div>
                <label>Type</label>
                <select name="type">
                    <option value="">All</option>
                    <option value="back_in_stock" @selected(request('type')==='back_in_stock')>Back in stock</option>
                    <option value="price_drop" @selected(request('type')==='price_drop')>Price drop</option>
                </select>
            </div>
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    @foreach(['active','queued','sent','cancelled','failed'] as $s)
                        <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bis-btn bis-btn-primary">Filter</button>
        </form>
    </div>

    <div class="bis-card">
        <p class="bis-hint bis-hint-spaced">
            <strong>Reach out:</strong> click an email to open your mail client with product context pre-filled, or tap a phone row to copy the number.
        </p>
        <div class="bis-table-wrap">
            <table class="bis-table">
            <thead>
                <tr>
                    <th>ID</th><th>Type</th><th>Product</th><th>Variant</th>
                    <th>Email</th><th>Phone</th><th>Status</th><th>Date</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php
                    $sub      = $row->subscriber;
                    $email    = $sub?->email;
                    $phone    = $sub?->phone;
                    $product  = $row->product_title ?: 'your product';
                    $variant  = ($row->variant_title && $row->variant_title !== 'Default Title')
                                    ? ' — ' . $row->variant_title
                                    : '';
                    $kind     = $row->type === 'price_drop' ? 'price drop' : 'restock';

                    $mailtoSubject = "About your {$kind} alert for {$product}";
                    $mailtoBody    = "Hi,\n\nThanks for joining our waitlist for "
                                   . $product . $variant . ".\n\n"
                                   . "(write your message here)\n\n"
                                   . "— " . (Auth::user()?->name ?? 'the team');
                    $mailtoHref    = $email
                        ? 'mailto:' . rawurlencode($email)
                          . '?subject=' . rawurlencode($mailtoSubject)
                          . '&body='    . rawurlencode($mailtoBody)
                        : null;
                @endphp
                <tr>
                    <td>#{{ $row->id }}</td>
                    <td>{{ str_replace('_', ' ', $row->type) }}</td>
                    <td>{{ $row->product_title }}</td>
                    <td>{{ $row->variant_title && $row->variant_title !== 'Default Title' ? $row->variant_title : '—' }}</td>
                    <td>
                        @if($email)
                            <a href="{{ $mailtoHref }}" target="_blank" rel="noopener" title="Email this customer">
                                <i class="bi bi-envelope"></i> {{ $email }}
                            </a>
                        @else
                            <span class="bis-subtitle">—</span>
                        @endif
                    </td>
                    <td>
                        @if($phone)
                            {{-- A `tel:` link inside Shopify's admin iframe
                                 attempts to navigate the iframe itself, which
                                 on desktop browsers without a tel handler
                                 collapses the embedded app. We render the
                                 number as a click-to-copy button instead. --}}
                            <button type="button" class="bis-btn bis-btn-sm"
                                    title="Click to copy this number"
                                    onclick="bisCopyPhone(this, @js($phone))">
                                <i class="bi bi-telephone"></i> {{ $phone }}
                                <i class="bi bi-clipboard" style="opacity:.55;"></i>
                            </button>
                        @else
                            <span class="bis-subtitle">—</span>
                        @endif
                    </td>
                    <td><span class="bis-badge bis-badge-{{ $row->status }}">{{ $row->status }}</span></td>
                    <td>{{ $row->created_at?->diffForHumans() }}</td>
                    <td>
                        @if($row->status==='active')
                            <button class="bis-btn bis-btn-danger" onclick="bisCancelSub({{ $row->id }})">Cancel</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="bis-subtitle">No subscriptions yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        <div class="bis-pagination">{{ $rows->links() }}</div>
    </div>
@endsection

@push('scripts')
<script>
    async function bisCancelSub(id) {
        try {
            await apiPost('{{ url('/subscriptions') }}/' + id + '/cancel', {}, '{{ csrf_token() }}');
            showToast(app, 'Cancelled');
           navigation('/subscriptions');
        } catch (e) {
            showToast(app, 'Failed', true);
        }
    }

    async function bisCopyPhone(btn, phone) {
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(phone);
            } else {
                // Fallback for older browsers / Safari in iframes
                const ta = document.createElement('textarea');
                ta.value = phone;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                ta.remove();
            }
            showToast(app, 'Phone number copied');
        } catch (e) {
            showToast(app, 'Could not copy', true);
        }
    }
</script>

<script>
    async function exportSubscriptionsCsv() {
        try {
            const response = await fetch('/subscriptions/export', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/csv,application/csv,text/plain,*/*'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Export failed');
            }

            const blob = await response.blob();
            const blobUrl = window.URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = blobUrl;
            a.download = 'subscriptions.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();

            window.URL.revokeObjectURL(blobUrl);
        } catch (e) {
            console.error(e);
            alert('CSV export failed.');
        }
    }
</script>
@endpush
