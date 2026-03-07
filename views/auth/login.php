<style>
    .auth-container {
        display: flex;
        height: 100vh;
        background: var(--sw-bg);
    }

    .auth-left {
        flex: 0 0 40%;
        background: linear-gradient(135deg, var(--sw-primary) 0%, #0F2438 100%);
        color: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px;
        position: relative;
        overflow: hidden;
    }

    .auth-left::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 600px;
        height: 600px;
        background: rgba(232, 160, 32, 0.1);
        border-radius: 50%;
    }

    .auth-left::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -30%;
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
    }

    .auth-left-content {
        position: relative;
        z-index: 1;
        text-align: center;
    }

    .auth-logo {
        font-family: var(--sw-font-display);
        font-size: 48px;
        font-weight: 800;
        margin-bottom: 16px;
        color: var(--sw-accent);
    }

    .auth-logo-icon {
        inline-size: 64px;
        block-size: 64px;
        background: var(--sw-accent);
        border-radius: var(--sw-radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin: 0 auto 20px;
    }

    .auth-tagline {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 12px;
        line-height: 1.4;
    }

    .auth-features {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-top: 48px;
        max-width: 320px;
    }

    .auth-feature {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: 14px;
    }

    .auth-feature-icon {
        font-size: 20px;
        margin-top: 2px;
    }

    .auth-right {
        flex: 0 0 60%;
        background: var(--sw-surface);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }

    .auth-form-wrapper {
        width: 100%;
        max-width: 420px;
    }

    .auth-form-header {
        margin-bottom: 32px;
        text-align: center;
    }

    .auth-form-title {
        font-family: var(--sw-font-display);
        font-size: 32px;
        font-weight: 700;
        color: var(--sw-text);
        margin-bottom: 8px;
    }

    .auth-form-subtitle {
        font-size: 14px;
        color: var(--sw-text-muted);
    }

    .auth-tabs {
        display: flex;
        gap: 16px;
        margin-bottom: 32px;
        border-bottom: 2px solid var(--sw-border);
    }

    .auth-tab {
        padding: 12px 0;
        background: none;
        border: none;
        font-family: var(--sw-font-display);
        font-size: 14px;
        font-weight: 700;
        color: var(--sw-text-muted);
        cursor: pointer;
        position: relative;
        transition: color 0.2s ease;
    }

    .auth-tab.active {
        color: var(--sw-primary);
    }

    .auth-tab.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--sw-primary);
    }

    .auth-tab-content {
        display: none;
    }

    .auth-tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .sw-input-auth {
        width: 100%;
        padding: 12px 16px;
        border: 1.5px solid var(--sw-border);
        border-radius: var(--sw-radius-md);
        font-family: var(--sw-font-body);
        font-size: 14px;
        transition: all 0.2s ease;
        background: var(--sw-bg);
    }

    .sw-input-auth:focus {
        outline: none;
        border-color: var(--sw-primary);
        box-shadow: 0 0 0 3px var(--sw-primary-light);
        background: var(--sw-surface);
    }

    .sw-form-group-auth {
        margin-bottom: 20px;
    }

    .sw-form-group-auth label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--sw-text-muted);
        margin-bottom: 8px;
    }

    .sw-btn-auth {
        width: 100%;
        padding: 14px;
        background: var(--sw-primary);
        color: #fff;
        border: none;
        border-radius: var(--sw-radius-md);
        font-family: var(--sw-font-display);
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .sw-btn-auth:hover {
        background: var(--sw-primary-dark);
        box-shadow: var(--sw-shadow-lg);
    }

    .sw-btn-auth:active {
        transform: scale(0.98);
    }

    .pin-display {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin: 32px 0;
    }

    .pin-dot {
        width: 16px;
        height: 16px;
        border: 2px solid var(--sw-border);
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .pin-dot.filled {
        background: var(--sw-primary);
        border-color: var(--sw-primary);
        box-shadow: 0 0 0 4px var(--sw-primary-light);
    }

    .pin-keypad {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 24px;
    }

    .pin-key {
        aspect-ratio: 1;
        background: var(--sw-surface2);
        border: 1.5px solid var(--sw-border);
        border-radius: var(--sw-radius-lg);
        font-family: var(--sw-font-display);
        font-size: 24px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .pin-key:hover {
        background: var(--sw-primary-light);
        border-color: var(--sw-primary);
    }

    .pin-key:active {
        transform: scale(0.95);
    }

    .pin-key-delete {
        grid-column: 3;
        font-size: 16px;
    }

    .pin-key-clear {
        grid-column: 1 / -1;
        background: var(--sw-danger-light);
        border-color: var(--sw-danger);
        color: var(--sw-danger);
    }

    .pin-key-clear:hover {
        background: var(--sw-danger);
        color: #fff;
    }

    .auth-remember {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        margin-bottom: 24px;
    }

    .auth-remember input {
        width: 16px;
        height: 16px;
        cursor: pointer;
    }

    .auth-form-footer {
        text-align: center;
        font-size: 12px;
        color: var(--sw-text-muted);
        margin-top: 32px;
    }

    .auth-alerts {
        margin-bottom: 24px;
    }

    .sw-flash-auth {
        padding: 12px 16px;
        border-radius: var(--sw-radius-md);
        font-size: 13px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .sw-flash-auth.success {
        background: var(--sw-success-light);
        color: var(--sw-success);
        border-left: 4px solid var(--sw-success);
    }

    .sw-flash-auth.danger {
        background: var(--sw-danger-light);
        color: var(--sw-danger);
        border-left: 4px solid var(--sw-danger);
    }

    .sw-flash-auth.warning {
        background: var(--sw-warning-light);
        color: var(--sw-warning);
        border-left: 4px solid var(--sw-warning);
    }

    .sw-flash-auth.info {
        background: var(--sw-info-light);
        color: var(--sw-info);
        border-left: 4px solid var(--sw-info);
    }

    @media (max-width: 1024px) {
        .auth-left {
            flex: 0 0 35%;
        }
        .auth-right {
            flex: 0 0 65%;
        }
    }

    @media (max-width: 768px) {
        .auth-container {
            flex-direction: column;
        }
        .auth-left {
            flex: 0 0 auto;
            padding: 32px 20px;
            min-height: 200px;
        }
        .auth-left::before,
        .auth-left::after {
            display: none;
        }
        .auth-right {
            flex: 1;
            padding: 20px;
        }
        .auth-form-wrapper {
            max-width: 100%;
        }
        .auth-features {
            display: none;
        }
    }
</style>

<div class="auth-container">
    <!-- LEFT PANEL: BRANDING -->
    <div class="auth-left">
        <div class="auth-left-content">
            <div class="auth-logo-icon">🛍️</div>
            <div class="auth-logo"><?= e(APP_NAME) ?></div>
            <div class="auth-tagline">Point of Sale Management Made Simple</div>
            
            <div class="auth-features">
                <div class="auth-feature">
                    <div class="auth-feature-icon">🔒</div>
                    <div>Secure authentication with session management</div>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon">⚡</div>
                    <div>Lightning-fast inventory & sales tracking</div>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon">📊</div>
                    <div>AI-powered insights for smarter decisions</div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL: LOGIN FORM -->
    <div class="auth-right">
        <div class="auth-form-wrapper">
            <div class="auth-form-header">
                <div class="auth-form-title">Sign In</div>
                <div class="auth-form-subtitle">Access your ShopWise account</div>
            </div>

            <!-- ALERTS -->
            <div class="auth-alerts">
                <?php if (!empty($sessionMessage)): ?>
                    <div class="sw-flash-auth warning">
                        ⚠️ <?= e($sessionMessage) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($flash)): ?>
                    <div class="sw-flash-auth <?= e($flash['type'] === 'success' ? 'success' : ($flash['type'] === 'warning' ? 'warning' : ($flash['type'] === 'info' ? 'info' : 'danger'))) ?>">
                        <?php 
                            $icon = $flash['type'] === 'success' ? '✓' : ($flash['type'] === 'danger' ? '✕' : ($flash['type'] === 'warning' ? '⚠' : 'ℹ'));
                        ?>
                        <?= $icon ?> <?= e($flash['message']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TABS -->
            <div class="auth-tabs">
                <button class="auth-tab active" data-tab="password-tab">
                    🔐 Password
                </button>
                <button class="auth-tab" data-tab="pin-tab">
                    📱 PIN
                </button>
            </div>

            <!-- PASSWORD LOGIN -->
            <div id="password-tab" class="auth-tab-content active">
                <form method="POST" action="<?= e(BASE_URL) ?>/login" autocomplete="off">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? '') ?>">

                    <div class="sw-form-group-auth">
                        <label for="username">Username</label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="sw-input-auth" 
                               placeholder="Enter your username"
                               required 
                               autofocus>
                    </div>

                    <div class="sw-form-group-auth">
                        <label for="password">Password</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="sw-input-auth" 
                               placeholder="Enter your password"
                               required>
                    </div>

                    <div class="auth-remember">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember" style="margin: 0; cursor: pointer;">Remember me</label>
                    </div>

                    <button type="submit" class="sw-btn-auth">Sign In</button>
                </form>
            </div>

            <!-- PIN LOGIN -->
            <div id="pin-tab" class="auth-tab-content">
                <form method="POST" action="<?= e(BASE_URL) ?>/login/pin" autocomplete="off">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? '') ?>">

                    <div class="sw-form-group-auth">
                        <label for="cashier-username">Cashier Username</label>
                        <input type="text" 
                               id="cashier-username" 
                               name="username" 
                               class="sw-input-auth" 
                               placeholder="e.g. cashier1"
                               required>
                    </div>

                    <!-- PIN DISPLAY -->
                    <div class="pin-display" id="pinDisplay">
                        <div class="pin-dot"></div>
                        <div class="pin-dot"></div>
                        <div class="pin-dot"></div>
                        <div class="pin-dot"></div>
                        <div class="pin-dot"></div>
                        <div class="pin-dot"></div>
                    </div>

                    <input type="hidden" id="pinInput" name="pin" value="">

                    <!-- PIN KEYPAD -->
                    <div class="pin-keypad" id="pinKeypad">
                        <button type="button" class="pin-key" data-digit="1">1</button>
                        <button type="button" class="pin-key" data-digit="2">2</button>
                        <button type="button" class="pin-key" data-digit="3">3</button>

                        <button type="button" class="pin-key" data-digit="4">4</button>
                        <button type="button" class="pin-key" data-digit="5">5</button>
                        <button type="button" class="pin-key" data-digit="6">6</button>

                        <button type="button" class="pin-key" data-digit="7">7</button>
                        <button type="button" class="pin-key" data-digit="8">8</button>
                        <button type="button" class="pin-key" data-digit="9">9</button>

                        <button type="button" class="pin-key" data-digit="0">0</button>
                        <button type="button" class="pin-key pin-key-delete" id="pinDelete">⌫</button>
                    </div>

                    <button type="button" class="pin-key pin-key-clear" id="pinClear">Clear All</button>

                    <button type="submit" class="sw-btn-auth" style="margin-top: 24px;">Login with PIN</button>
                </form>
            </div>

            <div class="auth-form-footer">
                <?= e(STORE_NAME) ?> • <?= e(APP_VERSION) ?> • Powered by ShopWise AI
            </div>
        </div>
    </div>
</div>

<script>
    // Tab switching
    document.querySelectorAll('.auth-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const targetId = tab.dataset.tab;
            
            // Hide all tabs
            document.querySelectorAll('.auth-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.auth-tab').forEach(t => {
                t.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(targetId).classList.add('active');
            tab.classList.add('active');
            
            // Focus appropriate field
            if (targetId === 'password-tab') {
                document.getElementById('username').focus();
            } else {
                document.getElementById('cashier-username').focus();
            }
        });
    });

    // PIN Keypad
    const pinInput = document.getElementById('pinInput');
    const pinDisplay = document.getElementById('pinDisplay');
    const pinDots = Array.from(pinDisplay.querySelectorAll('.pin-dot'));
    
    document.getElementById('pinKeypad').addEventListener('click', (e) => {
        if (!e.target.classList.contains('pin-key')) return;
        
        const digit = e.target.dataset.digit;
        if (digit !== undefined) {
            if (pinInput.value.length < 6) {
                pinInput.value += digit;
                updatePinDisplay();
            }
        }
    });
    
    document.getElementById('pinDelete').addEventListener('click', (e) => {
        e.preventDefault();
        if (pinInput.value.length > 0) {
            pinInput.value = pinInput.value.slice(0, -1);
            updatePinDisplay();
        }
    });
    
    document.getElementById('pinClear').addEventListener('click', (e) => {
        e.preventDefault();
        pinInput.value = '';
        updatePinDisplay();
    });
    
    function updatePinDisplay() {
        const length = pinInput.value.length;
        pinDots.forEach((dot, index) => {
            if (index < length) {
                dot.classList.add('filled');
            } else {
                dot.classList.remove('filled');
            }
        });
    }
    
    // Keyboard support for PIN
    document.addEventListener('keydown', (e) => {
        if (document.getElementById('pin-tab').classList.contains('active')) {
            if (/^\d$/.test(e.key) && pinInput.value.length < 6) {
                pinInput.value += e.key;
                updatePinDisplay();
            } else if (e.key === 'Backspace') {
                e.preventDefault();
                if (pinInput.value.length > 0) {
                    pinInput.value = pinInput.value.slice(0, -1);
                    updatePinDisplay();
                }
            }
        }
    });
</script>
