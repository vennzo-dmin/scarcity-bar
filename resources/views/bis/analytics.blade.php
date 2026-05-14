@extends('bis._layouts.app')

@section('bis-content')
    <div class="bis-header">
        <div>
            <h1 class="bis-title">Analytics</h1>
            <div class="bis-subtitle">Sends, conversions, and recovered revenue — last {{ $days }} days.</div>
        </div>
        <form method="GET" class="bis-form bis-inline-field">
            <input type="hidden" name="host" value="{{ $host }}">
            <label for="bisAnalyticsDays" class="bis-sr-only">Date range</label>
            <select id="bisAnalyticsDays" name="days" onchange="this.form.submit()">
                @foreach([7,14,30,60,90] as $d)
                    <option value="{{ $d }}" @selected($days==$d)>Last {{ $d }} days</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="bis-kpis">
        <div class="bis-kpi">
            <div class="bis-kpi-label">Sent</div>
            <div class="bis-kpi-value">{{ number_format($deliveries['sent'] ?? 0) }}</div>
        </div>
        <div class="bis-kpi">
            <div class="bis-kpi-label">Failed</div>
            <div class="bis-kpi-value">{{ number_format($deliveries['failed'] ?? 0) }}</div>
        </div>
        <div class="bis-kpi">
            <div class="bis-kpi-label">Clicks</div>
            <div class="bis-kpi-value">{{ number_format($deliveries['clicked'] ?? 0) }}</div>
        </div>
        <div class="bis-kpi">
            <div class="bis-kpi-label">Recovered revenue</div>
            <div class="bis-kpi-value">${{ number_format((float)$recovered, 2) }}</div>
        </div>
    </div>

    <div class="bis-card">
        <div class="bis-card-title">Sends by type</div>
        <div class="bis-table-wrap">
        <table class="bis-table">
            <thead><tr><th>Type</th><th>Sent</th></tr></thead>
            <tbody>
            @foreach($sent_by_type as $type => $count)
                <tr><td>{{ $type }}</td><td>{{ $count }}</td></tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>

    <div class="bis-card">
        <div class="bis-card-title">Top demanded products</div>
        <div class="bis-table-wrap">
        <table class="bis-table">
            <thead><tr><th>Product</th><th>Demand</th></tr></thead>
            <tbody>
            @foreach($top_products as $row)
                <tr><td>{{ $row->product_title }}</td><td>{{ $row->demand }}</td></tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>

    <div class="bis-card">
        <div class="bis-card-title">Timeline</div>
        <div class="bis-table-wrap">
        <table class="bis-table">
            <thead><tr><th>Date</th><th>Sent</th></tr></thead>
            <tbody>
            @foreach($timeline as $row)
                <tr><td>{{ $row->d }}</td><td>{{ $row->c }}</td></tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
@endsection
