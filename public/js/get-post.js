(function (window) {
    function _getFetch() {
        if (window.authenticatedFetch) return window.authenticatedFetch;

        if (window.app && window['app-bridge-utils'] && typeof window['app-bridge-utils'].authenticatedFetch === 'function') {
            return window['app-bridge-utils'].authenticatedFetch(window.app);
        }

        return window.fetch.bind(window);
    }

    async function _safeReadText(res) {
        try {
            return await res.text();
        } catch (_) {
            return '';
        }
    }

    async function _parseResponseBody(res) {
        const contentType = (res.headers.get('content-type') || '').toLowerCase();

        try {
            if (contentType.includes('application/json')) {
                return await res.json();
            }
        } catch (_) {
            // ignore and fallback to text
        }

        const text = await _safeReadText(res);
        if (!text) return null;

        try {
            return JSON.parse(text);
        } catch (_) {
            return text;
        }
    }

    function _buildError(res, body) {
        const error = new Error('Request failed');

        error.status = res.status;
        error.data = body;
        error.validationErrors = null;

        if (body && typeof body === 'object') {
            error.message =
                body.message ||
                body.error ||
                `Request failed with status ${res.status}`;

            if (res.status === 422 && body.errors && typeof body.errors === 'object') {
                error.validationErrors = body.errors;
            }
        } else if (typeof body === 'string' && body.trim()) {
            error.message = body;
        } else {
            error.message = `Request failed with status ${res.status}`;
        }

        return error;
    }

    async function _handleResponse(res) {
        const body = await _parseResponseBody(res);

        if (!res.ok) {
            throw _buildError(res, body);
        }

        return body;
    }

    async function apiGet(url) {
        const fetcher = _getFetch();
        const res = await fetcher(url, {
            method: "GET",
            headers: {
                "Accept": "application/json",
            },
        });

        return _handleResponse(res);
    }

    async function apiPost(url, data, csrf) {
        const fetcher = _getFetch();
        const res = await fetcher(url, {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf,
            },
            body: JSON.stringify(data || {}),
        });

        return _handleResponse(res);
    }

    async function apiPostForm(url, formEncodedString, csrf) {
        const fetcher = _getFetch();
        const res = await fetcher(url, {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-TOKEN": csrf,
            },
            body: formEncodedString || "",
        });

        return _handleResponse(res);
    }

    async function apiDelete(url, csrf) {
        const fetcher = _getFetch();
        const res = await fetcher(url, {
            method: "DELETE",
            headers: {
                "Accept": "application/json",
                "X-CSRF-TOKEN": csrf,
            },
        });

        return _handleResponse(res);
    }

    /**
     * Render Laravel validation errors (a `{field: [messages]}` map from a
     * 422 response) inline beneath each form field, and clear any prior ones.
     * Falls back to a toast/alert when no field can be matched.
     *
     * Usage in catch blocks:
     *   } catch (e) {
     *       if (!showFormErrors(form, e) && typeof showToast === 'function') {
     *           showToast(app, e.message || 'Failed', true);
     *       }
     *   }
     */
    function showFormErrors(form, error) {
        if (!form) return false;

        // Clear previous error markers.
        form.querySelectorAll('.bis-field-error').forEach(function (n) { n.remove(); });
        form.querySelectorAll('.is-invalid').forEach(function (n) { n.classList.remove('is-invalid'); });

        var errors = error && error.validationErrors;
        if (!errors || typeof errors !== 'object') return false;

        var matched = 0;
        Object.keys(errors).forEach(function (field) {
            var msgs = errors[field];
            var msg = Array.isArray(msgs) ? msgs[0] : String(msgs || '');
            if (!msg) return;

            // Convert Laravel's dotted/bracket notation to a name attribute.
            //   widget.button_label  ->  widget[button_label]
            //   sms.twilio.from      ->  sms[twilio][from]
            var parts = field.split('.');
            var name = parts.shift();
            parts.forEach(function (p) { name += '[' + p + ']'; });

            var input = form.querySelector('[name="' + name + '"]')
                     || form.querySelector('[name="' + field + '"]');
            if (!input) return;

            input.classList.add('is-invalid');
            var note = document.createElement('div');
            note.className = 'bis-field-error';
            note.textContent = msg;
            (input.parentNode || form).appendChild(note);
            matched++;
        });

        return matched > 0;
    }

    function clearFormErrors(form) {
        if (!form) return;
        form.querySelectorAll('.bis-field-error').forEach(function (n) { n.remove(); });
        form.querySelectorAll('.is-invalid').forEach(function (n) { n.classList.remove('is-invalid'); });
    }

    window.apiGet = apiGet;
    window.apiPost = apiPost;
    window.apiPostForm = apiPostForm;
    window.apiDelete = apiDelete;
    window.showFormErrors = showFormErrors;
    window.clearFormErrors = clearFormErrors;
})(window);
