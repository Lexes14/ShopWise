# ShopWise AI (PHP 8.1 + MySQL 8.0)

ShopWise AI is a modular convenience store management system with POS, inventory, suppliers, purchasing, loyalty, AI insights, reports, audit logs, and settings.

## Current Build Status

This workspace now includes:

- Core MVC bootstrapping and router
- Database schema + sample data (`database/schema.sql`)
- Authentication module (password + cashier PIN)
- Dashboard baseline with KPIs
- Full controller coverage for all registered routes
- Transactional business logic for POS checkout/void, shifts, inventory adjustments/stocktake, and PO receiving
- CRUD persistence for products, suppliers, promotions, loyalty customers, users, and settings
- Real reporting queries, audit detail/export, and AI recommendation workflow actions
- CSS design system (`assets/css/app.css`, `assets/css/pos.css`)
- JavaScript assets (`assets/js/app.js`, `pos.js`, `charts.js`, `live-search.js`, `denomination.js`)

## Requirements

- PHP 8.1+
- MySQL 8.0+
- XAMPP (Apache + MySQL)

## Setup

1. Place project in `C:/xamppNew/htdocs/ShopWise_ai`
2. Start Apache and MySQL in XAMPP
3. Import `database/schema.sql` into MySQL
4. Open `http://localhost/ShopWise_ai/login`

## Default Credentials

See `CREDENTIALS.md` for all 7 roles.

## Routes

All routes are defined in `core/Router.php` and now have corresponding controller methods.

## Notes

- This build provides complete module routing and scaffold flows.
- CRUD write logic for selected modules is scaffolded and can be expanded with business rules in the next pass.
