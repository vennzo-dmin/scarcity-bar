@extends('bis._layouts.app')

@section('bis-content')
    <div class="bis-header">
        <div>
            <h1 class="bis-title">Automation</h1>
            <div class="bis-subtitle">Internal alerts when demand spikes or stock runs low — separate from the customer-facing scarcity bar.</div>
        </div>
    </div>

    <div class="bis-card">
        <div class="bis-card-title">Create a rule</div>
        <form id="bisRuleForm" class="bis-form">
            <div class="row">
                <div>
                    <label>Name</label>
                    <input type="text" name="name" placeholder="Notify team at 50 waitlist">
                </div>
                <div>
                    <label>Type</label>
                    <select name="type">
                        <option value="low_stock">Low stock</option>
                        <option value="high_demand">High demand</option>
                        <option value="internal_notify">Internal notify</option>
                        <option value="export">Export</option>
                    </select>
                </div>
                <div>
                    <label>Threshold</label>
                    <input type="number" name="config[threshold]" value="50" min="1">
                </div>
                <div>
                    <label>Notify email</label>
                    <input type="email" name="config[notify_email]" placeholder="team@example.com">
                </div>
            </div>
            <button type="button" class="bis-btn bis-btn-primary" id="bisSaveRule">Save rule</button>
        </form>
    </div>

    <div class="bis-card">
        <div class="bis-card-title">Existing rules</div>
        <div class="bis-table-wrap">
        <table class="bis-table">
            <thead><tr><th>Name</th><th>Type</th><th>Active</th><th>Last run</th><th></th></tr></thead>
            <tbody>
            @forelse($rules as $r)
                <tr>
                    <td>{{ $r->name }}</td>
                    <td>{{ $r->type }}</td>
                    <td>{{ $r->is_active ? 'Yes' : 'No' }}</td>
                    <td>{{ $r->last_run_at?->diffForHumans() ?? '—' }}</td>
                    <td><button class="bis-btn bis-btn-danger bis-btn-sm" onclick="bisDeleteRule({{ $r->id }})"><i class="bi bi-trash"></i></button></td>
                </tr>
            @empty
                <tr><td colspan="5" class="bis-subtitle">No rules configured.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('bisSaveRule').addEventListener('click', async () => {
        const form = document.getElementById('bisRuleForm');
        clearFormErrors(form);
        const data = { name:'', type:'low_stock', is_active:true, config:{} };
        new FormData(form).forEach((v, k) => {
            const m = k.match(/^config\[(\w+)\]$/);
            if (m) data.config[m[1]] = v;
            else data[k] = v;
        });
        try {
            await apiPost('{{ route('bis.automation.store') }}', data, '{{ csrf_token() }}');
            showToast(app, 'Rule saved');
            navigation('/automation');
        } catch (e) {
            const shown = showFormErrors(form, e);
            showToast(app, shown ? 'Please fix the highlighted fields' : (e.message || 'Failed'), true);
        }
    });

    async function bisDeleteRule(id) {
        try {
            await apiDelete('{{ url('/automation') }}/' + id, '{{ csrf_token() }}');
            showToast(app, 'Deleted');
           navigation('/automation');
        } catch (e) { showToast(app, 'Failed', true); }
    }
</script>
@endpush
