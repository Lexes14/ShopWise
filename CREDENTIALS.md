# ╔══════════════════════════════════════════════════════════════════════╗
# ║              SHOPWISE AI — TEST LOGIN CREDENTIALS                   ║
# ║                   ALL ROLES & DEFAULT PASSWORDS                     ║
# ╚══════════════════════════════════════════════════════════════════════╝

⚠️  IMPORTANT: Change all passwords immediately after first login!
✅ **VERIFIED & WORKING** — Database updated on March 8, 2026

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

## 🔐 LOGIN CREDENTIALS BY ROLE (VERIFIED WORKING)

### 1. OWNER (Full System Access)
   **Username:** owner
   **Password:** Admin@2025
   **Full Name:** Juan Dela Cruz
   **Access:** Complete system control - all modules, settings, backups, user management
   **Login URL:** http://localhost/ShopWise_ai/login
   **Redirect After Login:** /dashboard

### 2. MANAGER (Store Manager)
   **Username:** manager
   **Password:** Manager@2025
   **Full Name:** Maria Santos
   **Access:** Full operational access except user management and backup restore
   **Login URL:** http://localhost/ShopWise_ai/login
   **Redirect After Login:** /dashboard

### 3. INVENTORY STAFF
   **Username:** inventory
   **Password:** Staff@2025
   **Full Name:** Pedro Reyes
   **Access:** Products, inventory, stock adjustments, stocktake, receive deliveries
   **Login URL:** http://localhost/ShopWise_ai/login
   **Redirect After Login:** /dashboard

### 4. CASHIER (POS Terminal)
   **Username:** cashier
   **Password:** Cashier@2025
   **6-Digit PIN:** 123456
   **Full Name:** Anna Garcia
   **Access:** POS terminal only, shift management, loyalty lookup
   **Login URL (Password):** http://localhost/ShopWise_ai/login
   **Login URL (PIN):** http://localhost/ShopWise_ai/pos/terminal
   **Redirect After Login:** /pos/terminal

### 5. PURCHASING OFFICER
   **Username:** purchasing
   **Password:** Purchase@2025
   **Full Name:** Roberto Cruz
   **Access:** Suppliers, purchase orders, deliveries, AI reorder suggestions
   **Login URL:** http://localhost/ShopWise_ai/login
   **Redirect After Login:** /dashboard

### 6. SECURITY PERSONNEL
   **Username:** security
   **Password:** Security@2025
   **Full Name:** Jose Ramos
   **Access:** View-only dashboard, incident reports, audit logs (read-only)
   **Login URL:** http://localhost/ShopWise_ai/login
   **Redirect After Login:** /dashboard

### 7. BOOKKEEPER / ACCOUNTANT
   **Username:** bookkeeper
   **Password:** Accounts@2025
   **Full Name:** Lydia Mendoza
   **Access:** Financial reports, profit analysis, audit logs, sales reports
   **Login URL:** http://localhost/ShopWise_ai/login
   **Redirect After Login:** /reports

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

## 📊 SAMPLE DATA INCLUDED

✅ **1 Active Branch:** ShopWise Main Branch (Parañaque City)
✅ **7 Test Users:** One per role (see credentials above)
✅ **15 Product Categories:** Snacks, Beverages, Personal Care, etc.
✅ **20 Brands:** Nestlé, Unilever, Oishi, Lucky Me, etc.
✅ **6 Suppliers:** Complete with contact info and payment terms
✅ **20 Sample Products:** Filipino convenience store items with realistic pricing
   - C2 Apple 500ml (₱28)
   - Lucky Me Pancit Canton (₱14)
   - Kopiko Coffee 3-in-1 (₱8)
   - Ligo Sardines (₱42)
   - Safeguard Soap (₱55)
   - Bear Brand Milk (₱18)
   - And 14 more...

✅ **10 Product Batches:** FEFO-tracked with expiry dates
✅ **5 Loyalty Customers:** With points balance and purchase history
✅ **1 Open Shift:** Morning shift for cashier (₱5,000 opening cash)
✅ **5 Sample Transactions:** Completed yesterday (March 6, 2025)
✅ **3 Active Promotions:** Weekend specials, BXGY, Senior discounts
✅ **5 AI Recommendations:** Restock alerts, dead stock, near-expiry promos
✅ **8 Store Shelves:** Organized by aisle (A-E)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

## 🚀 QUICK START GUIDE

### **Step 1: Import Database**
```bash
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Database already created: shopwise_db
3. Import file: database/schema.sql
4. Verify: Should see 50+ tables
```

### **Step 2: Test Owner Login**
```
URL: http://localhost/ShopWise_ai/login
Username: owner
Password: Admin@2025
```

### **Step 3: Test POS Terminal (Cashier)**
```
URL: http://localhost/ShopWise_ai/pos/terminal
Username: cashier
Password: Cashier@2025
(Or use PIN: 123456)
```

### **Step 4: Explore Dashboard**
- View today's KPIs
- Check AI recommendations
- Browse product catalog
- Review open shift

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

## 🔒 SECURITY NOTES

1. **Change ALL passwords** after first login via Settings > Users
2. **Cashier PIN** can be updated per user in User Management
3. **Account Lockout:** 5 failed attempts = 15-minute lockout
4. **Session Timeout:** 2 hours of inactivity
5. **CSRF Protection:** Enabled on all POST forms
6. **Password Requirements:** 
   - Minimum 8 characters
   - Must contain uppercase, lowercase, and numbers

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

## 📱 TESTING CHECKLIST

### Owner/Manager:
- [ ] Login to dashboard
- [ ] View sales KPIs
- [ ] Browse products
- [ ] Check inventory levels
- [ ] Review AI recommendations
- [ ] Access all reports
- [ ] Manage users (owner only)

### Cashier:
- [ ] PIN login to POS terminal
- [ ] Add products to cart
- [ ] Process cash transaction
- [ ] Process senior citizen discount
- [ ] Hold transaction
- [ ] Close shift with cash reconciliation

### Inventory Staff:
- [ ] Submit stock adjustment
- [ ] Create stocktake session
- [ ] Receive delivery from PO
- [ ] View expiring products

### Purchasing Officer:
- [ ] View suppliers
- [ ] Create purchase order
- [ ] View AI reorder suggestions
- [ ] Approve PO

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

## 🆘 TROUBLESHOOTING

**Login not working?**
- Verify database imported successfully
- Check config/database.php credentials
- Ensure Apache and MySQL are running

**Permission denied errors?**
- Check folder permissions: uploads/, backups/, logs/
- Windows: Ensure XAMPP has write access
- Should be chmod 755 or 777 for testing

**POS terminal blank?**
- Ensure shift is open (sample shift ID: 1)
- Check browser console for JS errors
- Verify products have stock > 0

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

**System Version:** ShopWise AI v5.0
**Generated:** March 7, 2025
**Environment:** Development (XAMPP localhost)
**Database:** MySQL 8.0 · shopwise_db · utf8mb4_unicode_ci

**Support Email:** support@shopwise.ph (sample - not real)
**Documentation:** README.md

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
