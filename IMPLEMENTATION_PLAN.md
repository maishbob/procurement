# SCIS Procurement System — Full Implementation Plan

Based on the audit of the current codebase against the envisioned R2P workflow (SCIS Procurement & Inventory Management Policy & Procedure).

**Date:** 2026-02-19
**Audited by:** Claude Code
**Baseline:** Initial commit — Laravel 11 modular monolith

---

## How to Read This Plan

Each item is tagged with:
- **Type:** BUG (broken code), WIRE (existing backend needs connecting), BUILD (new feature)
- **Risk:** HIGH (data integrity / compliance), MEDIUM (workflow gap), LOW (UI / reporting)
- **Effort:** S (< 1 day), M (1–3 days), L (3–7 days), XL (> 1 week)

Phases must be completed in order. Phase 1 items are blockers — the system will produce incorrect results in production without them.

---

## Phase 1 — Fix Broken and Stubbed Core Logic
### Priority: CRITICAL — Complete before any user testing

These are silent bugs and empty stubs that will produce wrong data or runtime errors.

---

### 1.1 — Fix duplicate `etims` key in config
**Type:** BUG | **Risk:** HIGH | **Effort:** S

**Problem:** `config/procurement.php` defines the `etims` key twice (lines 63 and 128). PHP silently uses the second definition, discarding the first. The `api_url`, `api_key`, `pin`, and `timeout` from the first block are lost.

**Fix:**
- Merge both `etims` blocks into one in `config/procurement.php`
- Final merged block should contain: `enabled`, `enforce_on_payment`, `grace_period_days`, `api_url`, `api_key`, `pin`, `timeout`, `retry_attempts`, `verification_required`

**File:** `config/procurement.php`

---

### 1.2 — Fix `validateThreeWayMatch()` method signature mismatch
**Type:** BUG | **Risk:** HIGH | **Effort:** S

**Problem:** `GovernanceRules::validateThreeWayMatch()` (in `app/Core/Rules/GovernanceRules.php`) expects a structured array with keys `po`, `grn`, `invoice`. `InvoiceService::performThreeWayMatch()` calls it with scalar values (`$poTotal`, `$invoiceTotal`, `$tolerance`). This will throw a runtime error when an invoice goes through verification in production.

**Fix:**
- Align the call signature in `InvoiceService` to match the structured array `GovernanceRules` expects, OR
- Update `GovernanceRules::validateThreeWayMatch()` to accept scalars directly
- Add a regression test to `tests/Integration/ThreeWayMatchingIntegrationTest.php` covering the full call chain

**Files:**
- `app/Core/Rules/GovernanceRules.php`
- `app/Modules/Finance/Services/InvoiceService.php`
- `tests/Integration/ThreeWayMatchingIntegrationTest.php`

---

### 1.3 — Replace mock `validateBudgetAvailability()` with real query
**Type:** BUG | **Risk:** HIGH | **Effort:** S

**Problem:** `GovernanceRules::validateBudgetAvailability()` returns a hardcoded result and never queries the `budget_lines` table. Any budget check routed through `GovernanceRules` silently passes regardless of actual available funds.

**Fix:**
- Inject `BudgetService` or query `BudgetLine` directly inside `validateBudgetAvailability()`
- Use the real formula: `available = allocated - committed - spent`
- Return false (blocked) if `available < requested_amount` and `BUDGET_OVERRUN_ALLOWED = false`

**Files:**
- `app/Core/Rules/GovernanceRules.php`
- `app/Services/BudgetService.php`

---

### 1.4 — Implement `updateBudgetSpent()` in PaymentService
**Type:** BUG | **Risk:** HIGH | **Effort:** M

**Problem:** `PaymentService::updateBudgetSpent()` is an empty function. When a payment is processed, the budget `spent_amount` is never updated. The commitment accounting cycle (`commit → spend → release`) is broken at the final step — budget lines will permanently show funds as "committed" rather than "spent" after payment.

**Fix:**
- Load the budget line(s) linked to the invoices being paid (via `invoice → grn → purchase_order → requisition → budget_line_id`)
- For each invoice, call `BudgetLine::spend($allocatedAmount)` (transactional)
- Call `BudgetLine::uncommit($allocatedAmount)` to release the committed hold
- Log via `AuditService`

**Files:**
- `app/Modules/Finance/Services/PaymentService.php`
- `app/Models/BudgetLine.php`
- `app/Services/BudgetService.php`

---

### 1.5 — Wire budget commit to workflow transition
**Type:** BUG | **Risk:** HIGH | **Effort:** M

**Problem:** `BudgetLine::commit()` exists and is correct, but nothing calls it when a requisition reaches `budget_approved`. Budget funds are never ring-fenced at approval time, meaning the same funds could be committed to multiple concurrent requisitions.

**Fix:**
- In `RequisitionService` (or via a `RequisitionObserver`), listen for the transition to `budget_approved`
- Call `BudgetService::commitBudget($requisition->budget_line_id, $requisition->estimated_total)`
- On rejection or cancellation from any post-`budget_approved` state, call `BudgetService::releaseCommitment()`
- Add budget commitment state to the audit trail

**Files:**
- `app/Modules/Requisitions/Services/RequisitionService.php` or new `app/Observers/RequisitionObserver.php`
- `app/Services/BudgetService.php`
- `app/Core/Workflow/WorkflowEngine.php` (optional: add post-transition hook support)

---

### 1.6 — Implement `createApprovalRecords()` in RequisitionService
**Type:** BUG | **Risk:** HIGH | **Effort:** M

**Problem:** `RequisitionService::createApprovalRecords()` is a stub. No approval routing records are created when a requisition is submitted. HODs have no queue of items to review; the approval workflow has no actor assignment.

**Fix:**
- Determine required approvers based on `requisition->department_id` and `estimated_total`
- Create `RequisitionApproval` records for: HOD of the department, Finance Manager (budget review), Procurement Officer (procurement queue)
- For amounts above thresholds, add Executive Head and/or Board approval records
- Each record should contain: `requisition_id`, `approver_id`, `role`, `sequence`, `status = pending`, `due_at` (now + 72 hours per governance config)

**Files:**
- `app/Modules/Requisitions/Services/RequisitionService.php`
- `app/Modules/Requisitions/Models/RequisitionApproval.php`

---

## Phase 2 — Complete the R2P Workflow (Missing Step)
### Priority: HIGH — Required for process completeness

---

### 2.1 — Build Step 6: End-User Acceptance
**Type:** BUILD | **Risk:** MEDIUM | **Effort:** M

**Problem:** There is no acceptance step between GRN approval (Stores Manager confirms receipt) and invoicing. The policy requires the end-user department to confirm goods/services are fit for purpose before payment can proceed.

**What to build:**
- Add `acceptance_status` (`pending`, `accepted`, `partially_accepted`, `rejected`) and `accepted_by`, `accepted_at`, `acceptance_notes` fields to `GoodsReceivedNote` model (migration required)
- Add a new GRN workflow state: `accepted` (between `approved` and `completed`)
- Add route: `POST /grn/{grn}/accept` and `POST /grn/{grn}/reject-acceptance`
- Build controller action in `GRNController` for acceptance sign-off
- Enforce in `InvoiceService`: GRN must have `acceptance_status = accepted` before an invoice can be raised against it
- For services/works: add optional `completion_certificate` file upload field

**Files:**
- New migration: `add_acceptance_fields_to_grns_table`
- `app/Modules/GRN/Models/GoodsReceivedNote.php`
- `app/Http/Controllers/GRNController.php`
- `app/Modules/Finance/Services/InvoiceService.php`
- `app/Core/Workflow/WorkflowEngine.php` (add `accepted` state to GRN workflow)
- `routes/web.php`
- New view: `resources/views/grn/accept.blade.php`

---

### 2.2 — Add Serial/Batch/Expiry to GRN Items
**Type:** BUILD | **Risk:** MEDIUM | **Effort:** S

**Problem:** `GRNItem` has no `serial_number`, `batch_number`, or `expiry_date` fields. These are required for pharmaceuticals, lab consumables, and tracked equipment. The Inventory `stock_transactions` table has these fields but they are not captured at receipt time.

**What to build:**
- Add `serial_number`, `batch_number`, `expiry_date`, `storage_location` to `grn_items` table (migration)
- Update `GRNItem` model fillable and casts
- Update GRN receive form to capture these per line item (conditional on item type)
- Pass values through to `GRNService::postToInventory()` when creating stock transaction records

**Files:**
- New migration: `add_tracking_fields_to_grn_items_table`
- `app/Modules/GRN/Models/GRNItem.php`
- `app/Services/GRNService.php`

---

## Phase 3 — Implement Missing Enforcement Features
### Priority: HIGH — Required for policy compliance

---

### 3.1 — Implement the 5-Tier Cash Band System
**Type:** BUILD | **Risk:** HIGH | **Effort:** M

**Problem:** `config/procurement.php` has 3 thresholds. The policy defines 5 cash bands (Micro/Low/Medium/High/Strategic), each with a specific sourcing method and minimum competition requirement. The system currently has no logic that maps a purchase amount to its required method or enforces minimum quote counts.

**What to build:**
- Add the 5 cash bands to `config/procurement.php` as a structured array with: `name`, `min`, `max`, `method`, `min_quotes`, `approvers`
- Build `GovernanceRules::determineCashBand(float $amount): array` — returns the applicable band
- Build `GovernanceRules::getRequiredSourcingMethod(float $amount): string` — returns `spot_buy`, `rfq`, `rfp`, or `tender`
- Build `GovernanceRules::getMinimumQuotes(float $amount): int`
- Enforce in `ProcurementService::createRFQ()` — reject if amount requires RFP/Tender
- Enforce in `ProcurementService::evaluateBids()` — reject if fewer than minimum quotes received
- Enforce in `RequisitionService` approval routing — derive required approver roles from band

**Files:**
- `config/procurement.php`
- `app/Core/Rules/GovernanceRules.php`
- `app/Services/ProcurementService.php`
- `app/Modules/Requisitions/Services/RequisitionService.php`

---

### 3.2 — Build Approved Supplier List (ASL)
**Type:** BUILD | **Risk:** HIGH | **Effort:** M

**Problem:** No ASL exists. Any supplier in the database can receive an RFQ regardless of whether they are approved, onboarded, or tax compliant.

**What to build:**
- Add `asl_status` (`pending_review`, `approved`, `suspended`, `removed`), `asl_approved_at`, `asl_approved_by`, `asl_review_due_at` to `suppliers` table (migration)
- Add `asl_categories` (JSON — which procurement categories the supplier is approved for)
- Build `SupplierService::approveForASL()`, `suspendFromASL()`, `removeFromASL()` methods
- Enforce in `ProcurementService` — when adding suppliers to an RFQ/RFP, validate `asl_status = approved`
- Add ASL management routes and views for Procurement Manager
- Add ASL status to supplier index and detail views

**Files:**
- New migration: `add_asl_fields_to_suppliers_table`
- `app/Modules/Suppliers/Models/Supplier.php`
- `app/Modules/Suppliers/Services/SupplierService.php`
- `app/Services/ProcurementService.php`
- New controller: `app/Http/Controllers/SupplierASLController.php`
- New views: `resources/views/suppliers/asl/`
- `routes/web.php`

---

### 3.3 — Build Supplier Onboarding Pack
**Type:** BUILD | **Risk:** MEDIUM | **Effort:** L

**Problem:** The `Supplier` model references `SupplierDocument`, `SupplierContact`, `SupplierCategory`, `SupplierPerformanceReview` relationships, but none of these model files exist. New suppliers have no onboarding checklist or pre-qualification process before they can be awarded.

**What to build:**
- Create `app/Modules/Suppliers/Models/SupplierDocument.php` — with `document_type` (kra_pin_certificate, tax_compliance_certificate, bank_letter, business_registration, eTIMS_registration), `file_path`, `expiry_date`, `verified`, `verified_by`, `verified_at`
- Create `app/Modules/Suppliers/Models/SupplierContact.php` — name, role, email, phone, is_primary
- Create `app/Modules/Suppliers/Models/SupplierCategory.php` — links supplier to procurement categories
- Create migrations for each
- Add `onboarding_status` (`incomplete`, `under_review`, `approved`, `expired`) to `suppliers` table
- Build `SupplierService::calculateOnboardingCompleteness()` — checks all required documents are present, verified, and not expired
- Gate ASL approval on `onboarding_status = approved`
- Build onboarding views: document upload, checklist, verification workflow for Procurement Manager

**Files:**
- New models in `app/Modules/Suppliers/Models/`
- New migrations
- `app/Modules/Suppliers/Services/SupplierService.php`
- New views: `resources/views/suppliers/onboarding/`

---

### 3.4 — Build Conflict of Interest Self-Declaration UI
**Type:** BUILD | **Risk:** HIGH | **Effort:** M

**Problem:** `ConflictOfInterestDeclaration` model and enforcement exist but there is no UI for evaluation panel members to declare conflicts. Panel members cannot submit declarations through the application.

**What to build:**
- Build route: `GET /procurement/{process}/coi-declaration` — declaration form
- Build route: `POST /procurement/{process}/coi-declaration` — submit declaration
- On submission, create `ConflictOfInterestDeclaration` record linked to the `ProcurementProcess`
- If `has_conflict = true`, automatically remove the user from the evaluation panel and notify the Procurement Manager
- Block access to bid evaluation views if a CoI declaration has not been submitted for the process
- Show CoI declaration status on the procurement process detail view

**Files:**
- New controller: `app/Http/Controllers/ConflictOfInterestController.php`
- New views: `resources/views/procurement/coi/`
- `app/Models/ConflictOfInterestDeclaration.php`
- `app/Services/ProcurementService.php`
- `routes/web.php`

---

### 3.5 — Add Explicit Payment-Level Policy Guard (No PO No Pay / No GRN No Pay)
**Type:** BUILD | **Risk:** HIGH | **Effort:** S

**Problem:** "No PO No Pay" and "No GRN No Pay" are enforced through the invoice chain but not at the payment policy level. A payment created against a manually-set invoice could bypass these controls.

**What to build:**
- In `app/Policies/PaymentPolicy.php`, add a `create()` check that validates the invoice chain: invoice → GRN (accepted) → PO (approved)
- In `PaymentService::create()`, explicitly verify that every invoice being paid has a linked, accepted GRN and an approved PO
- Throw a descriptive exception if the chain is broken, identifying which invoice failed and why

**Files:**
- `app/Policies/PaymentPolicy.php`
- `app/Modules/Finance/Services/PaymentService.php`

---

## Phase 4 — Connect Existing Backends to UI
### Priority: MEDIUM — Modules are built but unreachable

These modules have complete service/model layers but no HTTP surface. Users cannot access them.

---

### 4.1 — Wire Annual Procurement Plan (APP) to Controller and Routes
**Type:** WIRE | **Risk:** MEDIUM | **Effort:** M

**What to build:**
- Create `app/Http/Controllers/AnnualProcurementPlanController.php` with: `index`, `create`, `store`, `show`, `edit`, `update`, `submit`, `approve`, `reject`
- Register resource route in `routes/web.php`
- Build views: `resources/views/planning/`
  - `index.blade.php` — list of APPs by fiscal year
  - `create.blade.php` — new APP form with line items
  - `show.blade.php` — APP detail with status and approval history
  - `edit.blade.php` — edit draft APP
- Wire to `AnnualProcurementPlanService` methods already implemented
- Register `AnnualProcurementPlanPolicy` in `AuthServiceProvider`

**Files:**
- New: `app/Http/Controllers/AnnualProcurementPlanController.php`
- New: `resources/views/planning/`
- `routes/web.php`
- `app/Providers/AuthServiceProvider.php`

---

### 4.2 — Wire CAPA Module to Controller and Routes
**Type:** WIRE | **Risk:** MEDIUM | **Effort:** M

**What to build:**
- Create `app/Http/Controllers/CapaController.php` with full lifecycle actions (create, submit, approve, start, submit-for-verification, verify, close)
- Register resource route + additional action routes
- Build views: `resources/views/quality/capa/`
  - `index.blade.php` — CAPA register with status filters
  - `create.blade.php` — new CAPA form
  - `show.blade.php` — CAPA detail with updates timeline
- Link CAPA creation from: GRN inspection failures, 3-way match failures, payment failures (trigger `CapaService::createFromVariance()`)

**Files:**
- New: `app/Http/Controllers/CapaController.php`
- New: `resources/views/quality/capa/`
- `routes/web.php`

---

### 4.3 — Wire KPI Dashboard to HTTP Endpoint
**Type:** WIRE | **Risk:** LOW | **Effort:** S

**What to build:**
- Add `dashboard()` method to `app/Http/Controllers/ReportController.php`
- Route: `GET /reports/dashboard` → `ReportController@dashboard`
- Call `KpiDashboardService::getDashboardData()` with date range and fiscal year filters from request
- Build view: `resources/views/reports/dashboard.blade.php` — display KPI cards, trend charts, exception tables
- Add link to sidebar navigation

**Files:**
- `app/Http/Controllers/ReportController.php`
- New: `resources/views/reports/dashboard.blade.php`
- `routes/web.php`
- `resources/views/layouts/partials/sidebar.blade.php`

---

### 4.4 — Wire Supplier Sub-Models and Relations
**Type:** WIRE | **Risk:** MEDIUM | **Effort:** S

**Problem:** `Supplier.php` declares `hasMany` relationships to `SupplierDocument`, `SupplierContact`, `SupplierCategory`, `SupplierPerformanceReview` — but these model files do not exist. Any code that eager-loads these will throw a class-not-found error.

**Fix (interim, before Phase 3.3 full build):**
- Remove or comment out the four broken `hasMany` relationships from `Supplier.php`
- Or create stub model files for each to prevent the class-not-found error

**Files:**
- `app/Modules/Suppliers/Models/Supplier.php`

---

## Phase 5 — External Integrations
### Priority: LOW (until policy mandated) — Stubs exist; requires external credentials

---

### 5.1 — Activate PesaPal 3-Step Authorization
**Type:** BUILD | **Risk:** HIGH | **Effort:** L

**What to build:**
- Implement `PesapalGatewayService::callPesapalApi()` with real OAuth 1.0a signing and HTTP call
- Implement `initiatePayment()` — calls PesaPal `SubmitOrderRequest`; records `payment_gateway_transactions` entry
- Implement `checkPaymentStatus()` — calls PesaPal `GetTransactionStatus`; updates transaction record
- Add webhook endpoint: `POST /payments/pesapal/callback` — receives IPN (Instant Payment Notification), reconciles to `Payment` record
- Enforce role mapping: `Chief Accountant` → `initiator`, `Procurement Manager` → `approver`, `Director` → `processor` in `PaymentGatewayRole` seeder
- Add PesaPal routes for initiate/approve/status in `routes/web.php`
- Store PesaPal credentials in `.env`: `PESAPAL_CONSUMER_KEY`, `PESAPAL_CONSUMER_SECRET`, `PESAPAL_ENVIRONMENT` (sandbox/live)

**Files:**
- `app/Modules/Finance/Services/PesapalGatewayService.php`
- `config/services.php`
- New: `app/Http/Controllers/PesapalWebhookController.php`
- `routes/web.php`
- New seeder: `database/seeders/PaymentGatewayRoleSeeder.php`

---

### 5.2 — Activate eTIMS Invoice Verification
**Type:** BUILD | **Risk:** HIGH | **Effort:** L

**What to build:**
- Implement `InvoiceService::verifyETIMS()` with real HTTP call to KRA eTIMS API (`ETIMS_API_URL`)
- On verification response: set `etims_verified = true`, `etims_verified_at = now()`, `etims_qr_code` on `SupplierInvoice`
- On failure: log the failure, set `etims_verified = false`, return error to user
- Queue verification as a background job (`VerifyEtimsInvoiceJob`) to avoid blocking the request
- Handle retry logic (3 attempts per `config/procurement.php`) with exponential backoff
- Update `PaymentService::validateEtimsCompliance()` to check `etims_verified = true`, not just that `etims_control_number` is present
- Add `ETIMS_API_URL`, `ETIMS_API_KEY`, `ETIMS_PIN` to `.env`

**Files:**
- `app/Modules/Finance/Services/InvoiceService.php`
- New: `app/Jobs/VerifyEtimsInvoiceJob.php`
- `config/procurement.php` (after fix from Phase 1.1)

---

## Phase 6 — Test Coverage
### Priority: MEDIUM — Run alongside Phases 1–3

For each fix or new feature, the following tests should be written or updated:

| Test File | What to Cover |
|---|---|
| `tests/Unit/Core/TaxEngineTest.php` | Existing — verify WHT rates against all 5 categories |
| `tests/Feature/Workflows/RequisitionWorkflowTest.php` | Add: budget commit triggered at `budget_approved`; uncommit on rejection/cancellation |
| `tests/Feature/Finance/BudgetEnforcementTest.php` | Add: `validateBudgetAvailability()` queries real DB; overrun blocked; `updateBudgetSpent()` updates budget line |
| `tests/Integration/ThreeWayMatchingIntegrationTest.php` | Fix: ensure full call chain works without method signature error; test 2% tolerance boundary |
| New: `tests/Feature/Workflows/AcceptanceWorkflowTest.php` | End-user acceptance step; block invoice creation without acceptance |
| New: `tests/Feature/Procurement/CashBandEnforcementTest.php` | Each band routes to correct method; minimum quote enforcement |
| New: `tests/Feature/Suppliers/ApprovedSupplierListTest.php` | Unapproved supplier cannot be added to RFQ |
| New: `tests/Feature/Finance/PaymentChainGuardTest.php` | Payment blocked if PO or GRN missing from invoice chain |
| New: `tests/Feature/Finance/PesapalAuthorizationTest.php` | 3-step SoD — same person cannot initiate and approve |

---

## Delivery Summary

| Phase | Focus | Items | Priority |
|---|---|---|---|
| 1 | Fix broken/stubbed core logic | 6 items | CRITICAL |
| 2 | Complete missing R2P step (Acceptance) + GRN serial tracking | 2 items | HIGH |
| 3 | Missing enforcement (cash bands, ASL, onboarding, CoI UI, payment guard) | 5 items | HIGH |
| 4 | Connect existing backends to UI (APP, CAPA, KPI, supplier relations) | 4 items | MEDIUM |
| 5 | External integrations (PesaPal live, eTIMS live) | 2 items | LOW* |
| 6 | Test coverage | 9 test files | MEDIUM |

*Phase 5 priority escalates to HIGH once the school has live PesaPal and KRA eTIMS credentials.

---

## Key Config Changes Required

```
# config/procurement.php — merge duplicate etims keys
# config/procurement.php — replace 3-tier thresholds with 5-band cash band array

# .env additions
PESAPAL_CONSUMER_KEY=
PESAPAL_CONSUMER_SECRET=
PESAPAL_ENVIRONMENT=sandbox
ETIMS_API_URL=https://etims.kra.go.ke/api
ETIMS_API_KEY=
ETIMS_PIN=
```

---

## Roles and Permissions Required (to be seeded)

The following roles must exist in Spatie permissions for the system to enforce correctly:

| Role | Key Responsibilities in Workflow |
|---|---|
| `staff` | Raise requisitions |
| `hod` | Approve requisitions (Step 1 → 2), accept deliveries (Step 6) |
| `budget-owner` | Budget verification (Step 2) |
| `procurement-officer` | Sourcing, GRN inspection (Steps 3–5) |
| `stores-manager` | GRN creation (Step 5) |
| `finance-manager` | Invoice verification, payment approval (Steps 7–8) |
| `accountant` | Payment creation (Step 8 — Maker) |
| `principal` | Executive Head approvals for Medium band |
| `super-admin` | ASL management, system configuration |
| `auditor` | Read-only access to all audit trails |
