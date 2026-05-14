@extends('bis._layouts.app')

@section('bis-content')
    <div class="bis-header">
        <div>
            <h1 class="bis-title">Email &amp; SMS templates</h1>
            <div class="bis-subtitle">Restock and price-drop payloads. Variables: @{{product_title}}, @{{customer_first_name}}, @{{cta_link}}, @{{unsubscribe_link}}.</div>
        </div>
    </div>

    <div class="bis-card">
        <div class="bis-card-title">Create or update a template</div>
        <form id="bisTemplateForm" class="bis-form">
            <div class="row">
                <div>
                    <label>Type</label>
                    <select name="type">
                        <option value="back_in_stock">Back in stock</option>
                        <option value="price_drop">Price drop</option>
                    </select>
                </div>
                <div>
                    <label>Channel</label>
                    <select name="channel">
                        <option value="email">Email</option>
                        <option value="sms">SMS</option>
                    </select>
                </div>
                <div>
                    <label>Locale</label>
                    <input type="text" name="locale" value="en" maxlength="10">
                </div>
            </div>
            <div class="row">
                <div class="bis-form-full">
                    <label>Subject (email only)</label>
                    <input type="text" name="subject" placeholder="&#123;&#123;product_title&#125;&#125; is back in stock">
                </div>
            </div>
            <div class="row">
                <div class="bis-form-full">
                    <label>Body</label>
                    <textarea name="body" rows="8" placeholder="Hi &#123;&#123;customer_first_name&#125;&#125;, ..."></textarea>
                </div>
            </div>
            <button type="button" class="bis-btn bis-btn-primary" id="bisSaveTemplate"><i class="bi bi-save"></i> Save template</button>
        </form>
    </div>

    <div class="bis-card">
        <div class="bis-card-title">Existing templates</div>
        <div class="bis-table-wrap">
        <table class="bis-table">
            <thead><tr><th>Type</th><th>Channel</th><th>Locale</th><th>Subject</th><th>Active</th><th></th></tr></thead>
            <tbody>
            @forelse($templates as $t)
                <tr>
                    <td>{{ $t->type }}</td>
                    <td>{{ $t->channel }}</td>
                    <td>{{ $t->locale }}</td>
                    <td>{{ $t->subject }}</td>
                    <td>{{ $t->is_active ? 'Yes' : 'No' }}</td>
                    <td><button class="bis-btn bis-btn-danger bis-btn-sm" onclick="bisDeleteTemplate({{ $t->id }})"><i class="bi bi-trash"></i></button></td>
                </tr>
            @empty
                <tr><td colspan="6" class="bis-subtitle">No custom templates yet — defaults are used automatically.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('bisSaveTemplate').addEventListener('click', async () => {
        const form = document.getElementById('bisTemplateForm');
        clearFormErrors(form);
        const data = Object.fromEntries(new FormData(form).entries());
        try {
            await apiPost('{{ route('bis.templates.store') }}', data, '{{ csrf_token() }}');
            showToast(app, 'Template saved');
            setTimeout(() => navigation('/templates'), 500);
        } catch (e) {
            const shown = showFormErrors(form, e);
            showToast(app, shown ? 'Please fix the highlighted fields' : (e.message || 'Failed to save template'), true);
        }
    });

    async function bisDeleteTemplate(id) {
        try {
            await apiDelete('{{ url('/templates') }}/' + id, '{{ csrf_token() }}');
            showToast(app, 'Deleted');
           navigation('/templates');
        } catch (e) {
            showToast(app, 'Failed', true);
        }
    }
</script>
@endpush