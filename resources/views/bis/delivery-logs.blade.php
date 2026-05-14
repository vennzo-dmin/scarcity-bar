@extends('bis._layouts.app')

@section('bis-content')
    <div class="bis-header">
        <div>
            <h1 class="bis-title">Delivery logs</h1>
            <div class="bis-subtitle">Every send attempt with provider result.</div>
        </div>
    </div>

    <div class="bis-card">
        <form method="GET" class="bis-form bis-toolbar">
            <input type="hidden" name="host" value="{{ $host }}">
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    @foreach(['queued','sent','failed','bounced','clicked'] as $s)
                        <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Channel</label>
                <select name="channel">
                    <option value="">All</option>
                    <option value="email" @selected(request('channel')==='email')>Email</option>
                    <option value="sms"   @selected(request('channel')==='sms')>SMS</option>
                </select>
            </div>
            <button type="submit" class="bis-btn bis-btn-primary">Filter</button>
        </form>
    </div>

    <div class="bis-card">
        <div class="bis-table-wrap">
        <table class="bis-table">
            <thead><tr><th>ID</th><th>Type</th><th>Channel</th><th>Provider</th><th>Recipient</th><th>Status</th><th>Error</th><th>Sent</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>#{{ $row->id }}</td>
                    <td>{{ $row->type }}</td>
                    <td>{{ $row->channel }}</td>
                    <td>{{ $row->provider ?? '—' }}</td>
                    <td>{{ $row->recipient }}</td>
                    <td><span class="bis-badge bis-badge-{{ $row->status }}">{{ $row->status }}</span></td>
                    <td class="bis-text-danger bis-text-small">{{ $row->error }}</td>
                    <td>{{ $row->sent_at?->diffForHumans() ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="bis-subtitle">No deliveries logged.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        <div class="bis-pagination">{{ $rows->links() }}</div>
    </div>
@endsection
