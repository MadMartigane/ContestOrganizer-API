/**
 * JWT Token Helper Module
 * Handles JWT tokens for PHP API using firebase/php-jwt library
 * No external dependencies - Vanilla JavaScript only
 */

/**
 * Base64Url decode a string
 * Handles proper padding for Base64url format
 * @param {string} str - Base64Url encoded string
 * @returns {string} - Decoded string
 */
function base64UrlDecode(str) {
    let base64 = str.replace(/-/g, '+').replace(/_/g, '/');
    const padding = base64.length % 4;
    if (padding) {
        base64 += '='.repeat(4 - padding);
    }
    return atob(base64);
}

/**
 * Decode JWT token payload without verification
 * @param {string} token - JWT token string
 * @returns {object|null} - Decoded payload object or null if invalid
 */
function decodeToken(token) {
    if (!token || typeof token !== 'string') {
        return null;
    }

    const parts = token.split('.');
    if (parts.length !== 3) {
        return null;
    }

    try {
        const payload = base64UrlDecode(parts[1]);
        return JSON.parse(payload);
    } catch (e) {
        return null;
    }
}

/**
 * Check if token is expired
 * @param {string} token - JWT token string
 * @returns {boolean} - True if expired, false otherwise
 */
function isTokenExpired(token) {
    const payload = decodeToken(token);
    if (!payload || !payload.exp) {
        return true;
    }

    const currentTime = Math.floor(Date.now() / 1000);
    return payload.exp < currentTime;
}

/**
 * Get remaining time until token expiration in seconds
 * @param {string} token - JWT token string
 * @returns {number|null} - Seconds until expiration or null if invalid/no expiry
 */
function getTokenRemainingTime(token) {
    const payload = decodeToken(token);
    if (!payload || !payload.exp) {
        return null;
    }

    const currentTime = Math.floor(Date.now() / 1000);
    const remaining = payload.exp - currentTime;

    return remaining > 0 ? remaining : 0;
}

/**
 * Token storage object for localStorage operations
 */
const storage = {
    /**
     * Save tokens to localStorage
     * @param {string} accessToken - Access token
     * @param {string} refreshToken - Refresh token
     */
    setTokens(accessToken, refreshToken) {
        try {
            localStorage.setItem('jwt_access_token', accessToken);
            localStorage.setItem('jwt_refresh_token', refreshToken);
        } catch (e) {
            console.error('Failed to save tokens to localStorage:', e);
        }
    },

    /**
     * Get access token from localStorage
     * @returns {string|null} - Access token or null
     */
    getAccessToken() {
        try {
            return localStorage.getItem('jwt_access_token');
        } catch (e) {
            console.error('Failed to get access token from localStorage:', e);
            return null;
        }
    },

    /**
     * Get refresh token from localStorage
     * @returns {string|null} - Refresh token or null
     */
    getRefreshToken() {
        try {
            return localStorage.getItem('jwt_refresh_token');
        } catch (e) {
            console.error('Failed to get refresh token from localStorage:', e);
            return null;
        }
    },

    /**
     * Remove tokens from localStorage
     */
    clearTokens() {
        try {
            localStorage.removeItem('jwt_access_token');
            localStorage.removeItem('jwt_refresh_token');
        } catch (e) {
            console.error('Failed to clear tokens from localStorage:', e);
        }
    },

    /**
     * Get decoded user from access token
     * @returns {object|null} - Decoded user object or null
     */
    getUser() {
        const token = this.getAccessToken();
        if (!token) {
            return null;
        }

        const payload = decodeToken(token);
        return payload?.user || null;
    }
};

/**
 * Get authorization headers with Bearer token
 * @returns {object} - Headers object with Authorization or empty object
 */
function getAuthHeaders() {
    const token = storage.getAccessToken();
    if (!token) {
        return {};
    }

    return {
        Authorization: `Bearer ${token}`
    };
}

/**
 * Format token for display with decoded parts
 * @param {string} token - JWT token string
 * @returns {object} - Object with header, payload, signature, and raw
 */
function formatTokenForDisplay(token) {
    if (!token || typeof token !== 'string') {
        return {
            header: null,
            payload: null,
            signature: null,
            raw: null
        };
    }

    const parts = token.split('.');
    if (parts.length !== 3) {
        return {
            header: null,
            payload: null,
            signature: null,
            raw: token
        };
    }

    try {
        const header = JSON.parse(base64UrlDecode(parts[0]));
        const payload = JSON.parse(base64UrlDecode(parts[1]));

        return {
            header,
            payload,
            signature: parts[2],
            raw: token
        };
    } catch (e) {
        return {
            header: null,
            payload: null,
            signature: parts[2],
            raw: token
        };
    }
}

export {
    decodeToken,
    isTokenExpired,
    getTokenRemainingTime,
    storage,
    getAuthHeaders,
    formatTokenForDisplay
};
