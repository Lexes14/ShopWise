# 📚 ShopWise AI — Complete System Guide

**Version:** 5.0  
**Last Updated:** March 8, 2026  
**Environment:** Development (XAMPP)

---

## 📖 Table of Contents

1. [System Overview](#system-overview)
2. [Getting Started](#getting-started)
3. [User Roles & Access](#user-roles--access)
4. [Core Features](#core-features)
5. [How It Works](#how-it-works)
6. [Data Flow](#data-flow)
7. [Database Structure](#database-structure)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 System Overview

**ShopWise AI** is a comprehensive point-of-sale (POS) and inventory management system designed for Filipino convenience stores. It combines real-time sales tracking, intelligent stock management, and AI-powered business insights.

### Key Capabilities

✅ **POS Terminal** — Process transactions in-store with discounts, loyalty, and tax calculations  
✅ **Inventory Management** — Track stock levels, expiry dates, and receive deliveries  
✅ **AI Insights** — Get reorder suggestions, dead stock alerts, and promotion recommendations  
✅ **Financial Reporting** — Generate sales, profit, and audit reports  
✅ **Multi-User Support** — 7 dashboard roles with granular permissions  
✅ **Loyalty Program** — Manage customer points and tier benefits  
✅ **Audit Trail** — Complete logging of all user actions for compliance  

---

## 🚀 Getting Started

### Prerequisites

- **Server:** Apache (XAMPP)
- **Database:** MySQL 8.0
- **PHP:** 7.4+ with PDO extension
- **Browser:** Modern browser (Chrome, Firefox, Edge)

### Initial Setup

1. **Ensure XAMPP is Running**
   ```bash
   # Windows — Start Apache & MySQL from XAMPP Control Panel
   Apache → Start
   MySQL → Start
   ```

2. **Import Database**
   - Open: `http://localhost/phpmyadmin`
   - Create/Select: `shopwise_db`
   - Import: `database/schema.sql`
   - Verify: 50+ tables created

3. **Test Login**
   - URL: `http://localhost/ShopWise_ai/login`
   - Username: `owner`
   - Password: `Admin@2025`

4. **Change Default Passwords**
   - Settings → User Management
   - Update each user's password
   - Set new Cashier PIN if needed

---

## 👥 User Roles & Access

The system has **7 predefined roles**, each with specific permissions:

### 1️⃣ OWNER (Full System Access)
- **Will See:** Dashboard, all modules, user management, backups, system settings
- **Can Do:** Create/edit/delete users, restore backups, access all reports, change system settings
- **Login:** Username `owner` / Password `Admin@2025`
- **Redirect:** `/dashboard`

### 2️⃣ MANAGER (Store Manager)
- **Will See:** Dashboard, operations, reports (not user management)
- **Can Do:** Manage inventory, approve POs, view all reports, manage promotions
- **Cannot Do:** Manage users, restore backups
- **Login:** Username `manager` / Password `Manager@2025`
- **Redirect:** `/dashboard`

### 3️⃣ INVENTORY STAFF
- **Will See:** Products, inventory, stock adjustments, stocktake, deliveries
- **Can Do:** Receive deliveries, adjust stock, run stocktake, view expiring items
- **Cannot Do:** Access POS, manage users, view finances
- **Login:** Username `inventory` / Password `Staff@2025`
- **Redirect:** `/dashboard`

### 4️⃣ CASHIER (POS Terminal)
- **Will See:** POS terminal only, shift management, loyalty lookup
- **Can Do:** Ring sales, apply discounts, manage loyalty, open/close shifts, reconcile cash
- **Cannot Do:** Manage products, inventory, or system settings
- **PIN Login:** `123456` (for quick authentication)
- **Password Login:** Username `cashier` / Password `Cashier@2025`
- **Redirect:** `/pos/terminal`

### 5️⃣ PURCHASING OFFICER
- **Will See:** Suppliers, purchase orders, deliveries, AI suggestions
- **Can Do:** Create/edit purchase orders, approve orders, manage suppliers, track deliveries
- **Cannot Do:** Process POS sales, adjust inventory
- **Login:** Username `purchasing` / Password `Purchase@2025`
- **Redirect:** `/dashboard`

### 6️⃣ SECURITY PERSONNEL
- **Will See:** Dashboard (read-only), audit logs, incident reports
- **Can Do:** View audit logs, run security reports, monitor user activity
- **Cannot Do:** Modify data, manage inventory, process sales
- **Login:** Username `security` / Password `Security@2025`
- **Redirect:** `/dashboard`

### 7️⃣ BOOKKEEPER / ACCOUNTANT
- **Will See:** Financial reports, profit analysis, audit logs, tax compliance
- **Can Do:** Generate financial reports, view sales trends, audit compliance
- **Cannot Do:** Modify transactions, manage users, edit settings
- **Login:** Username `bookkeeper` / Password `Accounts@2025`
- **Redirect:** `/reports`

---

## 🔧 Core Features Explained

### 1. Dashboard
**What It Shows:**
- Today's KPIs (sales, transactions, unique customers)
- Top 5 products by revenue
- Stock status summary
- AI recommendations widget
- Recent transactions

**Where Data Comes From:**
- Real-time transactions table (`pos_transactions`)
- Product inventory table (`products`)
- AI analysis of sales history

---

### 2. POS Terminal (Cashier Feature)
**How It Works:**
1. Cashier logs in (username/password OR PIN)
2. Shift opens with opening cash
3. Products added to cart by scanning or searching
4. Discounts applied (quantity, coupon, loyalty, senior/PWD)
5. Payment processed (cash/check)
6. Transaction saved, receipt printed
7. Shift closes with cash reconciliation

**Data Flow:**
- **Input:** Product codes, quantities, customer info
- **Processing:** Tax calculation, discount logic, loyalty points
- **Storage:** `pos_transactions`, `transaction_items`, `transaction_discounts`, `loyalty_points`
- **Output:** Receipt (print/PDF), shift report

---

### 3. Inventory Management
**Modules:**
- **Products:** Browse, create, edit, set pricing & stock levels
- **Stock Adjustment:** Manual quantity corrections (receiving, loss, adjustment)
- **Stocktake:** Physical count validation against system
- **Aging Report:** Products not sold in N days (dead stock)
- **Expiring Items:** Products near expiry date (FEFO — First Expired, First Out)

**Data Storage:**
- `products` → Master product data
- `product_batches` → Individual batch tracking with expiry
- `inventory_adjustments` → Audit trail of changes
- `stocktake_sessions` → Physical count records

---

### 4. Purchase Orders (Purchasing Officer)
**Workflow:**
1. Create PO with supplier & products
2. Manager approves
3. PO sent to supplier
4. Delivery received → Stock updated automatically
5. Invoice matched & payment processed

**Data Storage:**
- `purchase_orders` → Order header
- `purchase_order_items` → Line items with qty & cost
- `purchase_order_received` → Received quantity tracking

---

### 5. AI Insights
**Three Types of Recommendations:**

#### A. Reorder Suggestions
- **What:** Products approaching minimum stock
- **How:** WMA (Weighted Moving Average) of past 4 weeks
- **Safety Buffer:** 1.5x average weekly usage
- **Data Source:** `pos_transaction_items` (historical sales)

#### B. Dead Stock Alerts
- **What:** Products not sold in 60+ days
- **How:** Last sale date comparison
- **Action:** Consider discontinue or promotion
- **Data Source:** `products`, `pos_transactions`

#### C. Promo Recommendations
- **What:** Near-expiry items or slow-moving stock
- **How:** Cost + margin analysis
- **Max Discount:** 30% (configurable)
- **Data Source:** `product_batches` (expiry), `pos_transactions` (velocity)

**Where AI Logic Runs:**
- Backend: [AIController.php](controllers/AIController.php)
- Calculation: PHP algorithms (no external API)
- Frequency:** Runs on dashboard load, cached for 6 hours

---

### 6. Loyalty Program
**How Points Work:**
- **Earn:** ₱10 spent = 1 point
- **Redeem:** 50 points = ₱25 off (configurable)
- **Tiers:** Silver (1,000 pts), Gold (5,000 pts), Platinum (10,000 pts)

**Data Storage:**
- `loyalty_customers` → Customer master
- `loyalty_points` → Point ledger (earn/redeem history)
- `loyalty_tiers` → Tier benefits

---

### 7. Reporting
**Available Reports:**

| Report | Who Sees | Data Source |
|--------|----------|-------------|
| Sales by Hour | Manager, Owner | `pos_transactions` |
| Profit Analysis | Bookkeeper, Owner | `pos_transactions` + `products` (cost) |
| Top Products | Manager, Owner | `transaction_items` grouped |
| Customer Spending | Manager, Owner | `pos_transactions` aggregated |
| Inventory Value | Owner | `products` (qty × cost) |
| Audit Log | Security, Owner | `audit_logs` |
| Tax Summary | Bookkeeper, Owner | `pos_transactions` (VAT calculation) |

---

## 🔄 How It Works

### Application Flow

```
User Request
    ↓
Router (core/Router.php) → Matches URL to Controller
    ↓
Controller (controllers/*.php) → Business Logic
    ↓
Model (models/*.php) → Database Query
    ↓
View (views/*.php) → HTML Response to Browser
```

### Authentication Flow

```
Login Page
    ↓
AuthController::login() → Validates credentials
    ↓
Auth::login() → password_verify() against password_hash
    ↓
Session Created → Redirect to Dashboard
    ↓
Auth::guard() → Check role permissions on each page
```

### POS Transaction Flow

```
Add Product to Cart
    ↓
Apply Discounts (loyalty, coupon, senior)
    ↓
Calculate Tax (VAT 12% or exempt)
    ↓
Process Payment
    ↓
INSERT pos_transactions + transaction_items
    ↓
UPDATE product stock (current_stock - qty)
    ↓
UPDATE loyalty_points (if customer enrolled)
    ↓
Generate & Print Receipt
```

---

## 💾 Data Flow

### Where Data Comes From

| Data | Source | Frequency |
|------|--------|-----------|
| **Products** | Manual entry or bulk import | Per creation/edit |
| **Suppliers** | Manual entry | Per creation/edit |
| **Sales** | POS terminal (cashier input) | Real-time |
| **Stock** | Inventory adjustments + PO receival | Real-time |
| **Customers** | Loyalty signup at POS | Per signup |
| **Settings** | Admin configuration | Per change |

### Where Data Is Stored

**All Data Lives in:** `shopwise_db` (MySQL 8.0)

**Key Tables:**

| Data Type | Tables | Notes |
|-----------|--------|-------|
| **Users & Auth** | `users`, `roles`, `user_sessions`, `login_logs` | Encrypted passwords (bcrypt) |
| **Products** | `products`, `categories`, `brands`, `product_images`, `product_batches` | Prices, stock, expiry dates |
| **Suppliers** | `suppliers` | Contact, payment terms, credit limit |
| **Inventory** | `inventory_adjustments`, `stocktake_sessions`, `product_batches` | Stock movements, expiry tracking |
| **POS Sales** | `pos_transactions`, `transaction_items`, `transaction_discounts` | Real-time transaction log |
| **Loyalty** | `loyalty_customers`, `loyalty_points` | Points earned/redeemed |
| **Reports** | `audit_logs`, `price_history` | System activity log |

### Data Flow to Filesystem

| What | Where | Format |
|------|-------|--------|
| **Product Images** | `uploads/products/` | JPG, PNG, WebP (max 5MB) |
| **Supplier Logos** | `uploads/suppliers/` | JPG, PNG |
| **User Avatars** | `uploads/users/` | JPG, PNG |
| **Logs** | `logs/` | `.log` files (daily rotation) |
| **Backups** | `backups/` | SQL dump files |
| **Exports** | `exports/` | CSV, PDF reports |

---

## 🗄️ Database Structure

### Schema Overview

```
shopwise_db/
├── Users & Access (7 tables)
│   ├── roles
│   ├── users
│   ├── user_sessions
│   └── login_logs
├── Store Config (2 tables)
│   ├── branches
│   └── system_settings
├── Product Catalog (7 tables)
│   ├── categories
│   ├── brands
│   ├── products
│   ├── product_images
│   ├── product_batches
│   └── price_history
├── Suppliers (1 table)
│   └── suppliers
├── Inventory (4 tables)
│   ├── purchase_orders
│   ├── purchase_order_items
│   └── inventory_adjustments
├── POS Sales (3 tables)
│   ├── pos_transactions
│   ├── transaction_items
│   └── transaction_discounts
├── Loyalty (2 tables)
│   ├── loyalty_customers
│   └── loyalty_points
├── Shifts (3 tables)
│   ├── shifts
│   ├── shift_cash_readings
│   └── shift_closeout
├── Reporting (3 tables)
│   ├── audit_logs
│   ├── promotion_performance
│   └── ai_recommendations
└── Miscellaneous
    └── system_settings
```

### Key Relationships

```
users → roles (each user has 1 role)
    ↓
users → pos_transactions (cashier creates transactions)
    ↓
pos_transactions → transaction_items (multiple products per transaction)
    ↓
transaction_items → products (product master data)
         ↓
      product_batches (unique batch with expiry)

suppliers → products (linked via primary/secondary supplier)
         ↓
purchase_orders → purchase_order_items (PO lines)
              ↓
         products (received qty updates stock)
```

---

## 🔐 Security Features

### Password Protection
- **Algorithm:** BCrypt (cost=12)
- **Hashing:** One-way, salted, collision-resistant
- **Verification:** `password_verify()` — plain text never stored

### Session Management
- **Timeout:** 2 hours of inactivity
- **Regeneration:** Session ID changed on login
- **CSRF Protection:** Token-based form validation

### Audit Trail
- Every user action logged: `audit_logs` table
- Timestamps, user ID, action, affected record
- Compliance with data protection regulations

### Database Security
- **Connections:** PDO prepared statements (SQL injection prevention)
- **Charset:** UTF-8MB4 (safe for international characters)
- **Backups:** Encrypted backup files in `backups/` folder

---

## 📊 Sample Data

The schema includes **realistic sample data** for immediate testing:

- **7 Users** — One per role (see CREDENTIALS.md)
- **20 Products** — Filipino convenience store items with pricing
- **5 Suppliers** — With contact info and payment terms
- **1 Branch** — ShopWise Main (Parañaque City)
- **10 Product Batches** — FEFO-tracked with expiry dates
- **5 Loyalty Customers** — With points and purchase history
- **3 Active Promotions** — Weekend specials, BXGY, discounts

---

## 🛠️ Configuration

### Key Files

| File | Purpose | Edit When |
|------|---------|-----------|
| `config/app.php` | App constants (store name, currency, settings) | Store info changes |
| `config/database.php` | DB connection (host, user, password) | Moving DB to new server |
| `config/permissions.php` | Role-based access control | Adding new features |
| `.env` (if present) | Environment-specific settings | Dev/staging/production |

### Example Config Changes

**Change Store Name:**
```php
// config/app.php
define('STORE_NAME', 'Your Store Name Here');
define('STORE_ADDRESS', 'Your Address');
define('STORE_PHONE', '(02) 1234-5678');
```

**Change VAT Rate (if needed):**
```php
// config/app.php
define('VAT_RATE', 0.12);  // 12% for Philippines
// Change to 0.08 for 8% VAT in other jurisdictions
```

**Change Loyalty Rate:**
```php
// config/app.php
define('LOYALTY_POINTS_PER_PESO', 0.10);  // ₱10 = 1 point
define('LOYALTY_PESO_PER_POINT', 0.50);   // 1 point = ₱0.50
```

---

## 📱 Typical User Workflows

### Manager Workflow

1. **Morning:** Login → Check dashboard KPIs
2. **Mid-Day:** Review inventory alerts, approve low-stock POs
3. **Afternoon:** Check promotions performance, adjust pricing if needed
4. **Evening:** Review shift reports, reconcile cash
5. **Night:** Export daily sales report

### Cashier Workflow

1. **Start of Shift:** Open shift with opening cash (₱1,000 default)
2. **During:** Ring sales, apply discounts, accept loyalty
3. **Periodic:** Check drawer accuracy
4. **End of Shift:** Close shift, reconcile cash, print closeout report

### Inventory Staff Workflow

1. **Receiving:** Scan delivery, receive into system
2. **Stock Check:** Run stocktake, compare to physical count
3. **Adjustments:** Log losses, damage, or corrections
4. **Monitoring:** Check expiry report, flag near-expiry items for promo

### Bookkeeper Workflow

1. **Daily:** Export sales summary
2. **Weekly:** Generate profit report
3. **Monthly:** Tax compliancecheck, revenue analysis
4. **Quarter/Year-End:** Audit log review, reconciliation

---

## ❓ Troubleshooting

### Login Issues

**Problem:** "Invalid username or password"
- **Check:** Username and password match CREDENTIALS.md
- **Check:** Account not locked (5 failed attempts = 15 min lockout)
- **Fix:** Database admin can reset password via SQL

**Problem:** "Session expired"
- **Cause:** 2-hour inactivity timeout
- **Fix:** Login again

---

### POS Terminal Blank

**Problem:** Page loads but no products shown
- **Check:** Products exist in database (Products module)
- **Check:** At least 1 product has `current_stock > 0`
- **Fix:** Add products via Products → Create

---

### Database Connection Error

**Problem:** "Database connection unavailable"
- **Check:** MySQL running (XAMPP Control Panel)
- **Check:** `config/database.php` has correct host/user/password
- **Fix:** Verify MySQL credentials

---

### Permissions Denied (Folder)

**Problem:** "Permission denied" on upload/logs
- **Cause:** XAMPP doesn't have write access
- **Fix (Windows):** Right-click folder → Properties → Security → Edit → Full Control

---

### AI Recommendations Not Showing

**Problem:** AI insights widget is empty
- **Cause:** Need 2+ weeks of sales history to calculate trends
- **Fix:** Run sample transactions first, check tomorrow

---

## 📞 Support

**For Issues:**
1. Check `logs/` folder for error messages
2. Review `CREDENTIALS.md` for login help
3. Consult this guide's relevant section

**For Development:**
- Backend changes: Edit `controllers/`, `models/`, `core/`
- Frontend changes: Edit `views/`, `assets/`
- Database changes: Update `database/schema.sql`

---

**Created:** March 8, 2026  
**System:** ShopWise AI v5.0  
**Environment:** Development (XAMPP localhost)

