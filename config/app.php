<?php
/**
 * ShopWise AI — Application Configuration Constants
 *
 * All system-wide constants are defined here.
 * Edit this file to match your store's actual information.
 * Do NOT commit credentials or TIN numbers to public repositories.
 *
 * @package ShopWiseAI\Config
 */

declare(strict_types=1);

// ── Environment ─────────────────────────────────────────────────────────────
// 'development' → shows errors | 'production' → hides errors, logs them
define('APP_ENV',         'development');
define('APP_VERSION',     '5.0');
define('APP_NAME',        'ShopWise AI');

// ── URL & Paths ──────────────────────────────────────────────────────────────
// Adjust BASE_URL if your XAMPP subfolder is different
define('BASE_URL',        'http://localhost/ShopWise_ai');
define('ASSET_URL',       BASE_URL . '/assets');
define('UPLOAD_URL',      BASE_URL . '/uploads');

// ── Store Information ────────────────────────────────────────────────────────
// These are used on receipts, reports, and BIR compliance documents.
define('STORE_NAME',      'ShopWise Main Branch');
define('STORE_ADDRESS',   'Sucat Road, Parañaque City, Metro Manila 1700');
define('STORE_PHONE',     '(02) 8888-1234');
define('STORE_EMAIL',     'store@shopwise.ph');
define('STORE_TIN',       '123-456-789-000');          // BIR Taxpayer Identification Number
define('STORE_VAT_REG',   'VAT-REG-2024-001234');      // BIR VAT Registration Number
define('BRANCH_ID',       1);                           // Default branch — schema is multi-branch ready

// ── Currency & Locale ────────────────────────────────────────────────────────
define('CURRENCY_SYMBOL', '₱');
define('CURRENCY_CODE',   'PHP');
define('LOCALE',          'en_PH');
define('TIMEZONE',        'Asia/Manila');
define('DATE_FORMAT_DISPLAY', 'M d, Y');
define('DATETIME_FORMAT_DISPLAY', 'M d, Y h:i A');
define('TIME_FORMAT_DISPLAY', 'h:i A');

// Set default timezone globally
date_default_timezone_set(TIMEZONE);

// ── Tax Configuration ────────────────────────────────────────────────────────
// Philippine VAT is 12% inclusive in the selling price
// Formula: VAT amount = price - (price / 1.12)
define('VAT_RATE',                0.12);   // 12% — Philippine NIRC Section 106
define('VAT_DIVISOR',             1.12);   // Used in inclusive VAT extraction

// Senior Citizen / PWD discount per RA 9994 & RA 10754
define('SENIOR_PWD_DISCOUNT',     0.20);   // 20% discount off pre-VAT price
define('SENIOR_PWD_VAT_EXEMPT',   true);   // Senior/PWD purchases are VAT-exempt

// ── Pricing Defaults ─────────────────────────────────────────────────────────
define('DEFAULT_MARKUP_PCT',      35.0);    // Default markup target in percent
define('MIN_MARGIN_PCT',          10.0);    // Warn if margin falls below this

// ── POS Configuration ────────────────────────────────────────────────────────
define('POS_SHIFT_FLOAT',         1000.00);  // Default opening cash per shift (₱1,000)
define('POS_VARIANCE_FLAG',       100.00);   // Flag cash variance above ₱100
define('POS_IDLE_LOCK_MINUTES',   10);        // Auto-lock POS after N minutes idle
define('POS_RECEIPT_COPIES',      1);         // Default receipt print copies
define('OR_PREFIX',               'OR');      // Official Receipt number prefix
define('TXN_PREFIX',              'TXN');     // Transaction number prefix

// ── Receipt Configuration ────────────────────────────────────────────────────
define('RECEIPT_FOOTER_LINE1',    'Thank you for shopping at ' . STORE_NAME . '!');
define('RECEIPT_FOOTER_LINE2',    'Please keep this receipt for returns within 7 days.');
define('RECEIPT_FOOTER_LINE3',    'This serves as your Official Receipt.');

// ── Loyalty Program ──────────────────────────────────────────────────────────
define('LOYALTY_POINTS_PER_PESO', 0.10);     // ₱10 spent = 1 point
define('LOYALTY_PESO_PER_POINT',  0.50);     // 1 point = ₱0.50 off
define('LOYALTY_MIN_REDEEM',      50);        // Minimum 50 points to redeem
define('LOYALTY_SILVER_THRESHOLD',  1000);   // Points needed for Silver tier
define('LOYALTY_GOLD_THRESHOLD',    5000);   // Points needed for Gold tier
define('LOYALTY_PLATINUM_THRESHOLD',10000);  // Points needed for Platinum tier

// ── Session & Security ───────────────────────────────────────────────────────
define('SESSION_TIMEOUT',         3600);      // Auto-logout after 1 hour of inactivity
define('SESSION_WARNING',         120);       // Show warning 2 minutes before timeout
define('MAX_LOGIN_ATTEMPTS',      5);         // Lock account after N failed logins
define('LOCKOUT_DURATION',        900);       // Lockout for 15 minutes (seconds)
define('CSRF_TOKEN_LENGTH',       32);        // CSRF token bytes
define('PASSWORD_MIN_LENGTH',     8);

// ── File Uploads ─────────────────────────────────────────────────────────────
define('MAX_UPLOAD_SIZE',         5242880);   // 5MB in bytes
define('UPLOAD_MAX_SIZE_MB',      5);
define('ALLOWED_IMAGE_TYPES',     ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('PRODUCT_IMAGE_WIDTH',     400);       // Max product image width px
define('PRODUCT_IMAGE_HEIGHT',    400);       // Max product image height px

// ── Pagination ───────────────────────────────────────────────────────────────
define('PER_PAGE',                25);        // Default rows per page
define('PER_PAGE_OPTIONS',        [10, 25, 50, 100]);
define('PAGINATION_MAX_LINKS',    7);

// ── AI Configuration (defaults — overridden by ai_settings table) ────────────
define('AI_WMA_WEIGHTS',          [0.10, 0.20, 0.30, 0.40]); // Oldest → newest week weights
define('AI_SAFETY_MULTIPLIER',    1.5);       // Safety stock multiplier
define('AI_MAX_PROMO_DISCOUNT',   30.0);      // Maximum AI-generated promo discount %
define('AI_DEAD_STOCK_DAYS',      60);        // No movement in N days = dead stock
define('AI_FORECAST_HORIZON',     14);        // Forecast N days ahead

// ── Backup Configuration ─────────────────────────────────────────────────────
// Path to mysqldump executable on your system
if (PHP_OS_FAMILY === 'Windows') {
    define('MYSQLDUMP_PATH', 'C:/xamppNew/mysql/bin/mysqldump.exe');
    define('MYSQL_PATH',     'C:/xamppNew/mysql/bin/mysql.exe');
} else {
    define('MYSQLDUMP_PATH', '/usr/bin/mysqldump');
    define('MYSQL_PATH',     '/usr/bin/mysql');
}
define('DB_HOST',     'localhost');
define('DB_NAME',     'shopwise_db');
define('DB_USER',     'root');
define('DB_PASS',     '');

// ── Stock Status Thresholds ──────────────────────────────────────────────────
define('STOCK_OUT_THRESHOLD',   0);    // At or below = Out of Stock
define('EXPIRY_CRITICAL_DAYS',  7);    // Within 7 days = Critical expiry
define('EXPIRY_WARNING_DAYS',   14);   // Within 14 days = Warning expiry

// ── Module Feature Flags ─────────────────────────────────────────────────────
define('FEATURE_LOYALTY',       true);   // Enable loyalty program
define('FEATURE_AI_INSIGHTS',   true);   // Enable AI engine
define('FEATURE_MULTI_BRANCH',  false);  // Currently single-branch mode
define('FEATURE_ECOMMERCE',     false);  // Future: online store integration