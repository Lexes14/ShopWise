# ShopWise AI Defense Guide (3 Members)
**Date:** March 11, 2026  
**Use for:** Final defense presentation to panelists

---

## 1. Presentation Flow (Systematic, End-to-End)

### Total Suggested Time: 25-30 minutes
1. Member 1: 8-10 minutes
2. Member 2: 8-10 minutes
3. Member 3: 8-10 minutes
4. Q&A: 10-20 minutes

### Master Flow
1. Opening and team introduction
2. Chapter 1 (Problem, SOP, Objectives, Significance, Scope/Delimitation)
3. Chapter 3 (Methodology, DFD, ERD, Use Case Diagram)
4. System architecture and role-based access
5. End-to-end module walkthrough
6. Key implemented features and updates
7. Security and data integrity controls
8. Limitations and future enhancements
9. Closing statement
10. Panel Q&A

---

## 2. Division of Parts (3 Members)

## Member 1: Business Context + Chapter 1 + System Overview
### Coverage
1. Introduction and business problem
2. Statement of the Problem (SOP)
3. Objectives (general + specific)
4. Significance of the study
5. Scope and delimitation
6. High-level overview of modules and user roles

### Speaker Script (Member 1)
Good day, panelists. We are presenting ShopWise AI, a POS and inventory management system for convenience stores.

The core problem we addressed is that many stores still struggle with manual tracking, delayed reporting, stockouts, overstocking, and expiry losses. These issues affect both operations and profitability.

Our study asks six key questions: how to process sales accurately, monitor inventory in real time, reduce stock-related losses, improve access control and accountability, support reporting decisions, and use historical data for recommendations.

Our general objective is to develop and evaluate a web-based POS and inventory system with intelligent recommendation support. Specifically, we implemented transaction processing, inventory and stocktake workflows, supplier and purchase order workflows, role-based access for seven roles, reporting, recommendation features, and audit tracking.

This system is significant for owners, managers, cashiers, inventory staff, purchasing officers, bookkeepers, and security personnel because each role gets relevant features and controlled access.

For scope, we cover authentication, POS, inventory, purchasing, loyalty, reporting, recommendations, and backup/log operations. For delimitation, the system is web-based, local deployment oriented, and does not include online payment gateway integration or external AI APIs.

I will now hand over to Member 2 for methodology and technical design.

---

## Member 2: Chapter 3 + Technical Design (DFD/ERD/Use Case + Architecture/Security)
### Coverage
1. Methodology model
2. DFD (context + level 1)
3. ERD and key relationships
4. Use Case diagram (actors and core use cases)
5. MVC architecture and route-controller-model-view flow
6. Security design and role-based authorization

### Speaker Script (Member 2)
Thank you. For development, we used an Iterative with Prototyping methodology so we could validate workflows and improve modules in cycles.

In our DFD context level, seven external actors interact with the ShopWise AI system. Inputs include sales, stock adjustments, purchase orders, and login requests. Outputs include receipts, dashboard KPIs, reports, and recommendations. All core data is stored in MySQL.

In Level 1 DFD, we decomposed the system into six major processes: authentication, POS and shifts, inventory/product management, purchasing/receiving, reporting/audit, and recommendation engine.

For ERD, key relationships include:
- roles to users
- users to pos_transactions
- pos_transactions to transaction_items
- products to product_batches
- suppliers to purchase_orders
- purchase_orders to purchase_order_items
- loyalty_customers to loyalty_points
- users to audit_logs

Our Use Case diagram includes seven actors: Owner, Manager, Cashier, Inventory Staff, Purchasing Officer, Security Personnel, and Bookkeeper. Core use cases include login, POS sales, shifts, inventory and stocktake, purchase orders, reports, audit logs, recommendations, and loyalty.

Architecturally, we use MVC: router maps URL to controller, controller applies business logic, model handles database operations, and views render UI.

For security, we apply password hashing with bcrypt verification, session handling, permission checks per role, prepared statements for SQL injection prevention, and audit logs for accountability.

I will now hand over to Member 3 for module walkthrough, results, and defense readiness.

---

## Member 3: Full Feature Walkthrough + Results + Limitations + Closing
### Coverage
1. POS workflow and cashier operations
2. Inventory workflow (adjustments, stocktake, expiry, shelves)
3. Purchasing and supplier management
4. Reports and exports
5. AI recommendation features and actions
6. Backup, notifications, settings, audit
7. Limitations and future enhancements
8. Closing statement

### Speaker Script (Member 3)
Thank you. I will now present the implemented modules and end-to-end flow.

In POS, cashier can search products, process checkout, apply discounts and loyalty, verify PIN, hold/recall cart, void transactions, and reprint receipts. Shift operations include open, close, history, and detail verification.

In inventory, users can manage products, submit stock adjustments, and run stocktake sessions with counting and finalize flow. We also support expiring and aging views and shelf-based visibility.

For purchasing, we support supplier management and purchase order lifecycle: create, add item, submit, approve/reject, mark ordered, and mark received.

For analytics and control, we provide reports such as sales, customer transactions, profit, inventory, cashier and supplier reports, including CSV/Excel exports. Audit logs capture critical user actions.

For AI insights, we support recommendation generation and action workflows such as accept, dismiss, and feedback, with dedicated views for demand, pricing, stock, bundling, anomalies, and customer segments.

Additional operational modules include notifications, system settings, and backup create/download/restore.

Limitations include local deployment focus, no native mobile app, and no external AI API integration. Future improvements include cloud deployment, automated testing, and enhanced forecasting.

In summary, ShopWise AI integrates operations, controls, and decision support in one role-based platform designed for real retail workflows.

Thank you. We are now ready for your questions.

---

## 3. Transition Lines (Use Between Members)

1. Member 1 to Member 2  
Now that we established the business context and study foundations, Member 2 will discuss our methodology and technical system design.

2. Member 2 to Member 3  
With the technical architecture and diagrams explained, Member 3 will now present the implemented modules and real workflow execution.

---

## 4. Panelist Questions and Suggested Answers

## A. Project Rationale and Scope

1. What problem does your system solve?  
It solves transaction inaccuracies, weak inventory visibility, delayed reporting, and poor decision support by integrating POS, inventory, purchasing, reporting, and recommendation workflows in one system.

2. Why did you choose this study?  
Because convenience stores commonly face stock and monitoring issues that can be reduced through a practical, integrated information system.

3. Who are the primary beneficiaries?  
Store owner, manager, cashier, inventory staff, purchasing officer, security, and bookkeeper.

4. What is inside and outside your scope?  
Inside: POS, inventory, purchasing, loyalty, reports, recommendations, security controls. Outside: online payment gateways, native mobile app, external AI APIs.

## B. Technical Design and Architecture

5. What architecture did you use?  
MVC architecture with route-controller-model-view separation for maintainability and modularity.

6. How does request flow work?  
Router matches URL, controller validates and processes, model performs DB operations, then view renders response.

7. Why PHP and MySQL?  
They are stable, cost-effective, and practical for local SME deployment.

8. Why iterative with prototyping?  
Because it allows early validation of workflows and continuous refinement based on feedback.

## C. Database and Data Integrity

9. Where does data come from?  
From user operations: cashier transactions, inventory updates, purchase order processing, loyalty activity, and system-generated logs.

10. How do you ensure data consistency?  
Transactional updates, defined relational schema, role-based process control, and audit trails for critical actions.

11. Explain your most important relationships.  
Users-to-roles, transactions-to-items, products-to-batches, suppliers-to-purchase-orders, and users-to-audit-logs.

## D. Security and Access Control

12. How do you secure passwords?  
Using bcrypt hashing and verification; plain text passwords are never stored.

13. How do you prevent unauthorized access?  
Role-based permission checks at route/controller level plus UI restrictions.

14. How do you prevent SQL injection?  
Using PDO prepared statements with parameterized queries.

15. What accountability mechanism exists?  
Audit logs with user action, timestamp, and related record information.

## E. Functional Modules

16. How does POS work end-to-end?  
Login/PIN, open shift, add products, apply discounts/loyalty, checkout, save transaction/items, update stock, print/reprint receipt, close shift.

17. How is inventory managed?  
Through product maintenance, stock adjustments, stocktake counting/finalization, expiring and aging monitoring, and shelf visibility.

18. How does purchasing work?  
Supplier management and PO lifecycle: create, add items, submit, approve/reject, order, receive.

19. What reports are available?  
Sales, customer transactions, profit, inventory, shrinkage, cashier, supplier, AI accuracy, plus CSV/Excel exports.

20. What does loyalty do?  
Tracks customer points, supports earn/redeem operations, and helps retention through rewards.

## F. AI/Recommendation Features

21. Why call it AI if no external AI API?  
Because recommendations are generated from historical business data using internal algorithmic logic for decision support.

22. What recommendations are generated?  
Reorder suggestions, dead-stock alerts, and promotion opportunities, with additional insight views and recommendation actions.

23. How do users interact with recommendations?  
They can generate, review, accept, dismiss, and provide feedback on recommendations.

## G. Limitations and Future Work

24. What are your limitations?  
Local deployment focus, no native mobile app, and no online payment gateway integration.

25. What are your next improvements?  
Cloud deployment, automated tests, stronger monitoring, and advanced forecasting models.

26. Why should this system be adopted?  
It improves operational control, reduces stock-related losses, accelerates reporting, and supports data-driven management.

---

## 5. Quick Rehearsal Plan (Tonight)

1. First run-through: no slides, just flow and transitions (20 mins)
2. Second run-through: with slides and timing (30 mins)
3. Q&A simulation: each member answers 10 random questions (30 mins)
4. Final polish: fix overlapping answers and shorten long responses (15 mins)

---

## 6. Final Closing Script (Team)

Panelists, ShopWise AI demonstrates a practical and secure integration of POS, inventory, purchasing, reporting, and decision support features. It addresses real operational pain points through role-based workflows and reliable data handling. Thank you, and we are ready for your evaluation and questions.
