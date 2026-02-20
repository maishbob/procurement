# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Kenya School Procurement, Inventory & Finance Governance System — a Laravel 10 modular monolith enforcing Kenya procurement law (PPADA), KRA tax rules (VAT 16%, WHT), and institutional financial governance for educational institutions. This is compliance infrastructure, not a generic CRUD app.

## Common Commands

```bash
# PHP / Laravel
php artisan serve                          # Start dev server
php artisan migrate                        # Run migrations
php artisan migrate:fresh --seed           # Reset and reseed DB
php artisan db:seed                        # Seed without migrating
php artisan tinker                         # Interactive REPL
php artisan cache:clear && php artisan config:clear && php artisan view:clear

# Testing (no phpunit.xml at root — uses Laravel defaults)
php artisan test                           # Run all tests
php artisan test tests/Unit/Core/TaxEngineTest.php  # Run a single test file
php artisan test --filter "test_method_name"        # Run a specific test

# Code style (Laravel Pint)
./vendor/bin/pint                          # Fix all files
./vendor/bin/pint app/Models/User.php     # Fix a single file

# Frontend
npm run dev        # Vite dev server (watch mode)
npm run build      # Production Vite build
```

## Architecture: Modular Monolith

Two structural layers coexist:

### `app/Core/` — Shared Governance Engines
Cross-cutting engines used by all modules. Do not bypass these:

| Engine | Purpose |
|---|---|
| `Workflow/WorkflowEngine.php` | State machine for all workflow entities |
| `Audit/AuditService.php` | Immutable audit trail (7-year retention, mandatory) |
| `TaxEngine/TaxEngine.php` | VAT (16%) + WHT calculation (categories: services 5%, professional 5%, management 2%, training 5%) |
| `CurrencyEngine/CurrencyEngine.php` | Multi-currency with exchange rate locking |
| `Rules/GovernanceRules.php` | PPADA-aligned compliance rule enforcement |

### `app/Modules/` — Domain Modules
Each module owns its Models, Services, Controllers, Policies, and Observers:

- `Requisitions/` — Full requisition lifecycle with approval routing
- `PurchaseOrders/` — PO management
- `Finance/` — Payments, invoices, WHT certificates, Pesapal gateway integration
- `Suppliers/` — Supplier registry and performance
- `GRN/` — Goods Received Notes
- `Inventory/` — Stock, stores, transactions
- `Planning/` — Annual Procurement Plans (in progress)
- `Quality/` — CAPA (Corrective and Preventive Actions)
- `Reporting/` — KPI dashboard service

### `app/Services/` — Top-Level Services
Parallel service layer at `app/Services/` (e.g. `RequisitionService`, `BudgetService`, `ApprovalService`). The module-level services in `app/Modules/*/Services/` and these top-level services have overlapping responsibilities — prefer module-level services when working within a module.

## Dual Model Layer (Structural Inconsistency)

Models exist in **two locations**: `app/Models/` and `app/Modules/*/Models/`. For example, `Requisition` exists as both `App\Models\Requisition` and `App\Modules\Requisitions\Models\Requisition`. When referencing a model, check both locations. The module-level models are generally more feature-complete.

## Authorization Model

Authorization uses **Laravel Policies exclusively** (no Spatie `role`/`permission` route middleware — those are not registered in `Kernel.php`). Policies are mapped in `AuthServiceProvider`. Role checks inside policies use Spatie's `hasRole()` / `hasPermissionTo()` on the `User` model.

Roles: `super-admin`, `principal`, `finance-manager`, `procurement-officer`, `stores-manager`, `hod`, `budget-owner`, `staff`, `auditor`, `accountant`

## Procurement Approval Thresholds (Kenya-specific)

Configured in `config/procurement.php` and `.env`:
- HOD: up to KES 50,000
- Principal: up to KES 200,000
- Board: above KES 1,000,000

Segregation of duties is enforced: requester ≠ approver ≠ buyer ≠ receiver ≠ payment processor.

## Key Workflow States

**Requisition**: `draft → submitted → hod_review → hod_approved → budget_review → budget_approved → procurement_queue → sourcing → quoted → evaluated → awarded → po_created → completed` (+ `rejected` / `cancelled`)

**Payment**: `draft → submitted → verification_pending → verified → approval_pending → approved → payment_processing → paid → completed`

All transitions must go through `WorkflowEngine` and are logged via `AuditService`.

## Budget Accounting

`BudgetLine` uses commitment accounting: `available = allocated - committed - spent`. Methods `commit()`, `uncommit()`, `spend()` are transactional. `BUDGET_OVERRUN_ALLOWED=false` by default — the system will block requests that exceed available budget.

## Three-Way Matching

Payments require PO + GRN + Invoice alignment within 2% tolerance, enforced by `SupplierInvoiceObserver` and `ThreeWayMatchingIntegrationTest`.

## Tests

```
tests/Unit/Core/TaxEngineTest.php                          — VAT/WHT calculations
tests/Unit/Core/AuditServiceTest.php                       — Audit immutability
tests/Feature/Workflows/RequisitionWorkflowTest.php        — Full requisition workflow
tests/Feature/Workflows/PaymentSegregationOfDutiesTest.php — SoD enforcement
tests/Feature/Finance/BudgetEnforcementTest.php            — Budget overrun controls
tests/Integration/ThreeWayMatchingIntegrationTest.php      — PO+GRN+Invoice matching
```

## Environment Notes

- `APP_TIMEZONE=Africa/Nairobi` — all timestamps are EAT
- `DEFAULT_CURRENCY=KES`, `DEFAULT_VAT_RATE=16`
- `ETIMS_ENABLED=false` — KRA eTIMS e-invoicing integration is planned but disabled
- `SMS_DRIVER=africastalking` — Africa's Talking SMS (disabled by default)
- `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`
- Frontend: Vite + Tailwind CSS 3.3 + Alpine.js (no React/Vue)
