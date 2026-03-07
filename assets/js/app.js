/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║              SHOPWISE AI — CORE JAVASCRIPT v5.0                     ║
 * ║         Global utilities, Flash, CSRF, Session, Sidebar             ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 */

(() => {
    'use strict';

    // ═══════════════════════════════════════════════════════════════════════
    // GLOBAL NAMESPACE
    // ═══════════════════════════════════════════════════════════════════════

    window.ShopWise = window.ShopWise || {};

    // ═══════════════════════════════════════════════════════════════════════
    // CSRF TOKEN MANAGEMENT
    // ═══════════════════════════════════════════════════════════════════════

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    let csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;
    const baseUrlMeta = document.querySelector('meta[name="base-url"]');
    const baseUrl = baseUrlMeta ? (baseUrlMeta.getAttribute('content') || '') : '';
    window.ShopWise.csrfToken = csrfToken;
    window.ShopWise.baseUrl = baseUrl;

    const syncCsrfInputs = (token) => {
        if (!token) return;

        document.querySelectorAll('input[name="_token"]').forEach((input) => {
            input.value = token;
        });
    };

    syncCsrfInputs(csrfToken);

    // ═══════════════════════════════════════════════════════════════════════
    // FLASH MESSAGES — AUTO-DISMISS AFTER 4 SECONDS
    // ═══════════════════════════════════════════════════════════════════════

    const flashMessages = document.querySelectorAll('.sw-flash');
    flashMessages.forEach((flash) => {
        // Close button
        const closeBtn = flash.querySelector('[data-flash-close]');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                flash.style.animation = 'slideUp 0.3s ease';
                setTimeout(() => flash.remove(), 300);
            });
        }

        // Auto-dismiss after 4 seconds
        setTimeout(() => {
            if (flash.parentElement) {
                flash.style.animation = 'slideUp 0.3s ease';
                setTimeout(() => flash.remove(), 300);
            }
        }, 4000);
    });

    // ═══════════════════════════════════════════════════════════════════════
    // TOAST NOTIFICATION SYSTEM
    // ═══════════════════════════════════════════════════════════════════════

    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.style.cssText = 'position:fixed;top:80px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:12px;';
        document.body.appendChild(toastContainer);
    }

    window.ShopWise.toast = (message, type = 'info') => {
        const toast = document.createElement('div');
        toast.className = `sw-flash sw-flash-${type}`;
        toast.style.cssText = 'min-width:320px;animation:slideDown 0.3s ease;';
        
        const icon = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        }[type] || 'ℹ';

        toast.innerHTML = `
            <span class="sw-flash-icon">${icon}</span>
            <span class="sw-flash-message">${message}</span>
            <button class="sw-flash-close" onclick="this.parentElement.remove()">×</button>
        `;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'slideUp 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }
        }, 4000);
    };

    // ═══════════════════════════════════════════════════════════════════════
    // CONFIRM MODAL — swConfirm(message, onConfirm)
    // ═══════════════════════════════════════════════════════════════════════

    window.ShopWise.confirm = (message, onConfirm) => {
        // Remove existing modal if any
        const existingModal = document.getElementById('swConfirmModal');
        if (existingModal) existingModal.remove();

        const modal = document.createElement('div');
        modal.id = 'swConfirmModal';
        modal.className = 'sw-modal-backdrop';
        modal.innerHTML = `
            <div class="sw-modal" style="max-width: 480px;">
                <div class="sw-modal-header">
                    <h3 class="sw-modal-title">Confirm Action</h3>
                </div>
                <div class="sw-modal-body">
                    <p>${message}</p>
                </div>
                <div class="sw-modal-footer">
                    <button class="sw-btn sw-btn-outline" data-action="cancel">Cancel</button>
                    <button class="sw-btn sw-btn-danger" data-action="confirm">Confirm</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        
        // Focus on confirm button
        setTimeout(() => {
            modal.querySelector('[data-action="confirm"]').focus();
        }, 100);

        // Event handlers
        modal.querySelector('[data-action="cancel"]').addEventListener('click', () => {
            modal.remove();
        });

        modal.querySelector('[data-action="confirm"]').addEventListener('click', () => {
            modal.remove();
            if (typeof onConfirm === 'function') onConfirm();
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });

        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', escHandler);
            }
        });
    };

    // ═══════════════════════════════════════════════════════════════════════
    // FETCH WITH CSRF TOKEN
    // ═══════════════════════════════════════════════════════════════════════

    window.ShopWise.fetchJson = async (url, options = {}) => {
        const resolveUrl = (inputUrl) => {
            if (!inputUrl) return inputUrl;
            if (/^https?:\/\//i.test(inputUrl)) return inputUrl;
            if (baseUrl && inputUrl.startsWith(baseUrl + '/')) {
                return inputUrl;
            }
            if (inputUrl.startsWith('/') && baseUrl) {
                return baseUrl + inputUrl;
            }
            return inputUrl;
        };

        let requestBody = options.body;

        if (requestBody && typeof requestBody === 'object' && csrfToken && !Array.isArray(requestBody)) {
            requestBody = {
                ...requestBody,
                _token: requestBody._token || csrfToken,
            };
        }

        const config = {
            method: options.method || 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                ...(options.headers || {}),
            },
            body: requestBody ? JSON.stringify(requestBody) : null,
        };

        if (csrfToken) {
            config.headers['X-CSRF-TOKEN'] = csrfToken;
        }

        const response = await fetch(resolveUrl(url), config);

        const refreshedToken = response.headers.get('X-CSRF-TOKEN');
        if (refreshedToken) {
            csrfToken = refreshedToken;
            window.ShopWise.csrfToken = refreshedToken;
            if (csrfMeta) {
                csrfMeta.setAttribute('content', refreshedToken);
            }
            syncCsrfInputs(refreshedToken);
        }

        const contentType = (response.headers.get('content-type') || '').toLowerCase();
        const rawText = await response.text();

        let payload = null;
        try {
            if (contentType.includes('application/json')) {
                payload = rawText ? JSON.parse(rawText) : {};
            } else {
                payload = rawText ? JSON.parse(rawText) : {};
            }
        } catch {
            try {
                payload = rawText ? JSON.parse(rawText) : {};
            } catch {
                payload = null;
            }
        }

        if (!payload) {
            if (response.status === 401 || response.status === 403 || response.redirected) {
                throw new Error('Session expired or access denied. Please login again.');
            }
            throw new Error('Server returned invalid response. Please refresh and try again.');
        }
        
        if (!response.ok) {
            throw new Error(payload.message || 'Request failed');
        }
        
        return payload;
    };

    // ═══════════════════════════════════════════════════════════════════════
    // FORM DIRTY CHECK
    // ═══════════════════════════════════════════════════════════════════════

    const forms = document.querySelectorAll('[data-dirty-check]');
    forms.forEach((form) => {
        const initialData = new FormData(form);
        let isDirty = false;

        form.addEventListener('change', () => {
            const currentData = new FormData(form);
            isDirty = false;
            
            for (let [key, value] of currentData.entries()) {
                if (initialData.get(key) !== value) {
                    isDirty = true;
                    break;
                }
            }
        });

        window.addEventListener('beforeunload', (e) => {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });

        form.addEventListener('submit', () => {
            isDirty = false;
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // TABLE SORT
    // ═══════════════════════════════════════════════════════════════════════

    const sortableTables = document.querySelectorAll('[data-sortable]');
    sortableTables.forEach((table) => {
        const headers = table.querySelectorAll('th[data-sort]');
        
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.innerHTML += ' <span class="sort-indicator">⇅</span>';
            
            header.addEventListener('click', () => {
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const ascending = header.dataset.sortDir !== 'asc';
                
                // Reset all indicators
                headers.forEach(h => {
                    h.querySelector('.sort-indicator').textContent = '⇅';
                    delete h.dataset.sortDir;
                });
                
                // Set current indicator
                header.dataset.sortDir = ascending ? 'asc' : 'desc';
                header.querySelector('.sort-indicator').textContent = ascending ? '▲' : '▼';
                
                rows.sort((a, b) => {
                    const aVal = a.children[index].textContent.trim();
                    const bVal = b.children[index].textContent.trim();
                    
                    // Try numeric comparison first
                    const aNum = parseFloat(aVal.replace(/[₱,]/g, ''));
                    const bNum = parseFloat(bVal.replace(/[₱,]/g, ''));
                    
                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return ascending ? aNum - bNum : bNum - aNum;
                    }
                    
                    // Fallback to string comparison
                    return ascending 
                        ? aVal.localeCompare(bVal) 
                        : bVal.localeCompare(aVal);
                });
                
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // SIDEBAR COLLAPSE — PERSIST TO LOCALSTORAGE
    // ═══════════════════════════════════════════════════════════════════════

    const sidebar = document.querySelector('.sw-sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (sidebar && sidebarToggle) {
        // Restore state
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
        }
        
        // Toggle handler
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SESSION TIMEOUT WARNING
    // ═══════════════════════════════════════════════════════════════════════

    const sessionTimeout = parseInt(document.body.dataset.sessionTimeout || '0');
    if (sessionTimeout > 0) {
        let idleTime = 0;
        const warningTime = sessionTimeout - 60; // Warn 1 minute before timeout
        
        const idleInterval = setInterval(() => {
            idleTime++;
            
            if (idleTime >= warningTime && idleTime < sessionTimeout) {
                if (!document.getElementById('sessionWarning')) {
                    const remaining = sessionTimeout - idleTime;
                    window.ShopWise.toast(
                        `Your session will expire in ${remaining} seconds. Move your mouse to stay logged in.`,
                        'warning'
                    );
                }
            }
            
            if (idleTime >= sessionTimeout) {
                window.location.href = '/auth/login?reason=timeout';
            }
        }, 1000);
        
        // Reset idle timer on user activity
        ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach((event) => {
            document.addEventListener(event, () => {
                idleTime = 0;
            }, { passive: true });
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // NOTIFICATION BELL — AJAX POLL EVERY 60 SECONDS
    // ═══════════════════════════════════════════════════════════════════════

    const notificationBell = document.getElementById('notificationBell');
    const notificationBadge = document.getElementById('notificationBadge');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    if (notificationBell && notificationDropdown) {
        // Toggle dropdown
        notificationBell.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            notificationDropdown.classList.remove('show');
        });
        
        // Fetch notifications
        const fetchNotifications = async () => {
            try {
                const response = await window.ShopWise.fetchJson('/notifications?limit=5');
                
                if (response.success) {
                    const { unread_count, notifications } = response.data;
                    
                    // Update badge
                    if (unread_count > 0) {
                        notificationBadge.textContent = unread_count > 9 ? '9+' : unread_count;
                        notificationBadge.style.display = 'block';
                    } else {
                        notificationBadge.style.display = 'none';
                    }
                    
                    // Update dropdown
                    if (notifications.length === 0) {
                        notificationDropdown.querySelector('.notification-list').innerHTML = 
                            '<div class="notification-empty">No new notifications</div>';
                    } else {
                        notificationDropdown.querySelector('.notification-list').innerHTML = 
                            notifications.map(n => `
                                <a href="${n.link || '#'}" class="notification-item ${n.is_read ? '' : 'unread'}">
                                    <div class="notification-icon">${n.icon || '🔔'}</div>
                                    <div class="notification-content">
                                        <div class="notification-title">${n.title}</div>
                                        <div class="notification-time">${window.ShopWise.timeAgo(n.created_at)}</div>
                                    </div>
                                </a>
                            `).join('');
                    }
                }
            } catch (error) {
                console.error('Failed to fetch notifications:', error);
            }
        };
        
        // Fetch immediately and then every 60 seconds
        fetchNotifications();
        setInterval(fetchNotifications, 60000);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPER FUNCTIONS
    // ═══════════════════════════════════════════════════════════════════════

    window.ShopWise.formatPeso = (amount) => {
        return '₱' + parseFloat(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    window.ShopWise.timeAgo = (dateString) => {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
        if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';
        
        return date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
    };

    window.ShopWise.debounce = (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    // ═══════════════════════════════════════════════════════════════════════
    // READY
    // ═══════════════════════════════════════════════════════════════════════

    console.log('ShopWise AI v5.0 initialized');
})();
