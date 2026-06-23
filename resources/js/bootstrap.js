/**
 * App bootstrap: CSRF helpers + session-expired handling for fetch/AJAX.
 */

window.getCsrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

window.refreshCsrfToken = (token) => {
    if (!token) {
        return;
    }

    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
        meta.setAttribute('content', token);
    }

    document.querySelectorAll('input[name="_token"]').forEach((input) => {
        input.value = token;
    });
};

window.redirectToLoginAfterSessionExpired = () => {
    const loginUrl =
        document.querySelector('meta[name="login-url"]')?.getAttribute('content') ||
        '/login';

    const separator = loginUrl.includes('?') ? '&' : '?';
    window.location.href = `${loginUrl}${separator}session_expired=1`;
};

const originalFetch = window.fetch.bind(window);

window.fetch = async (input, init = {}) => {
    const options = { ...init };
    const token = window.getCsrfToken();

    if (token) {
        if (options.headers instanceof Headers) {
            if (!options.headers.has('X-CSRF-TOKEN')) {
                options.headers.set('X-CSRF-TOKEN', token);
            }
        } else {
            options.headers = {
                ...(options.headers || {}),
                'X-CSRF-TOKEN': options.headers?.['X-CSRF-TOKEN'] || token,
            };
        }
    }

    const response = await originalFetch(input, options);

    if (response.status === 419) {
        window.redirectToLoginAfterSessionExpired();
        throw new Error('Session expired');
    }

    return response;
};
