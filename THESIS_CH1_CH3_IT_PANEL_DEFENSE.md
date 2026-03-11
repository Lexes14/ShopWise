# ShopWise AI Thesis Content

## Chapter 1: The Problem and Its Background

### 1.1 Introduction
Retail convenience stores often encounter recurring operational problems such as stockouts, overstocking, expiry losses, manual recording errors, and delayed reporting. These issues affect profitability, customer satisfaction, and daily decision-making.

ShopWise AI is a web-based Point-of-Sale (POS) and inventory management system developed to address these problems. It integrates cashier operations, inventory tracking, purchase order workflows, reporting, role-based access control, and algorithm-based recommendation features. The system is designed for Filipino convenience store operations and is implemented using PHP, MySQL, and XAMPP.

### 1.2 Statement of the Problem (SOP)
This study aims to design and develop a system that improves store operations through automation, security, and data-driven support.

Specifically, it seeks to answer the following questions:

1. How can sales transactions be processed faster and more accurately?
2. How can inventory levels and expiry dates be monitored in real time?
3. How can stockouts, dead stock, and near-expiry losses be reduced?
4. How can role-based permissions and audit logs improve data security and accountability?
5. How can automated reports support operational and financial decisions?
6. How can historical transaction data be used to generate actionable recommendations?

### 1.3 Objectives of the Study

#### General Objective
To develop and evaluate a web-based POS and inventory management system with intelligent recommendation support for convenience stores.

#### Specific Objectives
1. To implement a POS module for accurate transaction capture, discounting, tax computation, and receipt generation.
2. To implement inventory control for product management, stock adjustments, stocktake, and expiry batch monitoring.
3. To implement supplier and purchase order workflows for procurement and receiving.
4. To enforce role-based access for Owner, Manager, Inventory Staff, Cashier, Purchasing Officer, Security Personnel, and Bookkeeper.
5. To generate sales, profit, tax, and audit reports.
6. To provide recommendation features for reorder, dead stock, and promotion opportunities.
7. To ensure accountability through audit logs and secure authentication practices.

### 1.4 Significance of the Study
The system is beneficial to the following groups:

1. Store Owners: Provides complete visibility of operations and supports strategic decisions.
2. Managers: Enables faster operational monitoring and improved stock planning.
3. Cashiers: Improves checkout speed and reduces transaction errors.
4. Inventory Staff: Enhances stock accuracy and expiry management.
5. Purchasing Officers: Supports better procurement planning and supplier coordination.
6. Bookkeepers: Produces reliable financial and tax reports.
7. Security Personnel: Strengthens monitoring through audit trail analysis.
8. Future Researchers and Developers: Serves as a reference model for integrated retail systems.

### 1.5 Scope and Delimitation

#### Scope
The study covers the design and implementation of the following modules:

1. Authentication and role-based authorization.
2. POS processing with discount, VAT, and payment handling.
3. Inventory management with batch expiry tracking.
4. Purchase order and receiving workflow.
5. Loyalty points management.
6. Dashboard analytics and reporting.
7. Algorithm-based recommendations.
8. File operations for logs, backups, uploads, and exports.

#### Delimitation
The study is limited to the following boundaries:

1. Deployment is focused on local environment (XAMPP localhost).
2. The solution is web-based and does not include a native mobile app.
3. Recommendation features are algorithmic and do not use external AI APIs.
4. Online payment gateway integration is not included.
5. Enterprise-grade multi-branch orchestration is outside current implementation scope.
6. Large-scale performance benchmarking is not the primary focus.
7. Emphasis is on functional and operational effectiveness for a single-store setup.

---

## Chapter 3: Methodology and System Design

### 3.1 Methodology (Model)
The project follows an Iterative Development with Prototyping model to allow frequent user feedback and incremental improvement.

#### Development Phases
1. Requirements Analysis: Gathered needs for POS, inventory, reporting, security, and user roles.
2. System Design: Prepared architecture, data flow, entity relationships, and module interactions.
3. Prototyping: Built core modules and validated process flow.
4. Full Implementation: Integrated all modules including recommendations and reports.
5. Testing and Validation: Performed functionality and role-based workflow testing.
6. Documentation and Deployment: Prepared system guide and setup documentation.

#### Architectural Pattern
The system follows MVC (Model-View-Controller):

1. Router: Maps request URL to controller action.
2. Controller: Handles logic and processing.
3. Model: Manages database transactions.
4. View: Renders interface and output.

### 3.2 Data Flow Diagram (DFD)

#### Context Diagram (Level 0)
External entities interact with the ShopWise AI system, and the system stores/retrieves data from MySQL.

- Entities: Owner/Manager, Cashier, Inventory Staff, Purchasing Officer, Security, Bookkeeper.
- Inputs: Sales transactions, stock adjustments, PO data, login requests.
- Outputs: Receipts, dashboards, reports, alerts, recommendations.
- Data Store: shopwise_db (MySQL).

#### Level 1 DFD (Major Processes)
Main process decomposition:

1. User Authentication
2. POS and Shift Processing
3. Inventory and Product Management
4. Purchasing and Receiving
5. Reporting and Audit Generation
6. Recommendation Engine

Primary data stores involved:

1. Users/Roles/Sessions
2. POS Transactions and Items
3. Products/Batches/Adjustments
4. Suppliers/PO Tables
5. Audit and Report Sources
6. Recommendation Records

### 3.3 Entity Relationship Diagram (ERD)
Major entities and relationships include:

1. roles to users (one-to-many)
2. users to pos_transactions (one-to-many)
3. pos_transactions to transaction_items (one-to-many)
4. products to transaction_items (one-to-many)
5. products to product_batches (one-to-many)
6. suppliers to purchase_orders (one-to-many)
7. purchase_orders to purchase_order_items (one-to-many)
8. products to purchase_order_items (one-to-many)
9. loyalty_customers to loyalty_points (one-to-many)
10. users to audit_logs (one-to-many)

Key entities:

1. Users and Roles
2. Products and Product Batches
3. POS Transactions and Transaction Items
4. Suppliers and Purchase Orders
5. Loyalty Customers and Points
6. Audit Logs
7. Recommendation Records

### 3.4 Use Case Diagram
Main actors and use cases:

#### Actors
1. Owner
2. Manager
3. Cashier
4. Inventory Staff
5. Purchasing Officer
6. Security Personnel
7. Bookkeeper

#### Core Use Cases
1. Login/Authentication
2. Manage Users and Settings
3. Process POS Sales
4. Open and Close Shift
5. Manage Products and Stock
6. Perform Stocktake and Adjustments
7. Create/Approve/Receive Purchase Orders
8. Generate Reports
9. View Audit Logs
10. View Recommendations
11. Manage Loyalty Operations

---

## IT Panel Defense: Possible Questions and Suggested Answers

### A. System and Architecture

1. What is the main purpose of ShopWise AI?
Suggested Answer:
ShopWise AI is designed to integrate POS, inventory, reporting, and recommendation support in one platform to reduce manual errors, improve stock visibility, and accelerate retail decision-making.

2. What architecture does your system use?
Suggested Answer:
The system uses MVC architecture. Routing is handled by a router layer, controllers process business logic, models perform database operations, and views handle presentation.

3. Why did you choose PHP and MySQL?
Suggested Answer:
PHP and MySQL are practical, stable, and cost-efficient technologies for local business systems. They are easy to deploy and maintain in SME environments.

4. How does the request flow work in your application?
Suggested Answer:
A request is routed to a controller action, validated for authentication and authorization, processed through model/database operations, and returned through the appropriate view.

### B. Security and Access Control

5. How do you secure user passwords?
Suggested Answer:
Passwords are hashed using bcrypt via password_hash and verified with password_verify, so plain-text passwords are never stored.

6. How do you enforce role-based access?
Suggested Answer:
Roles and permissions are mapped in configuration and checked through guard logic before allowing module access.

7. How do you prevent SQL injection?
Suggested Answer:
All DB operations use PDO prepared statements with parameterized queries.

8. How do you provide accountability in the system?
Suggested Answer:
The system records audit logs including user actions, timestamps, and affected records for traceability.

### C. Functional Workflows

9. Explain your POS process flow.
Suggested Answer:
Cashier logs in, opens shift, adds products, applies discounts, computes VAT, records payment, saves transaction and items, updates stock, updates loyalty, and generates a receipt.

10. How is inventory updated?
Suggested Answer:
Inventory changes through sales deduction, PO receiving, stock adjustments, and stocktake reconciliation, all tracked with logs for consistency.

11. How do you handle expiry management?
Suggested Answer:
Products are tracked by batches with expiry dates. The system flags near-expiry items to support FEFO and promotion decisions.

12. What reports can your system generate?
Suggested Answer:
Sales, profit, customer spending, tax summaries, inventory status, and audit reports, depending on role permissions.

### D. AI/Recommendation Logic

13. Why do you call it AI if there is no external AI API?
Suggested Answer:
The system applies intelligent, data-driven recommendation algorithms using historical sales and stock patterns. It is explainable and practical for local deployments.

14. How are reorder suggestions computed?
Suggested Answer:
Using weighted moving average of recent sales with a safety buffer to estimate required replenishment.

15. How are dead-stock alerts generated?
Suggested Answer:
By checking products with long inactivity in sales, then flagging them for review, markdown, or discontinuation.

16. How are promo suggestions generated?
Suggested Answer:
By combining product movement velocity, expiry proximity, and margin constraints with configurable discount limits.

### E. Testing, Limitations, and Improvement

17. How did you test your system?
Suggested Answer:
We performed functional and role-based scenario testing for login, POS, inventory, purchasing, reporting, and recommendation outputs, including edge cases.

18. What are your system limitations?
Suggested Answer:
Current implementation is focused on local setup, no native mobile app, no online payment gateway, and no external ML services.

19. What improvements would you propose for future work?
Suggested Answer:
Add automated testing, cloud deployment options, background job processing, stronger monitoring, and advanced forecasting models.

20. What is the business value of your project?
Suggested Answer:
It reduces transaction and inventory errors, improves stock planning, supports financial visibility, and strengthens control through audit and role security.

---

## Optional Oral Defense Closing Statement
ShopWise AI demonstrates how an integrated information system can improve retail operations by combining transaction accuracy, inventory control, security, and decision support. It is practical for real-world convenience store workflows and provides a scalable foundation for future enhancements.
