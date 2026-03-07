// ============================================
// AUTH CLIENT (no exports, global functions)
// ============================================

const DEFAULT_API_URL = 'http://localhost/contest/api/index.php';
const ACCESS_TOKEN_KEY = 'api_access_token';
const REFRESH_TOKEN_KEY = 'api_refresh_token';
const API_URL_KEY = 'api_base_url';

function base64UrlDecode(str) {
    let base64 = str.replace(/-/g, '+').replace(/_/g, '/');
    const pad = base64.length % 4;
    if (pad) base64 += '='.repeat(4 - pad);
    return atob(base64);
}

function decodeJWT(token) {
    const parts = token.split('.');
    const payload = parts[1];
    if (!payload) throw new Error('Invalid JWT');
    return JSON.parse(base64UrlDecode(payload));
}

function isTokenExpired(token) {
    try {
        const decoded = decodeJWT(token);
        return decoded.exp ? decoded.exp < Math.floor(Date.now() / 1000) : false;
    } catch {
        return true;
    }
}

const storage = {
    setTokens: function(accessToken, refreshToken) {
        localStorage.setItem(ACCESS_TOKEN_KEY, accessToken);
        localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);
    },
    getAccessToken: function() {
        return localStorage.getItem(ACCESS_TOKEN_KEY);
    },
    getRefreshToken: function() {
        return localStorage.getItem(REFRESH_TOKEN_KEY);
    },
    clearTokens: function() {
        localStorage.removeItem(ACCESS_TOKEN_KEY);
        localStorage.removeItem(REFRESH_TOKEN_KEY);
    },
    setApiUrl: function(url) {
        localStorage.setItem(API_URL_KEY, url);
    },
    getApiUrl: function() {
        return localStorage.getItem(API_URL_KEY) || DEFAULT_API_URL;
    }
};

function getApiUrl() {
    return storage.getApiUrl();
}

function setApiUrl(url) {
    storage.setApiUrl(url);
}

function getAuthHeaders() {
    const token = storage.getAccessToken();
    if (!token) return {};
    return { 'Authorization': 'Bearer ' + token };
}

async function login(email, password) {
    const apiUrl = getApiUrl();
    const url = apiUrl + '/auth/login';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, password: password })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            return { success: false, error: (data.error && data.error.message) ? data.error.message : 'Login failed' };
        }
        
        storage.setTokens(data.access_token, data.refresh_token);
        
        return {
            success: true,
            user: data.user,
            expiresIn: data.expires_in
        };
    } catch (error) {
        return {
            success: false,
            error: error.message || 'Network error'
        };
    }
}

async function logout() {
    const apiUrl = getApiUrl();
    const url = apiUrl + '/auth/logout';
    
    try {
        await fetch(url, {
            method: 'POST',
            headers: Object.assign({ 'Content-Type': 'application/json' }, getAuthHeaders())
        });
    } catch (e) {
        // ignore error
    }
    
    storage.clearTokens();
    return { success: true };
}

function getCurrentUser() {
    const token = storage.getAccessToken();
    if (!token) return null;
    
    try {
        const decoded = decodeJWT(token);
        return {
            userId: decoded.user_id,
            email: decoded.email,
            role: decoded.role,
            expiresAt: decoded.exp
        };
    } catch (e) {
        return null;
    }
}

function isAuthenticated() {
    const token = storage.getAccessToken();
    if (!token) return false;
    return !isTokenExpired(token);
}

// ============================================
// UI HELPERS
// ============================================

function show(element) {
    if (element) element.classList.remove('hidden');
}

function hide(element) {
    if (element) element.classList.add('hidden');
}

function showToast(message, type) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + (type || 'info');
    toast.innerHTML = '<span class="toast-message">' + message + '</span><button class="toast-close">&times;</button>';
    
    container.appendChild(toast);
    
    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', function() {
        toast.remove();
    });
    
    setTimeout(function() {
        toast.remove();
    }, 4000);
}

// ============================================
// APP LOGIC
// ============================================

function updateAuthUI() {
    var logoutBtn = document.getElementById('logout-btn');
    var tokenDisplay = document.getElementById('token-display');
    var loginForm = document.getElementById('login-form');
    var connectionStatus = document.getElementById('connection-status');
    var connectionStatusText = document.getElementById('connection-status-text');
    
    if (isAuthenticated()) {
        if (logoutBtn) logoutBtn.classList.remove('hidden');
        if (tokenDisplay) tokenDisplay.classList.remove('hidden');
        if (loginForm) loginForm.classList.add('hidden');
        
        if (connectionStatus) {
            connectionStatus.classList.remove('disconnected');
            connectionStatus.classList.add('connected');
        }
        if (connectionStatusText) {
            connectionStatusText.textContent = 'Connected';
        }
        
        // Display tokens
        var accessToken = storage.getAccessToken();
        var refreshToken = storage.getRefreshToken();
        
        var accessTokenEl = document.getElementById('access-token');
        var refreshTokenEl = document.getElementById('refresh-token');
        
        if (accessTokenEl) accessTokenEl.textContent = accessToken || 'N/A';
        if (refreshTokenEl) refreshTokenEl.textContent = refreshToken || 'N/A';
        
        updateJWTDisplay();
    } else {
        if (logoutBtn) logoutBtn.classList.add('hidden');
        if (tokenDisplay) tokenDisplay.classList.add('hidden');
        if (loginForm) loginForm.classList.remove('hidden');
        
        if (connectionStatus) {
            connectionStatus.classList.remove('connected');
            connectionStatus.classList.add('disconnected');
        }
        if (connectionStatusText) {
            connectionStatusText.textContent = 'Disconnected';
        }
    }
}

function updateJWTDisplay() {
    var jwtDecoded = document.getElementById('jwt-decoded');
    var jwtPlaceholder = document.getElementById('jwt-placeholder');
    
    if (!isAuthenticated()) {
        if (jwtDecoded) jwtDecoded.classList.add('hidden');
        if (jwtPlaceholder) jwtPlaceholder.classList.remove('hidden');
        return;
    }
    
    var token = storage.getAccessToken();
    if (!token) return;
    
    try {
        var decoded = decodeJWT(token);
        
        var jwtSub = document.getElementById('jwt-sub');
        var jwtRole = document.getElementById('jwt-role');
        var jwtIat = document.getElementById('jwt-iat');
        var jwtExp = document.getElementById('jwt-exp');
        
        if (jwtSub) jwtSub.textContent = decoded.user_id || decoded.sub || '-';
        if (jwtRole) jwtRole.textContent = decoded.role || '-';
        if (jwtIat) jwtIat.textContent = decoded.iat ? new Date(decoded.iat * 1000).toLocaleString() : '-';
        if (jwtExp) jwtExp.textContent = decoded.exp ? new Date(decoded.exp * 1000).toLocaleString() : '-';
        
        if (jwtDecoded) jwtDecoded.classList.remove('hidden');
        if (jwtPlaceholder) jwtPlaceholder.classList.add('hidden');
        
    } catch (e) {
        console.error('Failed to decode JWT:', e);
    }
}

function clearUserInfo() {
    // Reset form
    var emailInput = document.getElementById('login-email');
    var passwordInput = document.getElementById('login-password');
    if (emailInput) emailInput.value = '';
    if (passwordInput) passwordInput.value = '';
}

// ============================================
// USERS SECTION
// ============================================

function setupUsersSection() {
    var listUsersBtn = document.getElementById('list-users-btn');
    var createUserForm = document.getElementById('create-user-form');
    
    // List users button
    if (listUsersBtn) {
        listUsersBtn.addEventListener('click', function() {
            var apiUrl = getApiUrl();
            fetch(apiUrl + '/auth/list', {
                headers: { 'Content-Type': 'application/json' }
            }).then(function(res) {
                return res.json();
            }).then(function(data) {
                if (data.users) {
                    displayUsers(data.users);
                } else {
                    showToast('Failed to list users', 'error');
                }
            }).catch(function(e) {
                showToast('Error: ' + e.message, 'error');
            });
        });
    }
    
    // Create user form
    if (createUserForm) {
        createUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var email = document.getElementById('new-user-email').value;
            var password = document.getElementById('new-user-password').value;
            var role = document.getElementById('new-user-role').value;
            
            var apiUrl = getApiUrl();
            fetch(apiUrl + '/auth/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, password: password, role: role })
            }).then(function(res) {
                return res.json();
            }).then(function(data) {
                if (data.id || data.user) {
                    showToast('User created successfully!', 'success');
                    createUserForm.reset();
                } else {
                    showToast(data.error ? data.error.message : 'Failed to create user', 'error');
                }
            }).catch(function(e) {
                showToast('Error: ' + e.message, 'error');
            });
        });
    }
}

function displayUsers(users) {
    var usersSection = document.getElementById('users-section');
    var listContainer = usersSection ? usersSection.querySelector('.users-list') : null;
    
    if (!listContainer) {
        listContainer = document.createElement('div');
        listContainer.className = 'users-list';
        var panelBody = usersSection ? usersSection.querySelector('.panel-body') : null;
        if (panelBody) panelBody.appendChild(listContainer);
    }
    
    if (!users || users.length === 0) {
        listContainer.innerHTML = '<div class="no-users">No users found</div>';
        return;
    }
    
    var html = '<div class="table-container"><table class="table"><thead><tr><th>ID</th><th>Email</th><th>Role</th></tr></thead><tbody>';
    for (var i = 0; i < users.length; i++) {
        var user = users[i];
        html += '<tr><td>' + (user.id || '-') + '</td><td>' + (user.email || '-') + '</td><td><span class="badge badge-' + (user.role || 'info') + '">' + (user.role || 'user') + '</span></td></tr>';
    }
    html += '</tbody></table></div>';
    listContainer.innerHTML = html;
}

// ============================================
// FILES SECTION
// ============================================

function setupFilesSection() {
    var listFilesBtn = document.getElementById('list-files-btn');
    var createFileForm = document.getElementById('create-file-form');
    var fileList = document.getElementById('file-list');
    var fileActions = document.getElementById('file-actions');
    var updateFileForm = document.getElementById('update-file-form');
    var deleteFileBtn = document.getElementById('delete-file-btn');
    
    var selectedFileId = null;
    
    // List files button
    if (listFilesBtn) {
        listFilesBtn.addEventListener('click', function() {
            var apiUrl = getApiUrl();
            fetch(apiUrl + '/files/list', {
                headers: { 'Content-Type': 'application/json' }
            }).then(function(res) {
                return res.json();
            }).then(function(data) {
                if (data.files) {
                    displayFiles(data.files);
                } else {
                    showToast('Failed to list files', 'error');
                }
            }).catch(function(e) {
                showToast('Error: ' + e.message, 'error');
            });
        });
    }
    
    // Create file form
    if (createFileForm) {
        createFileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var filename = document.getElementById('new-file-name').value;
            var content = document.getElementById('new-file-content').value;
            
            var apiUrl = getApiUrl();
            fetch(apiUrl + '/files/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ filename: filename, content: content })
            }).then(function(res) {
                return res.json();
            }).then(function(data) {
                if (data.id || data.file) {
                    showToast('File created successfully!', 'success');
                    createFileForm.reset();
                    listFilesBtn.click();
                } else {
                    showToast(data.error ? data.error.message : 'Failed to create file', 'error');
                }
            }).catch(function(e) {
                showToast('Error: ' + e.message, 'error');
            });
        });
    }
    
    // Update file form
    if (updateFileForm) {
        updateFileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!selectedFileId) return;
            
            var content = document.getElementById('update-file-content').value;
            
            var apiUrl = getApiUrl();
            fetch(apiUrl + '/files/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: selectedFileId, content: content })
            }).then(function(res) {
                return res.json();
            }).then(function(data) {
                if (res.ok) {
                    showToast('File updated successfully!', 'success');
                    updateFileForm.reset();
                    if (fileActions) fileActions.classList.add('hidden');
                    selectedFileId = null;
                    listFilesBtn.click();
                } else {
                    showToast(data.error ? data.error.message : 'Failed to update file', 'error');
                }
            }).catch(function(e) {
                showToast('Error: ' + e.message, 'error');
            });
        });
    }
    
    // Delete file button
    if (deleteFileBtn) {
        deleteFileBtn.addEventListener('click', function() {
            if (!selectedFileId) return;
            
            var apiUrl = getApiUrl();
            fetch(apiUrl + '/files/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: selectedFileId })
            }).then(function(res) {
                return res.json();
            }).then(function(data) {
                if (res.ok) {
                    showToast('File deleted successfully!', 'success');
                    if (fileActions) fileActions.classList.add('hidden');
                    selectedFileId = null;
                    listFilesBtn.click();
                } else {
                    showToast(data.error ? data.error.message : 'Failed to delete file', 'error');
                }
            }).catch(function(e) {
                showToast('Error: ' + e.message, 'error');
            });
        });
    }
    
    function displayFiles(files) {
        if (!fileList) return;
        
        if (!files || files.length === 0) {
            fileList.innerHTML = '<div class="empty-state">No files found</div>';
            return;
        }
        
        var html = '<ul class="files-list">';
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            html += '<li class="file-item" data-id="' + file.id + '">' +
                '<div class="file-info">' +
                '<i class="ph ph-file file-icon"></i>' +
                '<div>' +
                '<div class="file-name">' + (file.filename || file.name) + '</div>' +
                '<div class="file-meta">ID: ' + file.id + '</div>' +
                '</div></div></li>';
        }
        html += '</ul>';
        fileList.innerHTML = html;
        
        // Add click handlers for file items
        var items = fileList.querySelectorAll('.file-item');
        for (var j = 0; j < items.length; j++) {
            (function(item) {
                item.addEventListener('click', function() {
                    selectedFileId = item.dataset.id;
                    
                    // Update UI to show selected
                    var allItems = fileList.querySelectorAll('.file-item');
                    for (var k = 0; k < allItems.length; k++) {
                        allItems[k].classList.remove('selected');
                    }
                    item.classList.add('selected');
                    
                    // Show file actions
                    if (fileActions) fileActions.classList.remove('hidden');
                    
                    // Set the file id in the update form
                    var updateFileId = document.getElementById('update-file-id');
                    if (updateFileId) updateFileId.value = selectedFileId;
                    
                    // Clear content
                    var updateContent = document.getElementById('update-file-content');
                    if (updateContent) updateContent.value = '';
                });
            })(items[j]);
        }
    }
}

// ============================================
// RAW REQUEST SECTION
// ============================================

function setupRawRequestSection() {
    var form = document.getElementById('raw-request-form');
    var addHeaderBtn = document.getElementById('add-header-btn');
    var headersContainer = document.getElementById('custom-headers-container');
    
    // Add header button
    if (addHeaderBtn && headersContainer) {
        addHeaderBtn.addEventListener('click', function() {
            var headerRow = document.createElement('div');
            headerRow.className = 'header-row';
            headerRow.innerHTML = '<input type="text" class="header-key-input" placeholder="Header name">' +
                '<input type="text" class="header-value-input" placeholder="Header value">' +
                '<button type="button" class="remove-header-btn">&times;</button>';
            
            headerRow.querySelector('.remove-header-btn').addEventListener('click', function() {
                headerRow.remove();
            });
            
            headersContainer.appendChild(headerRow);
        });
    }
    
    // Form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var method = document.getElementById('request-method').value;
            var urlInput = document.getElementById('request-url').value;
            var bodyInput = document.getElementById('request-body').value;
            
            var baseUrl = getApiUrl();
            var fullUrl = urlInput.indexOf('http') === 0 ? urlInput : baseUrl + urlInput;
            
            // Build headers
            var headers = {
                'Content-Type': 'application/json'
            };
            
            // Add auth header if authenticated
            var authHeaders = getAuthHeaders();
            for (var key in authHeaders) {
                if (authHeaders.hasOwnProperty(key)) {
                    headers[key] = authHeaders[key];
                }
            }
            
            // Add custom headers
            if (headersContainer) {
                var headerRows = headersContainer.querySelectorAll('.header-row');
                for (var i = 0; i < headerRows.length; i++) {
                    var row = headerRows[i];
                    var keyInput = row.querySelector('.header-key-input');
                    var valueInput = row.querySelector('.header-value-input');
                    if (keyInput && valueInput && keyInput.value && valueInput.value) {
                        headers[keyInput.value] = valueInput.value;
                    }
                }
            }
            
            // Build fetch options
            var options = {
                method: method,
                headers: headers
            };
            
            // Add body for non-GET requests
            if (method !== 'GET' && bodyInput && bodyInput.trim()) {
                try {
                    options.body = JSON.stringify(JSON.parse(bodyInput));
                } catch (err) {
                    options.body = bodyInput;
                }
            }
            
            var startTime = Date.now();
            
            fetch(fullUrl, options).then(function(res) {
                var duration = Date.now() - startTime;
                
                var contentType = res.headers.get('content-type') || '';
                return res.json().then(function(data) {
                    return { res: res, data: data, duration: duration };
                }).catch(function() {
                    return res.text().then(function(text) {
                        return { res: res, data: text, duration: duration };
                    });
                });
            }).then(function(result) {
                displayResponse(result.res, result.data, result.duration);
            }).catch(function(e) {
                displayResponse(
                    { status: 0, statusText: e.message, headers: new Headers() },
                    { error: e.message },
                    Date.now() - startTime
                );
                showToast('Request failed: ' + e.message, 'error');
            });
        });
    }
}

function displayResponse(res, data, duration) {
    var statusEl = document.getElementById('response-status');
    var timeEl = document.getElementById('response-time');
    var headersEl = document.getElementById('response-headers');
    var bodyEl = document.getElementById('response-body');
    
    // Status
    if (statusEl) {
        statusEl.textContent = res.status || 'Error';
        statusEl.className = 'status-badge';
        if (res.status >= 200 && res.status < 300) statusEl.classList.add('status-2xx');
        else if (res.status >= 300 && res.status < 400) statusEl.classList.add('status-3xx');
        else if (res.status >= 400 && res.status < 500) statusEl.classList.add('status-4xx');
        else if (res.status >= 500) statusEl.classList.add('status-5xx');
    }
    
    // Duration
    if (timeEl) timeEl.textContent = duration + 'ms';
    
    // Headers
    if (headersEl) {
        var headersHtml = '';
        if (res.headers) {
            res.headers.forEach(function(value, key) {
                headersHtml += '<div class="header-item"><span class="header-key">' + key + ':</span> <span class="header-value">' + value + '</span></div>';
            });
        }
        headersEl.innerHTML = headersHtml || '<span class="placeholder">No headers</span>';
    }
    
    // Body
    if (bodyEl) {
        if (typeof data === 'object') {
            bodyEl.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        } else {
            bodyEl.innerHTML = '<pre>' + data + '</pre>';
        }
    }
}

// ============================================
// RESPONSE VIEWER
// ============================================

function setupResponseViewer() {
    var copyBtn = document.getElementById('copy-response-btn');
    var bodyEl = document.getElementById('response-body');
    
    if (copyBtn && bodyEl) {
        copyBtn.addEventListener('click', function() {
            var text = bodyEl.textContent;
            navigator.clipboard.writeText(text).then(function() {
                copyBtn.classList.add('copied');
                copyBtn.innerHTML = '<i class="ph ph-check"></i> Copied';
                setTimeout(function() {
                    copyBtn.classList.remove('copied');
                    copyBtn.innerHTML = '<i class="ph ph-copy"></i> Copy';
                }, 2000);
            }).catch(function(e) {
                showToast('Failed to copy', 'error');
            });
        });
    }
}

// ============================================
// API URL CONFIGURATION
// ============================================

function initApiUrlConfig() {
    var apiUrlInput = document.getElementById('api-base-url');
    
    if (apiUrlInput) {
        // Set initial value from storage
        apiUrlInput.value = getApiUrl();
        
        // Save on change
        apiUrlInput.addEventListener('change', function() {
            setApiUrl(apiUrlInput.value);
            showToast('API URL updated', 'info');
        });
    }
}

// ============================================
// NAVIGATION
// ============================================

function initNavigation() {
    var navItems = document.querySelectorAll('.nav-item[data-section]');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    var menuToggle = document.getElementById('menu-toggle');
    
    // Mobile menu toggle
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }
    
    for (var i = 0; i < navItems.length; i++) {
        (function(item) {
            item.addEventListener('click', function() {
                var section = item.dataset.section;
                
                // Update active state
                var allItems = document.querySelectorAll('.nav-item[data-section]');
                for (var j = 0; j < allItems.length; j++) {
                    allItems[j].classList.remove('active');
                }
                item.classList.add('active');
                
                // Show corresponding panel
                showSection(section);
                
                // Close mobile menu
                if (window.innerWidth <= 1024) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                }
            });
        })(navItems[i]);
    }
}

function showSection(sectionName) {
    var panels = {
        'auth': document.getElementById('auth-panel'),
        'jwt': document.getElementById('jwt-panel'),
        'users': document.getElementById('users-section'),
        'files': document.getElementById('files-panel'),
        'request': document.getElementById('raw-request-section'),
        'response': document.getElementById('response-panel')
    };
    
    // Hide all panels
    for (var key in panels) {
        if (panels.hasOwnProperty(key) && panels[key]) {
            panels[key].style.display = 'none';
        }
    }
    
    // Show selected panel
    if (panels[sectionName]) {
        panels[sectionName].style.display = 'block';
        panels[sectionName].classList.add('slide-up');
    }
}

// ============================================
// THEME TOGGLE
// ============================================

function initThemeToggle() {
    var themeToggle = document.getElementById('theme-toggle');
    var html = document.documentElement;
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            var currentTheme = html.getAttribute('data-theme');
            var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            
            // Update icon
            var icon = themeToggle.querySelector('i');
            if (newTheme === 'dark') {
                icon.className = 'ph ph-moon';
            } else {
                icon.className = 'ph ph-sun';
            }
        });
    }
}

// ============================================
// SETUP ON PAGE LOAD
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('[APP] Initializing...');
    
    var loginBtn = document.getElementById('login-submit-btn');
    var logoutBtn = document.getElementById('logout-btn');
    var loginError = document.getElementById('login-error');
    var emailInput = document.getElementById('login-email');
    var passwordInput = document.getElementById('login-password');
    
    console.log('[APP] Elements:', {
        loginBtn: !!loginBtn,
        logoutBtn: !!logoutBtn,
        emailInput: !!emailInput,
        passwordInput: !!passwordInput
    });
    
    // Handle login submission
    var handleLoginSubmit = function() {
        console.log('[APP] Login submit');
        var email = emailInput ? emailInput.value : '';
        var password = passwordInput ? passwordInput.value : '';
        
        if (loginError) {
            loginError.classList.add('hidden');
            loginError.textContent = '';
        }
        
        login(email, password).then(function(result) {
            if (result.success) {
                updateAuthUI();
                showToast('Connected!', 'success');
            } else {
                if (loginError) {
                    loginError.textContent = result.error || 'Login failed';
                    loginError.classList.remove('hidden');
                }
            }
        });
    };
    
    // Login button click
    if (loginBtn) {
        console.log('[APP] Attaching login button listener');
        loginBtn.addEventListener('click', handleLoginSubmit);
    }
    
    // Enter key on email -> focus password
    if (emailInput) {
        emailInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (passwordInput) passwordInput.focus();
            }
        });
    }
    
    // Enter key on password -> submit
    if (passwordInput) {
        passwordInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleLoginSubmit();
            }
        });
    }
    
    // Logout button
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            logout().then(function() {
                updateAuthUI();
                showToast('Disconnected', 'info');
            });
        });
    }
    
    // Initialize all sections
    initApiUrlConfig();
    setupResponseViewer();
    setupUsersSection();
    setupFilesSection();
    setupRawRequestSection();
    initNavigation();
    initThemeToggle();
    
    // Initial state
    updateAuthUI();
    console.log('[APP] Initialized');
});
