import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Set CSRF token from meta tag
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-token');
}

// CSRF token refresh with auto-retry
window.axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (error.response?.status === 419) {
            try {
                const response = await fetch(window.location.href, { method: 'GET' });
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newToken = doc.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (newToken) {
                    document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', newToken);
                    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;
                    
                    // Update the original request with new token
                    if (error.config && error.config.headers) {
                        error.config.headers['X-CSRF-TOKEN'] = newToken;
                    }
                    
                    // Retry the original request
                    return window.axios.request(error.config);
                }
            } catch (e) {}
        }
        return Promise.reject(error);
    }
);
