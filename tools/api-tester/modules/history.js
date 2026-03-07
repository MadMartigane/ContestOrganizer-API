/**
 * Request History Module
 * @module history
 */

/** @constant {number} Maximum number of history items to store */
export const MAX_HISTORY = 50;

/** @constant {string} localStorage key for request history */
const HISTORY_KEY = 'api_request_history';

/**
 * Get history from localStorage
 * @returns {Array} History array
 */
function loadHistory() {
    try {
        const stored = localStorage.getItem(HISTORY_KEY);
        if (!stored) {
            return [];
        }
        return JSON.parse(stored);
    } catch {
        return [];
    }
}

/**
 * Save history to localStorage
 * @param {Array} history - History array to save
 */
function saveHistory(history) {
    try {
        localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
    } catch (e) {
        console.error('Failed to save history:', e);
    }
}

/**
 * Truncate string for preview
 * @param {string} str - String to truncate
 * @param {number} maxLength - Maximum length
 * @returns {string}
 */
function truncatePreview(str, maxLength = 50) {
    if (!str || typeof str !== 'string') {
        return '';
    }
    if (str.length <= maxLength) {
        return str;
    }
    return str.substring(0, maxLength) + '...';
}

/**
 * Add request/response to history
 * @param {Object} request - Request object { method, url, body, headers }
 * @param {Object} response - Response object { status, data }
 */
export function addToHistory(request, response) {
    if (!request || !request.url) {
        return;
    }

    const history = loadHistory();

    // Create history item
    const item = {
        timestamp: Date.now(),
        method: request.method || 'GET',
        url: request.url,
        status: response?.status || 0,
        ok: response?.ok || false,
        request: {
            body: request.body,
            headers: request.headers
        },
        responsePreview: truncatePreview(
            typeof response?.data === 'string'
                ? response.data
                : JSON.stringify(response?.data),
            100
        )
    };

    // Add to beginning of array
    history.unshift(item);

    // Keep only MAX_HISTORY most recent
    if (history.length > MAX_HISTORY) {
        history.length = MAX_HISTORY;
    }

    saveHistory(history);
}

/**
 * Retrieve all history
 * @returns {Array} History array
 */
export function getHistory() {
    return loadHistory();
}

/**
 * Clear all history
 */
export function clearHistory() {
    saveHistory([]);
}

/**
 * Delete specific history item by timestamp
 * @param {number} timestamp - Timestamp of item to delete
 */
export function deleteHistoryItem(timestamp) {
    if (!timestamp || typeof timestamp !== 'number') {
        return;
    }

    const history = loadHistory();
    const filtered = history.filter(item => item.timestamp !== timestamp);
    saveHistory(filtered);
}

/**
 * Get request object for replay by timestamp
 * @param {number} timestamp - Timestamp of item to replay
 * @returns {Object|null} Request object or null if not found
 */
export function replayRequest(timestamp) {
    if (!timestamp || typeof timestamp !== 'number') {
        return null;
    }

    const history = loadHistory();
    const item = history.find(i => i.timestamp === timestamp);

    if (!item || !item.request) {
        return null;
    }

    return {
        method: item.method,
        url: item.url,
        body: item.request.body,
        headers: item.request.headers
    };
}

/**
 * Format history item for display
 * @param {Object} item - History item
 * @returns {string} HTML string for display
 */
export function formatHistoryItem(item) {
    if (!item) {
        return '';
    }

    const methodBadge = `<span class="method-badge method-${item.method.toLowerCase()}">${item.method}</span>`;
    const statusClass = item.ok ? 'status-ok' : 'status-error';
    const statusBadge = `<span class="status-badge ${statusClass}">${item.status}</span>`;

    // Extract endpoint from URL
    let endpoint = item.url;
    try {
        const url = new URL(item.url);
        endpoint = url.pathname + (url.search || '');
    } catch {
        // Use as-is if not a valid URL
    }

    const timeAgo = getTimeAgo(item.timestamp);

    return {
        html: `
            <div class="history-item" data-timestamp="${item.timestamp}">
                <div class="history-item-header">
                    ${methodBadge}
                    <span class="history-url">${escapeHtml(endpoint)}</span>
                    ${statusBadge}
                </div>
                <div class="history-item-meta">
                    <span class="history-time">${timeAgo}</span>
                </div>
            </div>
        `,
        method: item.method,
        url: item.url,
        status: item.status,
        ok: item.ok,
        timestamp: item.timestamp,
        timeAgo: timeAgo
    };
}

/**
 * Get human-readable time difference
 * @param {number} timestamp - Timestamp
 * @returns {string}
 */
function getTimeAgo(timestamp) {
    if (!timestamp) {
        return 'Unknown';
    }

    const now = Date.now();
    const diff = now - timestamp;

    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (seconds < 60) {
        return 'Just now';
    }
    if (minutes < 60) {
        return `${minutes}m ago`;
    }
    if (hours < 24) {
        return `${hours}h ago`;
    }
    return `${days}d ago`;
}

/**
 * Escape HTML special characters
 * @param {string} str - String to escape
 * @returns {string}
 */
function escapeHtml(str) {
    if (!str || typeof str !== 'string') {
        return '';
    }
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Setup history UI panel
 * Creates and renders the history panel if container exists
 */
export function setupHistoryUI() {
    const container = document.getElementById('history-container');
    if (!container) {
        console.warn('History container not found');
        return;
    }

    const history = getHistory();

    // Create header with clear button
    const header = document.createElement('div');
    header.className = 'history-header';
    header.innerHTML = `
        <h3>Request History</h3>
        ${history.length > 0 ? '<button class="clear-history-btn">Clear All</button>' : ''}
    `;

    // Create list container
    const list = document.createElement('div');
    list.className = 'history-list';

    if (history.length === 0) {
        list.innerHTML = '<p class="history-empty">No requests in history</p>';
    } else {
        history.forEach(item => {
            const itemEl = document.createElement('div');
            itemEl.className = 'history-item';
            itemEl.dataset.timestamp = item.timestamp;

            const formatted = formatHistoryItem(item);
            itemEl.innerHTML = formatted.html;

            // Add click handler for replay
            itemEl.addEventListener('click', () => {
                const event = new CustomEvent('historyReplay', {
                    detail: { timestamp: item.timestamp }
                });
                document.dispatchEvent(event);
            });

            // Add delete button
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'history-delete-btn';
            deleteBtn.innerHTML = '&times;';
            deleteBtn.title = 'Delete';
            deleteBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                deleteHistoryItem(item.timestamp);
                setupHistoryUI();
            });

            itemEl.appendChild(deleteBtn);
            list.appendChild(itemEl);
        });
    }

    // Clear button handler
    const clearBtn = header.querySelector('.clear-history-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            clearHistory();
            setupHistoryUI();
        });
    }

    // Clear container and append new content
    container.innerHTML = '';
    container.appendChild(header);
    container.appendChild(list);
}

/**
 * Export default object for convenience
 */
export default {
    MAX_HISTORY,
    addToHistory,
    getHistory,
    clearHistory,
    deleteHistoryItem,
    replayRequest,
    formatHistoryItem,
    setupHistoryUI
};
