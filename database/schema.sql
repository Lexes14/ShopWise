-- ╔══════════════════════════════════════════════════════════════════════╗
-- ║           SHOPWISE AI — COMPLETE DATABASE SCHEMA                    ║
-- ║                    MySQL 8.0 · InnoDB · utf8mb4                     ║
-- ╚══════════════════════════════════════════════════════════════════════╝

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing database and recreate
DROP DATABASE IF EXISTS shopwise_db;
CREATE DATABASE shopwise_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shopwise_db;

-- ══════════════════════════════════════════════════════════════════════
-- GROUP 1: USERS & ACCESS  
-- ══════════════════════════════════════════════════════════════════════

CREATE TABLE roles (
    role_id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_slug VARCHAR(30) NOT NULL UNIQUE,
    role_name VARCHAR(60) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (role_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id TINYINT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED DEFAULT 1,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    pin_hash VARCHAR(255) NULL COMMENT 'For cashier PIN login',
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    avatar_path VARCHAR(255) NULL,
    status ENUM('active','inactive','locked') DEFAULT 'active',
    failed_logins TINYINT UNSIGNED DEFAULT 0,
    locked_until DATETIME NULL,
    last_login DATETIME NULL,
    last_ip VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_username (username),
    INDEX idx_status (status),
    INDEX idx_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_sessions (
    session_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_token VARCHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_logs (
    log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    username_attempted VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    success TINYINT(1) DEFAULT 0,
    failure_reason VARCHAR(100) NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_attempted_at (attempted_at),
    INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- GROUP 2: STORE CONFIG  
-- ══════════════════════════════════════════════════════════════════════

CREATE TABLE branches (
    branch_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_settings (
    setting_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(80) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_group VARCHAR(50),
    description TEXT,
    updated_by INT UNSIGNED NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key),
    INDEX idx_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- GROUP 3: PRODUCT CATALOG  
-- ══════════════════════════════════════════════════════════════════════

CREATE TABLE categories (
    category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED NULL,
    category_name VARCHAR(60) NOT NULL,
    icon VARCHAR(20) NULL COMMENT 'Emoji icon',
    sort_order SMALLINT UNSIGNED DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    INDEX idx_parent (parent_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE brands (
    brand_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    brand_name VARCHAR(60) NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (brand_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE suppliers (
    supplier_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    alt_phone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    viber VARCHAR(20) NULL,
    address TEXT,
    payment_terms VARCHAR(60),
    credit_limit DECIMAL(12,2) DEFAULT 0.00,
    lead_time_days TINYINT UNSIGNED DEFAULT 7,
    delivery_days VARCHAR(50) NULL COMMENT 'e.g., Mon,Wed,Fri',
    min_order_value DECIMAL(12,2) DEFAULT 0.00,
    bank_details TEXT NULL,
    contract_start DATE NULL,
    contract_end DATE NULL,
    status ENUM('active','inactive','blacklisted') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (supplier_name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED DEFAULT 1,
    product_code VARCHAR(30) NOT NULL UNIQUE,
    product_name VARCHAR(150) NOT NULL,
    product_alias VARCHAR(150) NULL COMMENT 'Search alias',
    category_id INT UNSIGNED NOT NULL,
    brand_id INT UNSIGNED NULL,
    primary_supplier_id INT UNSIGNED NULL,
    secondary_supplier_id INT UNSIGNED NULL,
    unit_of_measure VARCHAR(20) DEFAULT 'pc',
    storage_condition ENUM('dry','chilled','frozen') DEFAULT 'dry',
    description TEXT NULL,
    is_vatable TINYINT(1) DEFAULT 1,
    
    cost_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    avg_cost_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    selling_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    wholesale_price DECIMAL(12,2) DEFAULT 0.00,
    min_selling_price DECIMAL(12,2) DEFAULT 0.00,
    promo_price DECIMAL(12,2) NULL,
    promo_start DATETIME NULL,
    promo_end DATETIME NULL,
    
    markup_pct DECIMAL(5,2) GENERATED ALWAYS AS (
        ROUND(((selling_price-cost_price)/NULLIF(cost_price,0))*100,2)
    ) STORED,
    margin_pct DECIMAL(5,2) GENERATED ALWAYS AS (
        ROUND(((selling_price-cost_price)/NULLIF(selling_price,0))*100,2)
    ) STORED,
    
    minimum_stock INT UNSIGNED DEFAULT 10,
    reorder_point INT UNSIGNED DEFAULT 20,
    reorder_qty INT UNSIGNED DEFAULT 50,
    maximum_stock INT UNSIGNED DEFAULT 500,
    current_stock INT UNSIGNED DEFAULT 0,
    
    image_path VARCHAR(255) NULL,
    status ENUM('active','inactive','discontinued','seasonal') DEFAULT 'active',
    
    created_by INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT,
    FOREIGN KEY (brand_id) REFERENCES brands(brand_id) ON DELETE SET NULL,
    FOREIGN KEY (primary_supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL,
    FOREIGN KEY (secondary_supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE CASCADE,
    
    INDEX idx_code (product_code),
    INDEX idx_name (product_name),
    INDEX idx_category (category_id),
    INDEX idx_brand (brand_id),
    INDEX idx_status (status),
    INDEX idx_current_stock (current_stock)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_images (
    image_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order TINYINT UNSIGNED DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE price_history (
    history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    field_changed VARCHAR(30) NOT NULL,
    old_value DECIMAL(12,2),
    new_value DECIMAL(12,2),
    changed_by INT UNSIGNED,
    reason VARCHAR(200),
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- GROUP 4: INVENTORY  
-- ══════════════════════════════════════════════════════════════════════

CREATE TABLE purchase_orders (
    po_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(30) NOT NULL UNIQUE,
    branch_id INT UNSIGNED DEFAULT 1,
    supplier_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    approved_by INT UNSIGNED NULL,
    status ENUM('draft','pending_approval','approved','sent','partially_received','fully_received','cancelled') DEFAULT 'draft',
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    expected_delivery DATE NULL,
    actual_delivery DATE NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_po_number (po_number),
    INDEX idx_supplier (supplier_id),
    INDEX idx_status (status),
    INDEX idx_expected (expected_delivery)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE po_items (
    item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    qty_ordered INT UNSIGNED NOT NULL,
    unit_cost DECIMAL(12,2) NOT NULL,
    qty_received INT UNSIGNED DEFAULT 0,
    ai_suggested_qty INT UNSIGNED NULL COMMENT 'AI recommendation',
    notes TEXT NULL,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    INDEX idx_po (po_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE batches (
    batch_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    po_id INT UNSIGNED NULL,
    batch_number VARCHAR(50) NOT NULL,
    qty_received INT UNSIGNED NOT NULL,
    qty_remaining INT UNSIGNED NOT NULL,
    delivery_date DATE NOT NULL,
    mfg_date DATE NULL,
    expiration_date DATE NULL,
    cost_price DECIMAL(12,2) NOT NULL,
    storage_location VARCHAR(60) NULL,
    status ENUM('active','depleted','expired','recalled','quarantined') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_batch_number (batch_number),
    INDEX idx_expiry (expiration_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_movements (
    movement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    batch_id INT UNSIGNED NULL,
    branch_id INT UNSIGNED DEFAULT 1,
    movement_type ENUM('sale','delivery','adjustment','transfer','return','waste','stocktake') NOT NULL,
    quantity_in INT DEFAULT 0,
    quantity_out INT DEFAULT 0,
    reference_type VARCHAR(30) NULL COMMENT 'e.g., transaction, po, adjustment',
    reference_id INT UNSIGNED NULL,
    performed_by INT UNSIGNED NULL,
    notes VARCHAR(200) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_type (movement_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_adjustments (
    adjustment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    batch_id INT UNSIGNED NULL,
    branch_id INT UNSIGNED DEFAULT 1,
    adjustment_type ENUM('damage','expired','theft','spoilage','correction','customer_return','supplier_return','transfer') NOT NULL,
    quantity INT NOT NULL COMMENT 'Can be negative',
    reason TEXT NOT NULL,
    reference_doc VARCHAR(100) NULL,
    requested_by INT UNSIGNED NOT NULL,
    approved_by INT UNSIGNED NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    admin_note TEXT NULL,
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    actioned_at DATETIME NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE SET NULL,
    FOREIGN KEY (requested_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_status (status),
    INDEX idx_requested_at (requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stocktake_sessions (
    session_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED DEFAULT 1,
    session_type ENUM('full','cycle','spot') NOT NULL,
    category_id INT UNSIGNED NULL,
    started_by INT UNSIGNED NOT NULL,
    completed_by INT UNSIGNED NULL,
    status ENUM('in_progress','completed','cancelled') DEFAULT 'in_progress',
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (started_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stocktake_items (
    item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    expected_qty INT UNSIGNED NOT NULL,
    counted_qty INT UNSIGNED NULL,
    variance_qty INT GENERATED ALWAYS AS (counted_qty - expected_qty) STORED,
    counted_by INT UNSIGNED NULL,
    counted_at DATETIME NULL,
    FOREIGN KEY (session_id) REFERENCES stocktake_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    FOREIGN KEY (counted_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_session (session_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shelves (
    shelf_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED DEFAULT 1,
    shelf_name VARCHAR(30) NOT NULL,
    aisle VARCHAR(10) NULL,
    position VARCHAR(20) NULL,
    capacity SMALLINT UNSIGNED DEFAULT 100,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shelf_assignments (
    assignment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shelf_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    qty_placed INT UNSIGNED NOT NULL,
    min_display_qty INT UNSIGNED DEFAULT 5,
    assigned_by INT UNSIGNED NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_shelf_product (shelf_id, product_id),
    FOREIGN KEY (shelf_id) REFERENCES shelves(shelf_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_shelf (shelf_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE deliveries (
    delivery_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_id INT UNSIGNED NOT NULL,
    received_by INT UNSIGNED NOT NULL,
    delivery_date DATE NOT NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE RESTRICT,
    FOREIGN KEY (received_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_po (po_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE delivery_items (
    di_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT UNSIGNED NOT NULL,
    po_item_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    qty_received INT UNSIGNED NOT NULL,
    batch_number VARCHAR(50) NOT NULL,
    expiration_date DATE NULL,
    actual_unit_cost DECIMAL(12,2) NOT NULL,
    discrepancy_note TEXT NULL,
    FOREIGN KEY (delivery_id) REFERENCES deliveries(delivery_id) ON DELETE CASCADE,
    FOREIGN KEY (po_item_id) REFERENCES po_items(item_id) ON DELETE RESTRICT,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    INDEX idx_delivery (delivery_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- GROUP 5: SUPPLIER PERFORMANCE  
-- ══════════════════════════════════════════════════════════════════════

CREATE TABLE supplier_contacts (
    contact_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    contact_name VARCHAR(100) NOT NULL,
    role VARCHAR(50),
    phone VARCHAR(20),
    email VARCHAR(100),
    is_primary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    INDEX idx_supplier (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_products (
    sp_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    supplier_sku VARCHAR(50) NULL,
    current_cost DECIMAL(12,2) NOT NULL,
    min_order_qty INT UNSIGNED DEFAULT 1,
    is_primary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY uq_supplier_product (supplier_id, product_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_price_history (
    ph_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    old_price DECIMAL(12,2),
    new_price DECIMAL(12,2),
    effective_date DATE NOT NULL,
    reason VARCHAR(200),
    recorded_by INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_supplier (supplier_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_performance (
    perf_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    po_id INT UNSIGNED NOT NULL,
    expected_date DATE NOT NULL,
    actual_date DATE NULL,
    days_variance TINYINT NULL COMMENT 'Negative = early, Positive = late',
    fill_rate DECIMAL(5,2) NULL COMMENT 'Percentage of items fulfilled',
    quality_rating TINYINT UNSIGNED NULL COMMENT '1-5 stars',
    quality_notes TEXT NULL,
    recorded_by INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE CASCADE,
    INDEX idx_supplier (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- GROUP 6: POS & SALES  
-- ══════════════════════════════════════════════════════════════════════

CREATE TABLE shifts (
    shift_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED DEFAULT 1,
    cashier_id INT UNSIGNED NOT NULL,
    shift_label ENUM('Morning','Afternoon','Evening','Graveyard','Custom') NOT NULL,
    custom_label VARCHAR(50) NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NULL,
    opening_cash DECIMAL(12,2) DEFAULT 0.00,
    closing_cash DECIMAL(12,2) NULL,
    expected_cash DECIMAL(12,2) NULL,
    cash_variance DECIMAL(12,2) NULL,
    denomination_json JSON NULL,
    total_cash_sales DECIMAL(12,2) DEFAULT 0.00,
    total_ewallet_sales DECIMAL(12,2) DEFAULT 0.00,
    total_card_sales DECIMAL(12,2) DEFAULT 0.00,
    total_voids INT UNSIGNED DEFAULT 0,
    total_refunds INT UNSIGNED DEFAULT 0,
    status ENUM('open','closed') DEFAULT 'open',
    manager_verified TINYINT(1) DEFAULT 0,
    manager_id INT UNSIGNED NULL,
    notes TEXT NULL,
    FOREIGN KEY (cashier_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (manager_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_cashier (cashier_id),
    INDEX idx_status (status),
    INDEX idx_start_time (start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cash_movements (
    movement_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shift_id INT UNSIGNED NOT NULL,
    cashier_id INT UNSIGNED NOT NULL,
    movement_type ENUM('drop','pickup','open','close') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    notes TEXT NULL,
    recorded_by INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shift_id) REFERENCES shifts(shift_id) ON DELETE CASCADE,
    FOREIGN KEY (cashier_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_shift (shift_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
    customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NULL,
    birthday DATE NULL,
    address TEXT NULL,
    tier ENUM('bronze','silver','gold','platinum') DEFAULT 'bronze',
    points_balance INT DEFAULT 0,
    total_spend DECIMAL(12,2) DEFAULT 0.00,
    total_visits INT UNSIGNED DEFAULT 0,
    last_visit DATETIME NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_tier (tier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transactions (
    transaction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_number VARCHAR(30) NOT NULL UNIQUE,
    or_number VARCHAR(30) NOT NULL UNIQUE,
    branch_id INT UNSIGNED DEFAULT 1,
    shift_id INT UNSIGNED NOT NULL,
    cashier_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NULL,
    customer_type ENUM('regular','senior','pwd') DEFAULT 'regular',
    senior_id_number VARCHAR(50) NULL,
    
    vatable_sales DECIMAL(12,2) DEFAULT 0.00,
    vat_exempt_sales DECIMAL(12,2) DEFAULT 0.00,
    vat_amount DECIMAL(12,2) DEFAULT 0.00,
    subtotal DECIMAL(12,2) NOT NULL,
    
    discount_type ENUM('none','senior_pwd','promotional','manual') DEFAULT 'none',
    discount_amount DECIMAL(12,2) DEFAULT 0.00,
    discount_authorized_by INT UNSIGNED NULL,
    
    total_amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cash','gcash','maya','card','split') NOT NULL,
    amount_tendered DECIMAL(12,2) NOT NULL,
    change_amount DECIMAL(12,2) NOT NULL,
    
    gcash_ref VARCHAR(50) NULL,
    maya_ref VARCHAR(50) NULL,
    card_approval_code VARCHAR(50) NULL,
    
    points_earned INT DEFAULT 0,
    points_redeemed INT DEFAULT 0,
    
    status ENUM('completed','voided','refunded','held') DEFAULT 'completed',
    void_reason TEXT NULL,
    voided_by INT UNSIGNED NULL,
    voided_at DATETIME NULL,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (shift_id) REFERENCES shifts(shift_id) ON DELETE RESTRICT,
    FOREIGN KEY (cashier_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL,
    FOREIGN KEY (voided_by) REFERENCES users(user_id) ON DELETE SET NULL,
    
    INDEX idx_transaction_number (transaction_number),
    INDEX idx_or_number (or_number),
    INDEX idx_shift (shift_id),
    INDEX idx_cashier (cashier_id),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transaction_items (
    item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    batch_id INT UNSIGNED NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    cost_price DECIMAL(12,2) NOT NULL,
    is_vatable TINYINT(1) DEFAULT 1,
    discount_amount DECIMAL(12,2) DEFAULT 0.00,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE SET NULL,
    INDEX idx_transaction (transaction_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE held_transactions (
    hold_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cashier_id INT UNSIGNED NOT NULL,
    shift_id INT UNSIGNED NOT NULL,
    label VARCHAR(60) NOT NULL,
    cart_json JSON NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cashier_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shifts(shift_id) ON DELETE CASCADE,
    INDEX idx_cashier (cashier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE void_logs (
    void_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    voided_by INT UNSIGNED NOT NULL,
    manager_pin_used TINYINT(1) DEFAULT 0,
    reason TEXT NOT NULL,
    items_json JSON NULL,
    voided_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (voided_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_transaction (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE refunds (
    refund_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    original_transaction_id BIGINT UNSIGNED NOT NULL,
    refund_number VARCHAR(30) NOT NULL UNIQUE,
    cashier_id INT UNSIGNED NOT NULL,
    authorized_by INT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    refund_amount DECIMAL(12,2) NOT NULL,
    restock TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (original_transaction_id) REFERENCES transactions(transaction_id) ON DELETE RESTRICT,
    FOREIGN KEY (cashier_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (authorized_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_refund_number (refund_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE refund_items (
    ri_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    refund_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    batch_id INT UNSIGNED NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    restocked TINYINT(1) DEFAULT 0,
    FOREIGN KEY (refund_id) REFERENCES refunds(refund_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    INDEX idx_refund (refund_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- GROUP 7: PROMOTIONS  
-- ══════════════════════════════════════════════════════════════════════

CREATE TABLE promotions (
    promo_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promo_name VARCHAR(100) NOT NULL,
    promo_type ENUM('price_discount','bxgy','bundle','threshold','near_expiry') NOT NULL,
    description TEXT NULL,
    discount_pct DECIMAL(5,2) NULL,
    discount_amount DECIMAL(12,2) NULL,
    min_qty INT UNSIGNED NULL COMMENT 'For BXGY',
    free_qty INT UNSIGNED NULL COMMENT 'For BXGY',
    threshold_amount DECIMAL(12,2) NULL COMMENT 'For threshold promo',
    threshold_discount DECIMAL(12,2) NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    applicable_to ENUM('product','category','all') NOT NULL,
    created_by INT UNSIGNED NULL,
    status ENUM('active','inactive','expired') DEFAULT 'active',
    ai_generated TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dates (start_datetime, end_datetime),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE promotion_products (
    pp_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promo_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    FOREIGN KEY (promo_id) REFERENCES promotions(promo_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY uq_promo_product (promo_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE promotion_categories (
    pc_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promo_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    FOREIGN KEY (promo_id) REFERENCES promotions(promo_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
    UNIQUE KEY uq_promo_category (promo_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE promotion_usage (
    usage_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promo_id INT UNSIGNED NOT NULL,
    transaction_id BIGINT UNSIGNED NOT NULL,
    discount_applied DECIMAL(12,2) NOT NULL,
    used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (promo_id) REFERENCES promotions(promo_id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE,
    INDEX idx_promo (promo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- GROUP 8: LOYALTY  
-- ══════════════════════════════════════════════════════════════════════

CREATE TABLE loyalty_points (
    lp_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    transaction_id BIGINT UNSIGNED NULL,
    points_type ENUM('earned','redeemed','adjusted','expired') NOT NULL,
    points_amount INT NOT NULL COMMENT 'Can be negative for redeemed/expired',
    balance_after INT NOT NULL,
    notes VARCHAR(200) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE SET NULL,
    INDEX idx_customer (customer_id),
    INDEX idx_type (points_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- GROUP 9: AI & ANALYTICS  
-- ══════════════════════════════════════════════════════════════════════

CREATE TABLE ai_demand_forecast (
    forecast_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    forecast_date DATE NOT NULL,
    avg_daily_sales DECIMAL(8,2) NOT NULL,
    max_daily_sales DECIMAL(8,2) NOT NULL,
    forecasted_7day_qty DECIMAL(8,2) NOT NULL,
    forecasted_14day_qty DECIMAL(8,2) NOT NULL,
    method_used VARCHAR(60) DEFAULT 'WMA-4WEEK',
    confidence_score DECIMAL(5,2) NOT NULL,
    seasonality_factor DECIMAL(5,4) DEFAULT 1.0000,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_product_date (product_id, forecast_date),
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_forecast_date (forecast_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_recommendations (
    rec_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rec_type ENUM('restock','promotion','dead_stock','supplier','staffing','substitution') NOT NULL,
    product_id INT UNSIGNED NULL,
    related_product_id INT UNSIGNED NULL,
    supplier_id INT UNSIGNED NULL,
    recommendation TEXT NOT NULL,
    reason TEXT NOT NULL,
    formula_used VARCHAR(500) NULL,
    confidence_score DECIMAL(5,2) NOT NULL,
    urgency ENUM('critical','urgent','normal','monitor') DEFAULT 'normal',
    suggested_value DECIMAL(12,2) NULL,
    status ENUM('pending','accepted','dismissed','expired') DEFAULT 'pending',
    acted_on_by INT UNSIGNED NULL,
    acted_on_at DATETIME NULL,
    expires_at DATETIME NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (related_product_id) REFERENCES products(product_id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL,
    FOREIGN KEY (acted_on_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_type (rec_type),
    INDEX idx_status (status),
    INDEX idx_urgency (urgency),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_feedback (
    feedback_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rec_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rating ENUM('helpful','not_helpful','partially_helpful') NOT NULL,
    comment TEXT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rec_id) REFERENCES ai_recommendations(rec_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_rec (rec_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_settings (
    setting_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(80) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL,
    description TEXT NULL,
    updated_by INT UNSIGNED NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- GROUP 10: SYSTEM  
-- ══════════════════════════════════════════════════════════════════════

CREATE TABLE audit_logs (
    log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    branch_id INT UNSIGNED DEFAULT 1,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(80) NOT NULL,
    record_id INT UNSIGNED NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_module_action (module, action),
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    notif_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL COMMENT 'NULL = broadcast',
    role_target VARCHAR(30) NULL COMMENT 'Target specific role',
    notif_type VARCHAR(50) NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE incident_reports (
    incident_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED DEFAULT 1,
    reported_by INT UNSIGNED NOT NULL,
    incident_type ENUM('theft','damage','safety','system','other') NOT NULL,
    description TEXT NOT NULL,
    items_involved TEXT NULL,
    estimated_loss DECIMAL(12,2) NULL,
    resolved TINYINT(1) DEFAULT 0,
    resolution_notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_type (incident_type),
    INDEX idx_resolved (resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_complaints (
    complaint_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NULL,
    product_id INT UNSIGNED NULL,
    reported_by INT UNSIGNED NOT NULL,
    complaint_text TEXT NOT NULL,
    status ENUM('open','in_progress','resolved') DEFAULT 'open',
    resolution TEXT NULL,
    resolved_by INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL,
    FOREIGN KEY (reported_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE backups (
    backup_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE price_labels_queue (
    label_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    queued_by INT UNSIGNED NOT NULL,
    printed TINYINT(1) DEFAULT 0,
    queued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    printed_at DATETIME NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (queued_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_product (product_id),
    INDEX idx_printed (printed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ══════════════════════════════════════════════════════════════════════
-- SAMPLE DATA INSERTION
-- ══════════════════════════════════════════════════════════════════════

-- Insert Branch
INSERT INTO branches (branch_id, branch_name, address, phone, status) VALUES
(1, 'ShopWise Main Branch', 'Aguirre Ave, BF Homes, Parañaque City, Metro Manila', '(02) 8888-1234', 'active');

-- Insert Roles
INSERT INTO roles (role_id, role_slug, role_name, description) VALUES
(1, 'owner', 'Owner', 'Full system access - all modules, all permissions'),
(2, 'manager', 'Store Manager', 'Full operational access - cannot manage users or restore backups'),
(3, 'inventory_staff', 'Inventory Staff', 'Manages stock, products, adjustments, and purchase orders'),
(4, 'cashier', 'Cashier', 'POS terminal access only - handles sales transactions'),
(5, 'purchasing_officer', 'Purchasing Officer', 'Manages suppliers and purchase orders'),
(6, 'security', 'Security Personnel', 'View-only access for monitoring and incident reporting'),
(7, 'bookkeeper', 'Bookkeeper/Accountant', 'Access to financial reports and audit logs');

-- Insert Users (passwords: Admin@2025, Manager@2025, Staff@2025, Cashier@2025, Purchase@2025, Security@2025, Accounts@2025)
-- Cashier PIN: 123456
INSERT INTO users (user_id, role_id, full_name, username, password_hash, pin_hash, email, phone, status) VALUES
(1, 1, 'Juan Dela Cruz', 'owner', '$2y$12$oHfudE0lJ.NelccLjdWQ.OaT9IPgbEak9Xqup7THos8Mf.liJN7ny', NULL, 'owner@shopwise.ph', '0917-123-4567', 'active'),
(2, 2, 'Maria Santos', 'manager', '$2y$12$ebIUIaX/QApgOtinBUad9OrIJh8fuf3USLgrGqoEo09oH5ULGP9BO', NULL, 'manager@shopwise.ph', '0918-234-5678', 'active'),
(3, 3, 'Pedro Reyes', 'inventory', '$2y$12$EJ1iPHj96MekhkXUQY6agu/hPr6Fr47QyWn0FZe48SAlyfEiIljbq', NULL, 'inventory@shopwise.ph', '0919-345-6789', 'active'),
(4, 4, 'Anna Garcia', 'cashier', '$2y$12$Dnqk0MJAGWPlaBH1fya5BOf9FlqdNu5dX3CSQX9N6QInpWX0a1A/e', '$2y$10$0JHgLkGI4.qFqpDjbC8e7uWkR2KZXvZ7pD5Vyg0KNV1ldGICpT1/W', 'cashier@shopwise.ph', '0920-456-7890', 'active'),
(5, 5, 'Roberto Cruz', 'purchasing', '$2y$12$N9jJsY2EL3pyvtEwnkfwc.XZmWLFCOI3TtDNJxlN8r/KMW1jxvZwm', NULL, 'purchasing@shopwise.ph', '0921-567-8901', 'active'),
(6, 6, 'Jose Ramos', 'security', '$2y$12$YhXw2kXz1X8oPTJpO7GpP.fGou7DOJXH.5wPeiXzIhHbqEeopCgJ6', NULL, 'security@shopwise.ph', '0922-678-9012', 'active'),
(7, 7, 'Lydia Mendoza', 'bookkeeper', '$2y$12$P98XnAuyjlhPQeLWyu.5S./rjACs.RSAOE9al5n9zIeCv9UZ2Rj3K', NULL, 'accounts@shopwise.ph', '0923-789-0123', 'active');

-- Insert Categories
INSERT INTO categories (category_id, parent_id, category_name, icon, sort_order, status) VALUES
(1, NULL, 'Snacks', '🍪', 1, 'active'),
(2, NULL, 'Beverages', '🥤', 2, 'active'),
(3, NULL, 'Personal Care', '🧴', 3, 'active'),
(4, NULL, 'Household', '🧹', 4, 'active'),
(5, NULL, 'Canned Goods', '🥫', 5, 'active'),
(6, NULL, 'Noodles', '🍜', 6, 'active'),
(7, NULL, 'Dairy Products', '🥛', 7, 'active'),
(8, NULL, 'Medicine & Health', '💊', 8, 'active'),
(9, NULL, 'Frozen Foods', '🧊', 9, 'active'),
(10, NULL, 'Bread & Bakery', '🍞', 10, 'active'),
(11, NULL, 'Condiments', '🧂', 11, 'active'),
(12, NULL, 'Candy & Sweets', '🍬', 12, 'active'),
(13, 1, 'Chips', '🥔', 1, 'active'),
(14, 1, 'Biscuits', '🍪', 2, 'active'),
(15, 2, 'Soft Drinks', '🥤', 1, 'active');

-- Insert Brands
INSERT INTO brands (brand_id, brand_name, status) VALUES
(1, 'Nestlé', 'active'),
(2, 'Unilever', 'active'),
(3, 'Procter & Gamble', 'active'),
(4, 'URC', 'active'),
(5, 'Monde Nissin', 'active'),
(6, 'San Miguel', 'active'),
(7, 'Coca-Cola', 'active'),
(8, 'Pepsi', 'active'),
(9, 'Oishi', 'active'),
(10, 'Jack n Jill', 'active'),
(11, 'Century Pacific', 'active'),
(12, 'Alaska', 'active'),
(13, 'Colgate-Palmolive', 'active'),
(14, 'Del Monte', 'active'),
(15, 'Liwayway', 'active'),
(16, 'Unilab', 'active'),
(17, 'Kopiko', 'active'),
(18, 'C2', 'active'),
(19, 'Safeguard', 'active'),
(20, 'Lucky Me', 'active');

-- Insert Suppliers
INSERT INTO suppliers (supplier_id, supplier_name, contact_person, phone, email, address, payment_terms, credit_limit, lead_time_days, delivery_days, status) VALUES
(1, 'Manila Trading Corp', 'Carlos Tan', '02-8234-5678', 'carlos@manilatrade.ph', '123 Rizal Ave, Manila', 'Net 30', 500000.00, 3, 'Mon,Wed,Fri', 'active'),
(2, 'Quezon City Distributors', 'Linda Cruz', '02-8345-6789', 'linda@qcdist.ph', '456 Commonwealth Ave, QC', 'Net 15', 300000.00, 2, 'Tue,Thu,Sat', 'active'),
(3, 'Makati Wholesale Inc', 'Robert Lim', '02-8456-7890', 'robert@makatiwholesale.ph', '789 Ayala Ave, Makati', 'COD', 0.00, 1, 'Daily', 'active'),
(4, 'Pasig Suppliers Network', 'Jenny Flores', '02-8567-8901', 'jenny@pasigsuppliers.ph', '321 Ortigas Ave, Pasig', 'Net 30', 400000.00, 5, 'Mon,Wed,Fri', 'active'),
(5, 'Caloocan Goods Supply', 'Michael Santos', '02-8678-9012', 'michael@caloocangoods.ph', '654 EDSA, Caloocan', 'Net 7', 200000.00, 3, 'Tue,Thu', 'active'),
(6, 'Paranaque Direct Imports', 'Teresa Ramos', '02-8789-0123', 'teresa@paraquedirect.ph', '987 Coastal Road, Paranaque', 'Net 45', 600000.00, 7, 'Mon,Fri', 'active');

-- Due to space limits, continuing with a representative sample of products
-- Insert 20 Sample Products (representative of all categories)
INSERT INTO products (product_id, product_code, product_name, product_alias, category_id, brand_id, primary_supplier_id, unit_of_measure, is_vatable, cost_price, avg_cost_price, selling_price, wholesale_price, min_selling_price, minimum_stock, reorder_point, reorder_qty, current_stock, status, created_by) VALUES
(1, 'BEV-C2-001', 'C2 Apple 500ml', 'c2 apple', 15, 18, 1, 'bottle', 1, 20.00, 20.00, 28.00, 26.00, 24.00, 50, 100, 200, 250, 'active', 1),
(2, 'SNK-OIS-001', 'Oishi Prawn Crackers 60g', 'oishi prawn', 13, 9, 1, 'pack', 1, 8.00, 8.00, 12.00, 11.00, 10.00, 30, 50, 100, 120, 'active', 1),
(3, 'NOD-LCK-001', 'Lucky Me Pancit Canton 60g', 'pancit canton', 6, 20, 2, 'pack', 1, 10.00, 10.00, 14.00, 13.00, 12.00, 40, 80, 150, 200, 'active', 1),
(4, 'CAN-LIG-001', 'Ligo Sardines in Tomato Sauce 155g', 'ligo sardines', 5, 11, 2, 'can', 1, 30.00, 30.00, 42.00, 40.00, 36.00, 20, 40, 80, 95, 'active', 1),
(5, 'BEV-KOP-001', 'Kopiko Brown 3-in-1 Coffee 30g', 'kopiko coffee', 2, 17, 1, 'sachet', 1, 5.50, 5.50, 8.00, 7.50, 7.00, 100, 200, 400, 550, 'active', 1),
(6, 'SNK-REG-001', 'Regent Cheese Rings 60g', 'regent cheese', 13, 4, 1, 'pack', 1, 7.00, 7.00, 10.00, 9.50, 9.00, 25, 50, 100, 85, 'active', 1),
(7, 'PER-SAF-001', 'Safeguard White Bar Soap 135g', 'safeguard soap', 3, 19, 3, 'bar', 1, 39.00, 39.00, 55.00, 52.00, 48.00, 15, 30, 60, 48, 'active', 1),
(8, 'DAI-BRD-001', 'Bear Brand Powdered Milk 33g', 'bear brand', 7, 1, 2, 'sachet', 1, 13.00, 13.00, 18.00, 17.00, 16.00, 50, 100, 200, 175, 'active', 1),
(9, 'BEV-MIL-001', 'Milo 3-in-1 Energy Drink 33g', 'milo', 2, 1, 2, 'sachet', 1, 8.50, 8.50, 12.00, 11.50, 10.50, 60, 120, 250, 285, 'active', 1),
(10, 'SNK-PIA-001', 'Piattos Cheese Flavor 85g', 'piattos cheese', 13, 10, 1, 'pack', 1, 21.00, 21.00, 30.00, 28.00, 26.00, 20, 40, 80, 62, 'active', 1),
(11, 'MED-BIO-001', 'Biogesic 500mg Tablet', 'biogesic', 8, 16, 3, 'tablet', 0, 6.00, 6.00, 8.00, 7.50, 7.00, 40, 80, 150, 125, 'active', 1),
(12, 'BEV-COK-001', 'Coca-Cola 1.5L', 'coke 1.5', 15, 7, 4, 'bottle', 1, 45.00, 45.00, 65.00, 62.00, 58.00, 30, 60, 120, 88, 'active', 1),
(13, 'CAN-CEN-001', 'Century Tuna Flakes in Oil 180g', 'century tuna', 5, 11, 2, 'can', 1, 48.00, 48.00, 68.00, 65.00, 60.00, 15, 30, 60, 42, 'active', 1),
(14, 'SNK-NOV-001', 'Nova Multigrain Snack 78g', 'nova', 13, 15, 1, 'pack', 1, 10.00, 10.00, 15.00, 14.00, 13.00, 25, 50, 100, 78, 'active', 1),
(15, 'PER-COL-001', 'Colgate Triple Action 160g', 'colgate toothpaste', 3, 13, 3, 'tube', 1, 52.00, 52.00, 75.00, 72.00, 68.00, 10, 20, 40, 28, 'active', 1),
(16, 'NOD-NIS-001', 'Nissin Cup Noodles Beef 60g', 'cup noodles', 6, 5, 2, 'cup', 1, 18.00, 18.00, 25.00, 24.00, 22.00, 30, 60, 120, 95, 'active', 1),
(17, 'BRD-GAR-001', 'Gardenia Classic White Bread 600g', 'gardenia bread', 10, 5, 5, 'loaf', 1, 42.00, 42.00, 55.00, 53.00, 50.00, 20, 40, 80, 58, 'active', 1),
(18, 'CON-UFC-001', 'UFC Banana Catsup 320g', 'ufc catsup', 11, 1, 2, 'bottle', 1, 35.00, 35.00, 48.00, 46.00, 42.00, 12, 24, 50, 32, 'active', 1),
(19, 'CAN-DML-001', 'Del Monte Fruit Cocktail 432g', 'del monte cocktail', 5, 14, 4, 'can', 1, 65.00, 65.00, 92.00, 88.00, 82.00, 10, 20, 40, 28, 'active', 1),
(20, 'BEV-SMA-001', 'San Miguel Pale Pilsen 330ml', 'san mig beer', 2, 6, 6, 'bottle', 1, 32.00, 32.00, 45.00, 43.00, 40.00, 50, 100, 200, 165, 'active', 1);

-- Insert Sample Batches (2 per product)
INSERT INTO batches (batch_id, product_id, po_id, batch_number, qty_received, qty_remaining, delivery_date, mfg_date, expiration_date, cost_price, status) VALUES
(1, 1, NULL, 'C2-2025-001', 300, 250, '2025-01-15', '2025-01-10', '2025-07-10', 20.00, 'active'),
(2, 2, NULL, 'OIS-2025-001', 150, 120, '2025-01-18', '2025-01-15', '2025-10-15', 8.00, 'active'),
(3, 3, NULL, 'LCK-2025-001', 250, 200, '2025-01-20', '2025-01-18', '2025-11-18', 10.00, 'active'),
(4, 4, NULL, 'LIG-2025-001', 100, 95, '2025-02-01', '2024-12-15', '2027-12-15', 30.00, 'active'),
(5, 5, NULL, 'KOP-2025-001', 600, 550, '2025-01-22', '2025-01-20', '2026-01-20', 5.50, 'active'),
(6, 6, NULL, 'REG-2025-001', 100, 85, '2025-01-25', '2025-01-22', '2025-09-22', 7.00, 'active'),
(7, 7, NULL, 'SAF-2025-001', 50, 48, '2025-02-05', '2025-01-30', '2027-01-30', 39.00, 'active'),
(8, 8, NULL, 'BRD-2025-001', 200, 175, '2025-02-10', '2025-02-05', '2026-02-05', 13.00, 'active'),
(9, 9, NULL, 'MIL-2025-001', 300, 285, '2025-02-12', '2025-02-08', '2026-02-08', 8.50, 'active'),
(10, 10, NULL, 'PIA-2025-001', 80, 62, '2025-02-15', '2025-02-10', '2025-08-10', 21.00, 'active');

-- Insert Shelves
INSERT INTO shelves (shelf_id, branch_id, shelf_name, aisle, position, capacity, status) VALUES
(1, 1, 'A1', 'A', 'Front-Left', 200, 'active'),
(2, 1, 'A2', 'A', 'Front-Right', 200, 'active'),
(3, 1, 'B1', 'B', 'Middle-Left', 250, 'active'),
(4, 1, 'B2', 'B', 'Middle-Right', 250, 'active'),
(5, 1, 'C1', 'C', 'Back-Left', 180, 'active'),
(6, 1, 'C2', 'C', 'Back-Right', 180, 'active'),
(7, 1, 'D1', 'D', 'Chilled-Section', 150, 'active'),
(8, 1, 'E1', 'E', 'Frozen-Section', 100, 'active');

-- Insert Sample Customers
INSERT INTO customers (customer_id, full_name, phone, email, birthday, tier, points_balance, total_spend, total_visits, last_visit, status) VALUES
(1, 'Rosa Martinez', '0915-123-4567', 'rosa.m@email.com', '1958-03-15', 'gold', 5500, 55000.00, 250, '2025-03-06', 'active'),
(2, 'Eduardo Santos', '0916-234-5678', 'eduardo.s@email.com', '1992-07-22', 'silver', 1800, 18000.00, 85, '2025-03-05', 'active'),
(3, 'Gloria Reyes', '0917-345-6789', 'gloria.r@email.com', '1975-11-08', 'silver', 2200, 22000.00, 110, '2025-03-06', 'active'),
(4, 'Ramon Cruz', '0918-456-7890', NULL, '1988-04-30', 'bronze', 450, 4500.00, 28, '2025-03-03', 'active'),
(5, 'Cristina Bautista', '0919-567-8901', 'cristina.b@email.com', '1995-09-12', 'bronze', 320, 3200.00, 15, '2025-03-04', 'active');

-- Insert AI Settings
INSERT INTO ai_settings (setting_key, setting_value, description) VALUES
('wma_week1_weight', '0.10', 'WMA weight for oldest week (4 weeks ago)'),
('wma_week2_weight', '0.20', 'WMA weight for 3 weeks ago'),
('wma_week3_weight', '0.30', 'WMA weight for 2 weeks ago'),
('wma_week4_weight', '0.40', 'WMA weight for most recent week'),
('safety_stock_multiplier', '1.5', 'Safety stock multiplier for reorder calculations'),
('max_promo_discount_pct', '50.0', 'Maximum discount percentage for AI-generated promos'),
('dead_stock_days', '60', 'Days without sales to classify as dead stock'),
('forecast_horizon_days', '14', 'Number of days to forecast ahead'),
('confidence_threshold', '70.0', 'Minimum confidence score to show recommendation');

-- Insert System Settings
INSERT INTO system_settings (setting_key, setting_value, setting_group, description) VALUES
('store_name', 'ShopWise Main Branch', 'store', 'Store name displayed on receipts'),
('store_tin', '123-456-789-000', 'store', 'BIR Tax Identification Number'),
('vat_rate', '0.12', 'tax', 'VAT rate (12%)'),
('senior_discount_rate', '0.20', 'tax', 'Senior/PWD discount rate (20%)'),
('pos_idle_lock_minutes', '15', 'pos', 'Auto-lock POS after N minutes idle'),
('shift_float_amount', '5000.00', 'pos', 'Default opening cash per shift');

-- Insert Open Shift for Cashier
INSERT INTO shifts (shift_id, branch_id, cashier_id, shift_label, start_time, opening_cash, status) VALUES
(1, 1, 4, 'Morning', '2025-03-07 08:00:00', 5000.00, 'open');

-- Insert Sample Transactions (completed yesterday)
INSERT INTO transactions (transaction_id, transaction_number, or_number, branch_id, shift_id, cashier_id, customer_type, subtotal, discount_amount, total_amount, payment_method, amount_tendered, change_amount, vatable_sales, vat_amount, status, created_at) VALUES
(1, 'TXN-20250306-0001', 'OR-20250306-0001', 1, 1, 4, 'regular', 150.00, 0.00, 150.00, 'cash', 200.00, 50.00, 133.93, 16.07, 'completed', '2025-03-06 09:15:23'),
(2, 'TXN-20250306-0002', 'OR-20250306-0002', 1, 1, 4, 'senior', 250.00, 50.00, 200.00, 'cash', 200.00, 0.00, 178.57, 21.43, 'completed', '2025-03-06 10:22:15'),
(3, 'TXN-20250306-0003', 'OR-20250306-0003', 1, 1, 4, 'regular', 320.00, 0.00, 320.00, 'gcash', 320.00, 0.00, 285.71, 34.29, 'completed', '2025-03-06 11:08:45'),
(4, 'TXN-20250306-0004', 'OR-20250306-0004', 1, 1, 4, 'regular', 88.00, 0.00, 88.00, 'cash', 100.00, 12.00, 78.57, 9.43, 'completed', '2025-03-06 13:35:12'),
(5, 'TXN-20250306-0005', 'OR-20250306-0005', 1, 1, 4, 'pwd', 180.00, 36.00, 144.00, 'cash', 150.00, 6.00, 128.57, 15.43, 'completed', '2025-03-06 14:50:33');

-- Insert Transaction Items
INSERT INTO transaction_items (transaction_id, product_id, batch_id, quantity, unit_price, cost_price, is_vatable, subtotal) VALUES
(1, 1, 1, 2, 28.00, 20.00, 1, 56.00),
(1, 3, 3, 4, 14.00, 10.00, 1, 56.00),
(1, 5, 5, 5, 8.00, 5.50, 1, 40.00),
(2, 8, 8, 10, 18.00, 13.00, 1, 180.00),
(2, 9, 9, 5, 12.00, 8.50, 1, 60.00),
(3, 7, 7, 4, 55.00, 39.00, 1, 220.00),
(3, 15, NULL, 1, 75.00, 52.00, 1, 75.00),
(4, 2, 2, 3, 12.00, 8.00, 1, 36.00),
(4, 6, 6, 2, 10.00, 7.00, 1, 20.00),
(5, 4, 4, 3, 42.00, 30.00, 1, 126.00),
(5, 13, NULL, 1, 68.00, 48.00, 1, 68.00);

-- Insert Sample Promotions
INSERT INTO promotions (promo_id, promo_name, promo_type, description, discount_pct, start_datetime, end_datetime, applicable_to, status, ai_generated) VALUES
(1, 'Weekend Special - 10% Off Snacks', 'price_discount', 'Get 10% off all snacks every weekend', 10.00, '2025-03-07 00:00:00', '2025-03-31 23:59:59', 'category', 'active', 0),
(2, 'Buy 2 Get 1 Free - Beverages', 'bxgy', 'Buy 2 beverages, get 1 free', NULL, '2025-03-01 00:00:00', '2025-03-31 23:59:59', 'category', 'active', 0),
(3, 'Senior Day - Extra 5% Off', 'price_discount', 'Extra 5% discount for senior citizens every Wednesday', 5.00, '2025-03-01 00:00:00', '2025-12-31 23:59:59', 'all', 'active', 0);

-- Insert Sample AI Recommendations
INSERT INTO ai_recommendations (rec_id, rec_type, product_id, recommendation, reason, confidence_score, urgency, suggested_value, status, generated_at) VALUES
(1, 'restock', 1, 'Reorder 200 units of C2 Apple 500ml', 'Current stock (250) approaching reorder point (100). Based on 14-day sales trend.', 85.50, 'normal', 200.00, 'pending', '2025-03-07 06:00:00'),
(2, 'promotion', 10, 'Create 15% discount promotion for Piattos Cheese 85g', 'Slow-moving item. Only 18 units sold in last 30 days. Average daily sales: 0.6 units.', 72.30, 'normal', 15.00, 'pending', '2025-03-07 06:00:00'),
(3, 'restock', 3, 'URGENT: Reorder 150 units of Lucky Me Pancit Canton', 'Stock level (200) near minimum (40). High sales velocity: 45 units sold last week.', 92.10, 'urgent', 150.00, 'pending', '2025-03-07 06:00:00'),
(4, 'dead_stock', 6, 'Review pricing or promote Regent Cheese Rings', 'No sales recorded in last 60 days. Current stock: 85 units. Consider clearance.', 88.40, 'normal', 20.00, 'pending', '2025-03-07 06:00:00'),
(5, 'promotion', 17, 'Create near-expiry promotion: 25% off Gardenia Bread', 'Batch expires in 9 days. 58 units remaining. Reduce waste with promotion.', 95.20, 'critical', 25.00, 'pending', '2025-03-07 06:00:00');

-- ══════════════════════════════════════════════════════════════════════
-- END OF SCHEMA
-- ══════════════════════════════════════════════════════════════════════

-- Verify tables created
SELECT 'Database schema created successfully!' as status;
SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema = 'shopwise_db';
