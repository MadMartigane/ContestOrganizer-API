/**
 * UI Helper Utilities for API Tester
 * @module ui
 */

/**
 * DOM Helpers
 * @group DOM
 */

/**
 * Query selector wrapper - returns first matching element
 * @param {string} selector - CSS selector
 * @returns {Element|null}
 */
export function $(selector) {
    if (!selector || typeof selector !== 'string') {
        return null;
    }
    return document.querySelector(selector);
}

/**
 * Query selector all wrapper - returns all matching elements
 * @param {string} selector - CSS selector
 * @returns {NodeList}
 */
export function $$(selector) {
    if (!selector || typeof selector !== 'string') {
        return document.querySelectorAll('');
    }
    return document.querySelectorAll(selector);
}

/**
 * Create element with classes and attributes
 * @param {string} tag - HTML tag name
 * @param {string[]|string} [classes] - CSS class names
 * @param {Object} [attributes] - Key-value pairs of attributes
 * @returns {HTMLElement}
 */
export function createElement(tag, classes = [], attributes = {}) {
    if (!tag || typeof tag !== 'string') {
        throw new Error('Tag name is required');
    }

    const element = document.createElement(tag);

    // Handle classes
    if (classes) {
        const classArray = Array.isArray(classes) ? classes : classes.split(' ');
        element.classList.add(...classArray.filter(c => c));
    }

    // Handle attributes
    if (attributes && typeof attributes === 'object') {
        Object.entries(attributes).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                element.setAttribute(key, value);
            }
        });
    }

    return element;
}

/**
 * Show element by removing hidden class and setting display
 * @param {HTMLElement} element
 * @param {string} [display='block']
 */
export function show(element, display = 'block') {
    if (!element) return;
    element.classList.remove('hidden');
    element.style.display = display;
}

/**
 * Hide element by adding hidden class
 * @param {HTMLElement} element
 */
export function hide(element) {
    if (!element) return;
    element.classList.add('hidden');
    element.style.display = 'none';
}

/**
 * Toggle element visibility
 * @param {HTMLElement} element
 * @param {boolean} [force] - Force state: true = show, false = hide
 * @returns {boolean} - New visibility state
 */
export function toggle(element, force) {
    if (!element) return false;

    const isHidden = element.classList.contains('hidden') || element.style.display === 'none';
    const shouldShow = force !== undefined ? force : isHidden;

    if (shouldShow) {
        show(element);
    } else {
        hide(element);
    }

    return shouldShow;
}


/**
 * JSON Formatting
 * @group JSON
 */

/**
 * Pretty print JSON with 2 spaces
 * @param {*} obj - Object to stringify
 * @returns {string}
 */
export function formatJSON(obj) {
    if (obj === null || obj === undefined) {
        return '';
    }

    try {
        if (typeof obj === 'string') {
            const parsed = JSON.parse(obj);
            return JSON.stringify(parsed, null, 2);
        }
        return JSON.stringify(obj, null, 2);
    } catch {
        return String(obj);
    }
}

/**
 * Add HTML classes for syntax highlighting
 * @param {string} json - JSON string
 * @returns {string} HTML formatted string
 */
export function syntaxHighlight(json) {
    if (!json || typeof json !== 'string') {
        return '';
    }

    // Escape HTML first
    let escaped = json
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    // Apply syntax highlighting
    return escaped
        // Keys (property names in double quotes)
        .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?)/g, (match) => {
            const isKey = /\s:$/.test(match);
            const cls = isKey ? 'json-key' : 'json-string';
            return `<span class="${cls}">${match}</span>`;
        })
        // Numbers
        .replace(/\b(-?\d+\.?\d*([eE][+-]?\d+)?)\b/g, '<span class="json-number">$1</span>')
        // Booleans and null
        .replace(/\b(true|false|null)\b/g, '<span class="json-bool">$1</span>');
}

/**
 * Copy text to clipboard
 * @param {string} text - Text to copy
 * @returns {Promise<boolean>}
 */
export async function copyToClipboard(text) {
    if (!text || typeof text !== 'string') {
        return false;
    }

    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
            return true;
        }

        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();

        const success = document.execCommand('copy');
        document.body.removeChild(textarea);
        return success;
    } catch {
        return false;
    }
}


/**
 * Notifications (Toast)
 * @group Notifications
 */

let toastContainer = null;

/**
 * Get or create toast container
 * @returns {HTMLElement}
 */
function getToastContainer() {
    if (!toastContainer) {
        toastContainer = $('.toast-container');
        if (!toastContainer) {
            toastContainer = createElement('div', 'toast-container', {
                id: 'toast-container'
            });
            document.body.appendChild(toastContainer);
        }
    }
    return toastContainer;
}

/**
 * Show toast notification
 * @param {string} message - Toast message
 * @param {string} [type='info'] - Toast type: 'info', 'success', 'error', 'warning'
 * @returns {HTMLElement}
 */
export function toast(message, type = 'info') {
    if (!message) return null;

    const container = getToastContainer();
    const toastEl = createElement('div', ['toast', `toast-${type}`]);

    const messageEl = createElement('span');
    messageEl.textContent = message;
    toastEl.appendChild(messageEl);

    const closeBtn = createElement('button', 'toast-close', {
        type: 'button',
        'aria-label': 'Close'
    });
    closeBtn.innerHTML = '&times;';
    closeBtn.onclick = () => toastEl.remove();
    toastEl.appendChild(closeBtn);

    container.appendChild(toastEl);

    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        if (toastEl.parentNode) {
            toastEl.remove();
        }
    }, 3000);

    return toastEl;
}

/**
 * Show error toast notification
 * @param {string} message - Error message
 * @returns {HTMLElement}
 */
export function showError(message) {
    return toast(message, 'error');
}

/**
 * Show success toast notification
 * @param {string} message - Success message
 * @returns {HTMLElement}
 */
export function showSuccess(message) {
    return toast(message, 'success');
}


/**
 * Form Helpers
 * @group Forms
 */

/**
 * Serialize form data to object
 * @param {HTMLFormElement} formElement - Form element
 * @returns {Object}
 */
export function serializeForm(formElement) {
    if (!formElement || !(formElement instanceof HTMLFormElement)) {
        return {};
    }

    const formData = new FormData(formElement);
    const result = {};

    for (const [key, value] of formData.entries()) {
        // Handle multiple values for same key
        if (result[key] !== undefined) {
            if (!Array.isArray(result[key])) {
                result[key] = [result[key]];
            }
            result[key].push(value);
        } else {
            result[key] = value;
        }
    }

    return result;
}

/**
 * Validate JSON string
 * @param {string} str - String to validate
 * @returns {{valid: boolean, error?: string}}
 */
export function validateJSON(str) {
    if (!str || typeof str !== 'string') {
        return { valid: false, error: 'Input must be a non-empty string' };
    }

    try {
        JSON.parse(str);
        return { valid: true };
    } catch (e) {
        return { valid: false, error: e.message };
    }
}

/**
 * Format bytes to human readable size
 * @param {number} bytes - Size in bytes
 * @returns {string}
 */
export function formatBytes(bytes) {
    if (bytes === null || bytes === undefined || bytes === 0) {
        return '0 Bytes';
    }

    if (typeof bytes !== 'number' || isNaN(bytes)) {
        return '0 Bytes';
    }

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}


/**
 * HTTP Status Helpers
 * @group HTTP
 */

/**
 * Get status color class based on HTTP status code
 * @param {number} code - HTTP status code
 * @returns {string}
 */
export function getStatusColor(code) {
    if (typeof code !== 'number' || isNaN(code)) {
        return 'info';
    }

    if (code >= 200 && code < 300) {
        return 'success';
    }
    if (code >= 300 && code < 400) {
        return 'info';
    }
    if (code >= 400 && code < 500) {
        return 'warning';
    }
    if (code >= 500) {
        return 'error';
    }

    return 'info';
}

/**
 * Get HTTP status text
 * @param {number} code - HTTP status code
 * @returns {string}
 */
export function getStatusText(code) {
    if (typeof code !== 'number' || isNaN(code)) {
        return 'Unknown';
    }

    const statusTexts = {
        // 1xx Informational
        100: 'Continue',
        101: 'Switching Protocols',
        102: 'Processing',
        103: 'Early Hints',

        // 2xx Success
        200: 'OK',
        201: 'Created',
        202: 'Accepted',
        203: 'Non-Authoritative Information',
        204: 'No Content',
        205: 'Reset Content',
        206: 'Partial Content',
        207: 'Multi-Status',
        208: 'Already Reported',
        226: 'IM Used',

        // 3xx Redirection
        300: 'Multiple Choices',
        301: 'Moved Permanently',
        302: 'Found',
        303: 'See Other',
        304: 'Not Modified',
        305: 'Use Proxy',
        307: 'Temporary Redirect',
        308: 'Permanent Redirect',

        // 4xx Client Errors
        400: 'Bad Request',
        401: 'Unauthorized',
        402: 'Payment Required',
        403: 'Forbidden',
        404: 'Not Found',
        405: 'Method Not Allowed',
        406: 'Not Acceptable',
        407: 'Proxy Authentication Required',
        408: 'Request Timeout',
        409: 'Conflict',
        410: 'Gone',
        411: 'Length Required',
        412: 'Precondition Failed',
        413: 'Payload Too Large',
        414: 'URI Too Long',
        415: 'Unsupported Media Type',
        416: 'Range Not Satisfiable',
        417: 'Expectation Failed',
        418: "I'm a teapot",
        421: 'Misdirected Request',
        422: 'Unprocessable Entity',
        423: 'Locked',
        424: 'Failed Dependency',
        425: 'Too Early',
        426: 'Upgrade Required',
        428: 'Precondition Required',
        429: 'Too Many Requests',
        431: 'Request Header Fields Too Large',
        451: 'Unavailable For Legal Reasons',

        // 5xx Server Errors
        500: 'Internal Server Error',
        501: 'Not Implemented',
        502: 'Bad Gateway',
        503: 'Service Unavailable',
        504: 'Gateway Timeout',
        505: 'HTTP Version Not Supported',
        506: 'Variant Also Negotiates',
        507: 'Insufficient Storage',
        508: 'Loop Detected',
        510: 'Not Extended',
        511: 'Network Authentication Required'
    };

    return statusTexts[code] || 'Unknown';
}


/**
 * Time Helpers
 * @group Time
 */

/**
 * Format milliseconds to human readable duration
 * @param {number} ms - Duration in milliseconds
 * @returns {string}
 */
export function formatDuration(ms) {
    if (ms === null || ms === undefined || typeof ms !== 'number' || isNaN(ms)) {
        return '0ms';
    }

    if (ms < 0) {
        return '0ms';
    }

    if (ms < 1000) {
        return `${ms}ms`;
    }

    const seconds = ms / 1000;
    if (seconds < 60) {
        return `${seconds.toFixed(2)}s`;
    }

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    if (minutes < 60) {
        return `${minutes}m ${remainingSeconds.toFixed(0)}s`;
    }

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    return `${hours}h ${remainingMinutes}m`;
}

/**
 * Get human readable time difference from now
 * @param {number|string|Date} timestamp - Timestamp to compare
 * @returns {string}
 */
export function timeAgo(timestamp) {
    if (!timestamp) {
        return 'Unknown';
    }

    let date;
    if (timestamp instanceof Date) {
        date = timestamp;
    } else if (typeof timestamp === 'number') {
        date = new Date(timestamp);
    } else if (typeof timestamp === 'string') {
        date = new Date(timestamp);
    } else {
        return 'Unknown';
    }

    if (isNaN(date.getTime())) {
        return 'Unknown';
    }

    const now = Date.now();
    const diff = now - date.getTime();

    if (diff < 0) {
        return 'In the future';
    }

    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    const months = Math.floor(days / 30);
    const years = Math.floor(days / 365);

    if (seconds < 60) {
        return 'Just now';
    }
    if (minutes < 60) {
        return minutes === 1 ? '1 minute ago' : `${minutes} minutes ago`;
    }
    if (hours < 24) {
        return hours === 1 ? '1 hour ago' : `${hours} hours ago`;
    }
    if (days < 30) {
        return days === 1 ? '1 day ago' : `${days} days ago`;
    }
    if (months < 12) {
        return months === 1 ? '1 month ago' : `${months} months ago`;
    }

    return years === 1 ? '1 year ago' : `${years} years ago`;
}
