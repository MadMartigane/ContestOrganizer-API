/**
 * API HTTP Client Module
 * @module api
 */

import { getAuthHeaders, storage } from './jwt.js';
import { showError } from './ui.js';

/** @constant {string} Default API base URL */
const DEFAULT_BASE_URL = 'http://localhost/contest/api/index.php';

/** @constant {string} localStorage key for API base URL */
const BASE_URL_KEY = 'api_base_url';

/**
 * Get API base URL from localStorage or default
 * @returns {string} - API base URL
 */
export function getBaseURL() {
    try {
        const stored = localStorage.getItem(BASE_URL_KEY);
        return stored || DEFAULT_BASE_URL;
    } catch {
        return DEFAULT_BASE_URL;
    }
}

/**
 * Save API base URL to localStorage
 * @param {string} url - API base URL to save
 */
export function setBaseURL(url) {
    if (!url || typeof url !== 'string') {
        return;
    }
    try {
        localStorage.setItem(BASE_URL_KEY, url);
    } catch (e) {
        console.error('Failed to save API base URL:', e);
    }
}

/**
 * Main request function
 * @param {string} endpoint - API endpoint (e.g., '/auth/login' or full URL)
 * @param {Object} [options={}] - Request options
 * @param {string} [options.method='GET'] - HTTP method
 * @param {*} [options.body] - Request body
 * @param {Object} [options.headers={}] - Additional headers
 * @param {boolean} [options.skipAuth=false] - Skip authorization header
 * @returns {Promise<{ok: boolean, status: number, data: any, headers: Headers, duration: number}>}
 */
export async function request(endpoint, options = {}) {
    const {
        method = 'GET',
        body = null,
        headers = {},
        skipAuth = false
    } = options;

    const startTime = performance.now();

    // Build URL
    let url;
    if (endpoint.startsWith('http://') || endpoint.startsWith('https://')) {
        url = endpoint;
    } else if (endpoint.startsWith('/')) {
        url = getBaseURL() + endpoint;
    } else {
        url = getBaseURL() + '/' + endpoint;
    }

    // Build headers
    const requestHeaders = { ...headers };

    // Add Content-Type for body methods
    if (body && ['POST', 'PUT', 'PATCH'].includes(method.toUpperCase())) {
        requestHeaders['Content-Type'] = 'application/json';
    }

    // Add authorization header if not skipped and token exists
    if (!skipAuth) {
        const authHeaders = getAuthHeaders();
        Object.assign(requestHeaders, authHeaders);
    }

    // Prepare fetch options
    const fetchOptions = {
        method: method.toUpperCase(),
        headers: requestHeaders,
        mode: 'cors'
    };

    // Add body if present
    if (body) {
        fetchOptions.body = typeof body === 'object' 
            ? JSON.stringify(body) 
            : body;
    }

    try {
        const response = await fetch(url, fetchOptions);
        const duration = performance.now() - startTime;

        // Parse response
        let data;
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            try {
                data = await response.json();
            } catch {
                data = null;
            }
        } else {
            try {
                data = await response.text();
            } catch {
                data = null;
            }
        }

        return {
            ok: response.ok,
            status: response.status,
            data: data,
            headers: response.headers,
            duration: duration
        };
    } catch (error) {
        const duration = performance.now() - startTime;
        
        // Show error to user
        showError(`Network error: ${error.message}`);
        
        return {
            ok: false,
            status: 0,
            data: { error: error.message },
            headers: new Headers(),
            duration: duration
        };
    }
}

/**
 * GET request
 * @param {string} endpoint - API endpoint
 * @param {Object} [options={}] - Request options
 * @returns {Promise<{ok: boolean, status: number, data: any, headers: Headers, duration: number}>}
 */
export async function get(endpoint, options = {}) {
    return request(endpoint, { ...options, method: 'GET' });
}

/**
 * POST request
 * @param {string} endpoint - API endpoint
 * @param {*} [body=null] - Request body
 * @param {Object} [options={}] - Request options
 * @returns {Promise<{ok: boolean, status: number, data: any, headers: Headers, duration: number}>}
 */
export async function post(endpoint, body = null, options = {}) {
    return request(endpoint, { ...options, method: 'POST', body });
}

/**
 * PUT request
 * @param {string} endpoint - API endpoint
 * @param {*} [body=null] - Request body
 * @param {Object} [options={}] - Request options
 * @returns {Promise<{ok: boolean, status: number, data: any, headers: Headers, duration: number}>}
 */
export async function put(endpoint, body = null, options = {}) {
    return request(endpoint, { ...options, method: 'PUT', body });
}

/**
 * DELETE request
 * @param {string} endpoint - API endpoint
 * @param {Object} [options={}] - Request options
 * @returns {Promise<{ok: boolean, status: number, data: any, headers: Headers, duration: number}>}
 */
export async function del(endpoint, options = {}) {
    return request(endpoint, { ...options, method: 'DELETE' });
}

/**
 * Login user
 * @param {string} email - User email
 * @param {string} password - User password
 * @returns {Promise<{success: boolean, data?: object, error?: string}>}
 */
export async function login(email, password) {
    const response = await post('/auth/login', { email, password });

    if (response.ok && response.status === 200) {
        // Check if response contains tokens
        const data = response.data;
        if (data && data.access_token) {
            storage.setTokens(data.access_token, data.refresh_token || null);
            return { success: true, data: data };
        }
        return { success: false, error: 'Invalid response: missing tokens' };
    }

    const errorMessage = response.data?.error 
        || response.data?.message 
        || 'Login failed';
    return { success: false, error: errorMessage };
}

/**
 * Logout user
 * @returns {Promise<{success: boolean}>}
 */
export async function logout() {
    const token = storage.getAccessToken();

    // Attempt to call logout endpoint if token exists
    if (token) {
        try {
            await post('/auth/logout', { token }, { skipAuth: true });
        } catch {
            // Ignore network errors during logout
        }
    }

    // Always clear tokens regardless of endpoint success/failure
    storage.clearTokens();
    return { success: true };
}

// Default export for convenience
export default {
    getBaseURL,
    setBaseURL,
    request,
    get,
    post,
    put,
    del,
    login,
    logout
};
