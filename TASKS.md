# SCIS Procurement System — Task Breakdown

Derived from `IMPLEMENTATION_PLAN.md`.
Each task is independently completable and has a clear done condition.

Legend: BUG | WIRE | BUILD — S / M / L / XL — depends on [T-x.x]

---

## PHASE 1 — Fix Broken and Stubbed Core Logic

---

### T-1.1 — Fix duplicate `etims` config key

**Type:** BUG | **Effort:** S | **No dependencies**

**What to do:**
Merge the two `etims` blocks in `config/procurement.php` into one. The final block must contain all keys from both: `enabled`, `enforce_on_payment`, `grace_period_days`, `api_url`, `api_key`, `pin`, `timeout`, `retry_attempts`, `verification_required`.

**File:** `config/procurement.php`

**Done when:**
- Only one `etims` key exists in the config file
- `config('procurement.etims.timeout')` and `config('procurement.etims.enforce_on_payment')` both return values
- `php artisan config:clear` runs without error

---

### T-1.2 — Fix `validateThreeWayMatch()` method signature mismatch

**Type:** BUG | **Effort:** S | **No dependencies**

**What to do:**
In `GovernanceRules::validateThreeWayMatch()`, check the expected parameter structure. In `InvoiceService::performThreeWayMatch()`, find the call site. Align one to the other — preference is to fix the call in `InvoiceService` to pass the structured array that `GovernanceRules` expects, keeping `GovernanceRules` as the stable contract.

**Files:**
- `app/Core/Rules/GovernanceRules.php`
- `app/Modules/Finance/Services/InvoiceService.php`

**Done when:**
- The method call and signature match exactly
- `php artisan test tests/Integration/ThreeWayMatchingIntegrationTest.php` passes without error
- No `ArgumentCountError` or `TypeError` thrown when an invoice is verified

---

### T-1.3 — Add regression test for three-way match call chain

**Type:** BUG | **Effort:** S | **Depends on:** T-1.2

**What to do:**
In `tests/Integration/ThreeWayMatchingIntegrationTest.php`, add a test that exercises the full call chain from `InvoiceService::performThreeWayMatch()` through `GovernanceRules::validateThreeWayMatch()`. Cover: exact match passes, within 2% passes, over 2% blocks.

**File:** `tests/Integration/ThreeWayMatchingIntegrationTest.php`

**Done when:**
- Test exists and covers the three scenarios above
- `php artisan test tests/Integration/ThreeWayMatchingIntegrationTest.php` is green

---

### T-1.4 — Replace mock `validateBudgetAvailability()` with real DB query

**Type:** BUG | **Effort:** S | **No dependencies**

**What to do:**
In `GovernanceRules::validateBudgetAvailability()`, replace the hardcoded mock return with a real query against `BudgetLine`. Use the formula: `available = allocated - committed - spent`. Return `false` if `available < requested_amount` and `BUDGET_OVERRUN_ALLOWED` is false. Inject `BudgetService` or query `BudgetLine` directly.

**Files:**
- `app/Core/Rules/GovernanceRules.php`
- `app/Services/BudgetService.php`

**Done when:**
- Method queries the real `budget_lines` table
- Returns `false` when available funds are insufficient
- `php artisan test tests/Feature/Finance/BudgetEnforcementTest.php` passes

---

### T-1.5 — Implement `updateBudgetSpent()` in PaymentService

**Type:** BUG | **Effort:** M | **Depends on:** T-1.4

**What to do:**
In `PaymentService::updateBudgetSpent()`, traverse the chain: `payment → invoices → grn → purchase_order → requisition → budget_line_id`. For each invoice in the payment, call `BudgetLine::spend($invoice->pivot->amount_allocated)` and `BudgetLine::uncommit($invoice->pivot->amount_allocated)` within a DB transaction. Log the budget update via `AuditService`.

**Files:**
- `app/Modules/Finance/Services/PaymentService.php`
- `app/Models/BudgetLine.php`

**Done when:**
- After a payment is processed, the linked `BudgetLine` has its `spent_amount` increased and `committed_amount` decreased by the payment amount
- Budget `available_amount` is unchanged (spend replaces commit, not adds to it)
- Change is logged in audit trail
- Test added to `tests/Feature/Finance/BudgetEnforcementTest.php`

---

### T-1.6 — Wire budget commit to `budget_approved` workflow transition

**Type:** BUG | **Effort:** M | **Depends on:** T-1.4

**What to do:**
Create a `RequisitionObserver` (or add to the existing one if it exists). Listen for the `updated` event on `Requisition`. When `status` changes to `budget_approved`, call `BudgetService::commitBudget($requisition->budget_line_id, $requisition->estimated_total)`. When status changes to `rejected` or `cancelled` from any state after `budget_approved`, call `BudgetService::releaseCommitment()`. Register the observer in `AppServiceProvider` or `AuthServiceProvider`.

**Files:**
- New or existing: `app/Observers/RequisitionObserver.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/BudgetService.php`

**Done when:**
- Approving a requisition to `budget_approved` commits funds on the `BudgetLine`
- Rejecting or cancelling after budget approval releases the commitment
- `BudgetLine::available_amount` reflects committed funds in real time
- Tests added to `tests/Feature/Workflows/RequisitionWorkflowTest.php`

---

### T-1.7 — Implement `createApprovalRecords()` in RequisitionService

**Type:** BUG | **Effort:** M | **Depends on:** T-1.6

**What to do:**
In `RequisitionService::createApprovalRecords()`, build the approval routing matrix:

1. Find the HOD of `requisition->department_id` — create a `RequisitionApproval` record with `role = hod`, `sequence = 1`, `status = pending`, `due_at = now() + 72 hours`
2. Create a Finance Manager approval record: `role = budget_owner`, `sequence = 2`
3. Based on `estimated_total` and the cash band (config), add: `procurement-officer` (sequence 3), and if above thresholds, `principal` or `board` records

Each `RequisitionApproval` record must have: `requisition_id`, `approver_id` (looked up by role + department), `role`, `sequence`, `status`, `due_at`.

**Files:**
- `app/Modules/Requisitions/Services/RequisitionService.php`
- `app/Modules/Requisitions/Models/RequisitionApproval.php`

**Done when:**
- Submitting a requisition creates the correct approval chain records
- The chain length and roles match the amount band
- Approvers can query their pending approvals

---

### T-1.8 — Write budget enforcement tests

**Type:** BUG | **Effort:** S | **Depends on:** T-1.4, T-1.5, T-1.6

**What to do:**
In `tests/Feature/Finance/BudgetEnforcementTest.php`, add tests for:
- Budget commit triggered when requisition reaches `budget_approved`
- Budget commitment released when requisition is rejected after `budget_approved`
- `updateBudgetSpent()` moves amount from committed to spent after payment
- `validateBudgetAvailability()` blocks a requisition when budget is insufficient
- Overrun is blocked when `BUDGET_OVERRUN_ALLOWED = false`

**File:** `tests/Feature/Finance/BudgetEnforcementTest.php`

**Done when:** All new tests pass with `php artisan test tests/Feature/Finance/BudgetEnforcementTest.php`

---

## PHASE 2 — Complete the R2P Workflow

---

### T-2.1 — Create migration for acceptance fields on GRN

**Type:** BUILD | **Effort:** S | **No dependencies**

**What to do:**
Generate and write a migration that adds to `grns` table:
- `acceptance_status` enum: `pending`, `accepted`, `partially_accepted`, `rejected` — default `pending`
- `accepted_by` unsigned big integer nullable (FK to `users`)
- `accepted_at` timestamp nullable
- `acceptance_notes` text nullable
- `completion_certificate_path` string nullable (for services/works)

**File:** New migration `add_acceptance_fields_to_grns_table`

**Done when:** `php artisan migrate` runs without error; columns exist in `grns` table

---

### T-2.2 — Update GRN model and workflow for acceptance step

**Type:** BUILD | **Effort:** S | **Depends on:** T-2.1

**What to do:**
- Add new fields to `GoodsReceivedNote` `$fillable` and `$casts`
- Add `accepted` state to the GRN workflow in `WorkflowEngine::getWorkflowTransitions()`: `approved → accepted → completed`
- Add helper methods: `isAccepted()`, `isPendingAcceptance()`, `canBeAccepted()`
- Add `accepted_by` relationship

**Files:**
- `app/Modules/GRN/Models/GoodsReceivedNote.php`
- `app/Core/Workflow/WorkflowEngine.php`

**Done when:**
- Model fillable and casts include new fields
- `WorkflowEngine` allows `approved → accepted` transition
- Helper methods return correct boolean values

---

### T-2.3 — Build acceptance controller actions and routes

**Type:** BUILD | **Effort:** S | **Depends on:** T-2.2

**What to do:**
In `GRNController`, add two actions:
- `accept(Request $request, GoodsReceivedNote $grn)` — validates the GRN is in `approved` state, transitions to `accepted`, records `accepted_by`, `accepted_at`, `acceptance_notes`, optional file upload for `completion_certificate_path`
- `rejectAcceptance(Request $request, GoodsReceivedNote $grn)` — transitions to a `acceptance_rejected` state (add to workflow), records reason

Register in `routes/web.php`:
```
POST /grn/{grn}/accept
POST /grn/{grn}/reject-acceptance
```

**Files:**
- `app/Http/Controllers/GRNController.php`
- `routes/web.php`

**Done when:** Both routes resolve; acceptance sets correct DB fields; audit log entry created

---

### T-2.4 — Build acceptance view

**Type:** BUILD | **Effort:** S | **Depends on:** T-2.3

**What to do:**
Create `resources/views/grn/accept.blade.php`. Display: GRN number, supplier, PO reference, list of items received with quantities and quality notes. Form fields: acceptance decision (radio: accept / partially accept / reject), notes (required on rejection), file upload for completion certificate. Submit button routes to `POST /grn/{grn}/accept`.

**File:** `resources/views/grn/accept.blade.php`

**Done when:** The view renders from the GRN show page with an "Accept Delivery" button (visible to HOD and department users only, via policy)

---

### T-2.5 — Enforce acceptance before invoice creation

**Type:** BUILD | **Effort:** S | **Depends on:** T-2.2

**What to do:**
In `InvoiceService::createFromGRN()` (and in `InvoiceController` validation), add a check: `$grn->acceptance_status` must be `accepted` or `partially_accepted`. Throw a descriptive exception if the GRN has not been accepted by the end-user department.

**Files:**
- `app/Modules/Finance/Services/InvoiceService.php`
- `app/Http/Controllers/InvoiceController.php`

**Done when:**
- Attempting to create an invoice against a non-accepted GRN throws a clear error
- Test added: `tests/Feature/Workflows/AcceptanceWorkflowTest.php`

---

### T-2.6 — Write acceptance workflow tests

**Type:** BUILD | **Effort:** S | **Depends on:** T-2.2, T-2.5

**What to do:**
Create `tests/Feature/Workflows/AcceptanceWorkflowTest.php`. Cover:
- GRN in `approved` state can be accepted
- GRN in `approved` state can be partially accepted
- GRN in `approved` state can be rejected (acceptance)
- Invoice creation blocked if GRN is not yet accepted
- Invoice creation allowed once GRN is accepted

**File:** New `tests/Feature/Workflows/AcceptanceWorkflowTest.php`

**Done when:** All tests pass

---

### T-2.7 — Create migration for serial/batch/expiry on GRN items

**Type:** BUILD | **Effort:** S | **No dependencies**

**What to do:**
Generate and write a migration that adds to `grn_items` table:
- `serial_number` string nullable
- `batch_number` string nullable
- `expiry_date` date nullable
- `storage_location` string nullable

**File:** New migration `add_tracking_fields_to_grn_items_table`

**Done when:** `php artisan migrate` runs; columns exist in `grn_items`

---

### T-2.8 — Update GRNItem model and GRN receive form

**Type:** BUILD | **Effort:** S | **Depends on:** T-2.7

**What to do:**
- Add new fields to `GRNItem` `$fillable` and `$casts`
- Update `GRNService::postToInventory()` to pass `serial_number`, `batch_number`, `expiry_date` through to the `stock_transactions` record it creates
- Update the GRN creation/edit form to show these fields per line item (conditionally shown based on item category)

**Files:**
- `app/Modules/GRN/Models/GRNItem.php`
- `app/Services/GRNService.php`
- `resources/views/grn/create.blade.php` or equivalent

**Done when:** Serial/batch/expiry captured on GRN receipt and passed to inventory stock transaction

---

## PHASE 3 — Missing Enforcement Features

---

### T-3.1 — Define 5-tier cash band structure in config

**Type:** BUILD | **Effort:** S | **No dependencies**

**What to do:**
In `config/procurement.php`, replace the current 3-threshold `thresholds` block with a `cash_bands` array. Each band is a keyed entry with:
```php
'micro'    => ['max' => 50000,    'method' => 'spot_buy',  'min_quotes' => 1, 'approvers' => ['hod','procurement-officer','accountant']],
'low'      => ['max' => 250000,   'method' => 'rfq',       'min_quotes' => 3, 'approvers' => ['hod','procurement-officer','accountant','principal']],
'medium'   => ['max' => 1000000,  'method' => 'rfq_formal','min_quotes' => 3, 'approvers' => ['procurement-officer','accountant','principal']],
'high'     => ['max' => 5000000,  'method' => 'tender',    'min_quotes' => 0, 'approvers' => ['board']],
'strategic'=> ['max' => null,     'method' => 'tender',    'min_quotes' => 0, 'approvers' => ['board']],
```
Keep existing keys that other code references (add a `@deprecated` comment on them); migrate callers in Phase 3.2.

**File:** `config/procurement.php`

**Done when:** `config('procurement.cash_bands')` returns the full 5-band array

---

### T-3.2 — Implement cash band resolver in GovernanceRules

**Type:** BUILD | **Effort:** S | **Depends on:** T-3.1

**What to do:**
Add three methods to `GovernanceRules`:
- `determineCashBand(float $amount): array` — loops bands in ascending order, returns first band where `$amount <= max` (or 'strategic' if above all)
- `getRequiredSourcingMethod(float $amount): string` — calls `determineCashBand()`, returns `method`
- `getMinimumQuotes(float $amount): int` — calls `determineCashBand()`, returns `min_quotes`
- `getRequiredApprovers(float $amount): array` — calls `determineCashBand()`, returns `approvers`

**File:** `app/Core/Rules/GovernanceRules.php`

**Done when:** Each method returns correct values for amounts in each band boundary

---

### T-3.3 — Enforce sourcing method in ProcurementService

**Type:** BUILD | **Effort:** S | **Depends on:** T-3.2

**What to do:**
In `ProcurementService::createRFQ()`, call `GovernanceRules::getRequiredSourcingMethod($requisition->estimated_total)`. If the required method is `tender` or `rfp`, throw an exception: "This purchase value requires a formal tender — RFQ is not permitted." Do the same check in `createRFP()` for `tender`-band amounts.

**File:** `app/Services/ProcurementService.php`

**Done when:** Creating an RFQ for a High or Strategic band amount throws a descriptive error

---

### T-3.4 — Enforce minimum quote count in ProcurementService

**Type:** BUILD | **Effort:** S | **Depends on:** T-3.2

**What to do:**
In `ProcurementService::evaluateBids()`, before allowing evaluation to proceed, count submitted bids for the process. Call `GovernanceRules::getMinimumQuotes($process->estimated_value)`. If `bid_count < min_quotes`, throw: "Evaluation blocked — {$min_quotes} quotes required for this value band; {$bid_count} received."

**File:** `app/Services/ProcurementService.php`

**Done when:** Evaluation is blocked when insufficient bids have been received; test added

---

### T-3.5 — Write cash band enforcement tests

**Type:** BUILD | **Effort:** S | **Depends on:** T-3.2, T-3.3, T-3.4

**What to do:**
Create `tests/Feature/Procurement/CashBandEnforcementTest.php`. Cover:
- Amount in each band returns correct method and min quotes
- RFQ creation blocked for High/Strategic amounts
- Evaluation blocked when fewer than minimum quotes received
- Approval chain for Micro does not include Board
- Approval chain for Strategic requires Board

**File:** New `tests/Feature/Procurement/CashBandEnforcementTest.php`

**Done when:** All tests pass

---

### T-3.6 — Create migration for ASL fields on suppliers

**Type:** BUILD | **Effort:** S | **No dependencies**

**What to do:**
Generate and write a migration adding to `suppliers` table:
- `asl_status` enum: `not_applied`, `pending_review`, `approved`, `suspended`, `removed` — default `not_applied`
- `asl_approved_at` timestamp nullable
- `asl_approved_by` unsigned big integer nullable (FK to `users`)
- `asl_review_due_at` date nullable
- `asl_categories` JSON nullable (procurement categories the supplier is approved for)
- `onboarding_status` enum: `incomplete`, `under_review`, `approved`, `expired` — default `incomplete`

**File:** New migration `add_asl_fields_to_suppliers_table`

**Done when:** `php artisan migrate` runs; columns exist on `suppliers`

---

### T-3.7 — Add ASL methods to SupplierService

**Type:** BUILD | **Effort:** S | **Depends on:** T-3.6

**What to do:**
In `app/Modules/Suppliers/Services/SupplierService.php` (create if it doesn't exist), add:
- `submitForASLReview(Supplier $supplier): Supplier` — sets `asl_status = pending_review`
- `approveForASL(Supplier $supplier, User $approver, array $categories): Supplier` — sets `asl_status = approved`, `asl_approved_by`, `asl_approved_at`, `asl_review_due_at = now() + 1 year`
- `suspendFromASL(Supplier $supplier, string $reason): Supplier`
- `removeFromASL(Supplier $supplier, string $reason): Supplier`
- `isApprovedSupplier(Supplier $supplier): bool`

**File:** `app/Modules/Suppliers/Services/SupplierService.php`

**Done when:** All methods update the correct DB fields and log via `AuditService`

---

### T-3.8 — Enforce ASL in ProcurementService

**Type:** BUILD | **Effort:** S | **Depends on:** T-3.7

**What to do:**
In `ProcurementService`, wherever a supplier is added to an RFQ/RFP/Tender (e.g. `addSupplierToProcess()` or equivalent), check `SupplierService::isApprovedSupplier($supplier)`. If not approved, throw: "Supplier {$supplier->name} is not on the Approved Supplier List and cannot be invited to this process."

**File:** `app/Services/ProcurementService.php`

**Done when:** Unapproved supplier cannot be added to any procurement process; test added

---

### T-3.9 — Build ASL management controller and routes

**Type:** BUILD | **Effort:** M | **Depends on:** T-3.7

**What to do:**
Create `app/Http/Controllers/SupplierASLController.php` with:
- `index()` — list all suppliers with ASL status filter
- `submit(Supplier $supplier)` — submit for review
- `approve(Supplier $supplier)` — approve (Procurement Manager only)
- `suspend(Supplier $supplier)` — suspend with reason
- `remove(Supplier $supplier)` — remove with reason

Register routes:
```
GET    /suppliers/asl
POST   /suppliers/{supplier}/asl/submit
POST   /suppliers/{supplier}/asl/approve
POST   /suppliers/{supplier}/asl/suspend
POST   /suppliers/{supplier}/asl/remove
```

**Files:**
- New: `app/Http/Controllers/SupplierASLController.php`
- `routes/web.php`

**Done when:** All routes resolve; actions update supplier ASL status correctly

---

### T-3.10 — Build ASL management views

**Type:** BUILD | **Effort:** M | **Depends on:** T-3.9

**What to do:**
Create `resources/views/suppliers/asl/`:
- `index.blade.php` — supplier list with ASL status badges, filter by status, action buttons per status
- `review.blade.php` — supplier detail for review: documents checklist, category selector, approve/reject buttons

Update `resources/views/suppliers/show.blade.php` to display ASL status and link to review page.

**Files:**
- New: `resources/views/suppliers/asl/index.blade.php`
- New: `resources/views/suppliers/asl/review.blade.php`
- `resources/views/suppliers/show.blade.php`

**Done when:** Procurement Manager can view, submit, approve, suspend suppliers through the UI

---

### T-3.11 — Write ASL test

**Type:** BUILD | **Effort:** S | **Depends on:** T-3.7, T-3.8

**What to do:**
Create `tests/Feature/Suppliers/ApprovedSupplierListTest.php`. Cover:
- Unapproved supplier cannot be added to an RFQ
- Approved supplier can be added
- Suspended supplier is blocked
- ASL approval sets correct fields including review due date
- ASL status transitions follow correct sequence

**File:** New `tests/Feature/Suppliers/ApprovedSupplierListTest.php`

**Done when:** All tests pass

---

### T-3.12 — Create supplier sub-models (Onboarding Pack)

**Type:** BUILD | **Effort:** M | **Depends on:** T-3.6

**What to do:**
Create the four model files currently referenced but missing from `Supplier.php`:

**`SupplierDocument`** — fields: `supplier_id`, `document_type` (enum: `kra_pin_certificate`, `tax_compliance_certificate`, `bank_letter`, `business_registration`, `etims_registration`, `other`), `file_path`, `file_name`, `expiry_date`, `is_required`, `verified`, `verified_by`, `verified_at`

**`SupplierContact`** — fields: `supplier_id`, `name`, `role`, `email`, `phone`, `is_primary`

**`SupplierCategory`** — fields: `supplier_id`, `category_name`, `approved_at`, `is_active`

**`SupplierPerformanceReview`** — fields: `supplier_id`, `reviewed_by`, `review_period`, `delivery_score`, `quality_score`, `compliance_score`, `overall_score`, `comments`, `action_required`

Create migrations for each. Add `$fillable`, `$casts`, and `belongsTo(Supplier::class)` to each model.

**Files:**
- New models in `app/Modules/Suppliers/Models/`
- New migrations for each

**Done when:** All four models exist, migrations run, and `Supplier.php` relationships resolve without class-not-found error

---

### T-3.13 — Build onboarding completeness checker

**Type:** BUILD | **Effort:** S | **Depends on:** T-3.12

**What to do:**
In `SupplierService`, add `calculateOnboardingCompleteness(Supplier $supplier): array`. Returns:
```php
[
    'complete' => bool,
    'percentage' => int,
    'missing' => ['kra_pin_certificate', ...],   // required docs not yet uploaded
    'expired' => ['tax_compliance_certificate'],  // uploaded but past expiry_date
]
```
Required documents: `kra_pin_certificate`, `tax_compliance_certificate`, `bank_letter`, `business_registration`.

Gate `SupplierService::approveForASL()` on `completeness['complete'] === true`.

**File:** `app/Modules/Suppliers/Services/SupplierService.php`

**Done when:** Approving an incomplete supplier throws a descriptive error listing missing/expired documents

---

### T-3.14 — Build supplier onboarding views

**Type:** BUILD | **Effort:** M | **Depends on:** T-3.12, T-3.13

**What to do:**
Create `resources/views/suppliers/onboarding/`:
- `checklist.blade.php` — document checklist with upload button per required document, status badge (missing/uploaded/verified/expired), overall completeness percentage bar
- `upload.blade.php` — document upload form: document type, file, expiry date

Add document verification action to `SupplierASLController`: `POST /suppliers/{supplier}/documents/{document}/verify`

**Files:**
- New: `resources/views/suppliers/onboarding/`
- `app/Http/Controllers/SupplierASLController.php`
- `routes/web.php`

**Done when:** Procurement Manager can upload and verify supplier documents through the UI

---

### T-3.15 — Remove broken relationship stubs from Supplier model (interim fix)

**Type:** BUG | **Effort:** S | **No dependencies** (do immediately; T-3.12 replaces this)

**What to do:**
In `Supplier.php`, comment out or remove the four `hasMany` relationship methods that reference non-existent model classes (`SupplierDocument`, `SupplierContact`, `SupplierCategory`, `SupplierPerformanceReview`). This prevents a `Class not found` runtime error until the full models are built in T-3.12.

**File:** `app/Modules/Suppliers/Models/Supplier.php`

**Done when:** Loading a `Supplier` record does not throw a class-not-found error

---

### T-3.16 — Build CoI self-declaration controller and routes

**Type:** BUILD | **Effort:** M | **No dependencies**

**What to do:**
Create `app/Http/Controllers/ConflictOfInterestController.php`:
- `create(ProcurementProcess $process)` — show declaration form
- `store(Request $request, ProcurementProcess $process)` — save declaration
  - If `has_conflict = true`: remove user from evaluation panel, notify Procurement Manager via notification
  - If `has_conflict = false`: record clean declaration, allow access to evaluation views

Register routes:
```
GET  /procurement/{process}/coi-declaration
POST /procurement/{process}/coi-declaration
```

**Files:**
- New: `app/Http/Controllers/ConflictOfInterestController.php`
- `routes/web.php`

**Done when:** Panel members can submit declarations through the UI; conflicts result in automatic exclusion

---

### T-3.17 — Build CoI declaration view and gate bid evaluation

**Type:** BUILD | **Effort:** S | **Depends on:** T-3.16

**What to do:**
Create `resources/views/procurement/coi/declare.blade.php` — form with: process summary, yes/no radio for conflict, conditional text area for conflict details if yes, submit button.

In the bid evaluation view and `ProcurementService::evaluateBids()`, check that the authenticated user has a `ConflictOfInterestDeclaration` record for the process before allowing access. If not declared, redirect to the declaration form.

**Files:**
- New: `resources/views/procurement/coi/declare.blade.php`
- `app/Services/ProcurementService.php`
- Evaluation view (whichever blade handles evaluation)

**Done when:** Panel members without a declaration cannot access evaluation; declaration form shown instead

---

### T-3.18 — Add explicit payment chain guard to PaymentPolicy

**Type:** BUILD | **Effort:** S | **No dependencies**

**What to do:**
In `app/Policies/PaymentPolicy.php`, add a `create(User $user, $invoiceIds)` check. For each invoice ID in the set: verify the invoice has a linked GRN with `acceptance_status` in `['accepted', 'partially_accepted']`, and the GRN has a linked PO with `status` in `['approved', 'issued', 'acknowledged', 'fully_received', 'invoiced']`. Throw a policy denial with a message identifying which invoice failed and which link is broken.

In `PaymentService::create()`, add the same chain verification explicitly before any other logic.

**Files:**
- `app/Policies/PaymentPolicy.php`
- `app/Modules/Finance/Services/PaymentService.php`

**Done when:** Payment creation against an invoice without a GRN or PO is denied with a clear message

---

### T-3.19 — Write payment chain guard tests

**Type:** BUILD | **Effort:** S | **Depends on:** T-3.18

**What to do:**
Create `tests/Feature/Finance/PaymentChainGuardTest.php`. Cover:
- Payment creation succeeds when full chain exists (PO → GRN accepted → invoice)
- Payment blocked when invoice has no GRN
- Payment blocked when GRN is not accepted
- Payment blocked when PO is not approved
- Payment blocked when mixing invoices from different suppliers

**File:** New `tests/Feature/Finance/PaymentChainGuardTest.php`

**Done when:** All tests pass

---

## PHASE 4 — Connect Existing Backends to UI

---

### T-4.1 — Build AnnualProcurementPlan controller

**Type:** WIRE | **Effort:** M | **No dependencies**

**What to do:**
Create `app/Http/Controllers/AnnualProcurementPlanController.php` with actions: `index`, `create`, `store`, `show`, `edit`, `update`, `submit`, `approve`, `reject`. Wire to `AnnualProcurementPlanService` methods. Apply `AnnualProcurementPlanPolicy` for authorization. Register as resource route plus additional actions (`submit`, `approve`, `reject`) in `routes/web.php`.

**Files:**
- New: `app/Http/Controllers/AnnualProcurementPlanController.php`
- `routes/web.php`
- `app/Providers/AuthServiceProvider.php`

**Done when:** All routes resolve; service methods called correctly from controller

---

### T-4.2 — Build APP views

**Type:** WIRE | **Effort:** M | **Depends on:** T-4.1

**What to do:**
Create `resources/views/planning/`:
- `index.blade.php` — list APPs by fiscal year with status badge and action links
- `create.blade.php` — new APP form: fiscal year, description, and a dynamic line-item table (category, description, planned quarter, estimated value, sourcing method)
- `show.blade.php` — APP detail: header info, item list, approval status, history timeline
- `edit.blade.php` — edit draft APP (same layout as create, pre-filled)

Add "Annual Plan" link to sidebar navigation.

**Files:**
- New: `resources/views/planning/`
- `resources/views/layouts/partials/sidebar.blade.php`

**Done when:** Procurement Manager can create, submit, and view APPs through the UI

---

### T-4.3 — Build CAPA controller

**Type:** WIRE | **Effort:** M | **No dependencies**

**What to do:**
Create `app/Http/Controllers/CapaController.php` with actions matching the `CapaService` lifecycle: `index`, `create`, `store`, `show`, `submit`, `approve`, `reject`, `start`, `submitForVerification`, `verify`, `close`. Register resource route plus named action routes. Apply authorization via a new `CapaPolicy` or role checks.

Auto-trigger CAPA creation: in `GRNService::recordInspectionFailure()`, call `CapaService::createFromVariance()`. In `InvoiceService` when three-way match fails, do the same.

**Files:**
- New: `app/Http/Controllers/CapaController.php`
- `routes/web.php`
- `app/Services/GRNService.php`
- `app/Modules/Finance/Services/InvoiceService.php`

**Done when:** All routes resolve; CAPA auto-created on inspection failure and match failure

---

### T-4.4 — Build CAPA views

**Type:** WIRE | **Effort:** M | **Depends on:** T-4.3

**What to do:**
Create `resources/views/quality/capa/`:
- `index.blade.php` — CAPA register: list with status filter, date, assignee, source (GRN / invoice / manual)
- `create.blade.php` — new CAPA form: title, description, root cause, corrective action, preventive action, assignee, target date
- `show.blade.php` — CAPA detail: full history timeline of status changes and updates, current status, action buttons per state

Add "Quality / CAPA" link to sidebar.

**Files:**
- New: `resources/views/quality/capa/`
- `resources/views/layouts/partials/sidebar.blade.php`

**Done when:** Quality Officer can create, manage, and close CAPAs through the UI

---

### T-4.5 — Wire KPI dashboard to an HTTP endpoint

**Type:** WIRE | **Effort:** S | **No dependencies**

**What to do:**
In `app/Http/Controllers/ReportController.php`, add a `dashboard(Request $request)` method. Accept optional query params: `fiscal_year`, `date_from`, `date_to`. Call `KpiDashboardService::getDashboardData(...)`. Pass result to a new view. Register route: `GET /reports/dashboard`.

**Files:**
- `app/Http/Controllers/ReportController.php`
- `routes/web.php`

**Done when:** `GET /reports/dashboard` returns HTTP 200 and KPI data is passed to the view

---

### T-4.6 — Build KPI dashboard view

**Type:** WIRE | **Effort:** M | **Depends on:** T-4.5

**What to do:**
Create `resources/views/reports/dashboard.blade.php`. Display:
- Summary KPI cards: procurement cycle time, 3-way match compliance rate, eTIMS compliance rate, budget utilization, on-time delivery rate
- Exception summary: pending approvals past SLA, failed 3-way matches, non-compliant invoices
- Date range and fiscal year filter form at the top

Add "Dashboard" link as first item in sidebar.

**Files:**
- New: `resources/views/reports/dashboard.blade.php`
- `resources/views/layouts/partials/sidebar.blade.php`

**Done when:** KPI dashboard renders with real data from `KpiDashboardService`

---

## PHASE 5 — External Integrations

---

### T-5.1 — Add PesaPal credentials to config and env

**Type:** BUILD | **Effort:** S | **No dependencies**

**What to do:**
In `config/services.php`, add:
```php
'pesapal' => [
    'consumer_key'    => env('PESAPAL_CONSUMER_KEY'),
    'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
    'environment'     => env('PESAPAL_ENVIRONMENT', 'sandbox'),
    'sandbox_url'     => 'https://cybqa.pesapal.com/pesapalv3',
    'live_url'        => 'https://pay.pesapal.com/v3',
    'ipn_url'         => env('APP_URL') . '/payments/pesapal/callback',
],
```
Add the corresponding keys to `.env` (with empty values) and `.env.example`.

**Files:** `config/services.php`, `.env`, `.env.example`

**Done when:** `config('services.pesapal.consumer_key')` resolves correctly

---

### T-5.2 — Implement PesaPal API token fetch and HTTP call

**Type:** BUILD | **Effort:** L | **Depends on:** T-5.1

**What to do:**
In `PesapalGatewayService`, implement `callPesapalApi()`:
- Call `GET /api/Auth/RequestToken` with consumer key + secret to get a bearer token (cache for 5 minutes)
- Use the token as `Authorization: Bearer` on all subsequent calls
- Implement `registerIPN()` — register the school's IPN URL with PesaPal on first boot
- Implement `initiatePayment()` — call `POST /api/Transactions/SubmitOrderRequest` with amount, description, reference, callback URL
- Implement `checkPaymentStatus()` — call `GET /api/Transactions/GetTransactionStatus?orderTrackingId=`
- Store `order_tracking_id` and `merchant_reference` on `PaymentGatewayTransaction`

**File:** `app/Modules/Finance/Services/PesapalGatewayService.php`

**Done when:** With sandbox credentials, `initiatePayment()` returns a real PesaPal redirect URL; `checkPaymentStatus()` returns current status

---

### T-5.3 — Build PesaPal IPN webhook and reconciliation

**Type:** BUILD | **Effort:** M | **Depends on:** T-5.2

**What to do:**
Create `app/Http/Controllers/PesapalWebhookController.php`:
- `callback(Request $request)` — receives IPN notification from PesaPal; validates signature; calls `checkPaymentStatus()` to confirm; updates `Payment` record to `paid`; transitions payment workflow via `WorkflowEngine`; triggers `updateBudgetSpent()`
- Exempt from CSRF middleware in `Kernel.php`
- Register route: `POST /payments/pesapal/callback`
- Create `PesapalIpnJob` to process the webhook asynchronously

**Files:**
- New: `app/Http/Controllers/PesapalWebhookController.php`
- New: `app/Jobs/PesapalIpnJob.php`
- `routes/web.php`
- `app/Http/Kernel.php`

**Done when:** PesaPal can POST to the callback URL and payment status is updated in real time

---

### T-5.4 — Build PesaPal payment routes and views

**Type:** BUILD | **Effort:** M | **Depends on:** T-5.2

**What to do:**
- Add route: `POST /payments/{payment}/initiate` → Chief Accountant (Maker) initiates PesaPal payment
- Add route: `POST /payments/{payment}/pesapal/approve` → Procurement Manager (Checker) approves
- In payment `show.blade.php`, add PesaPal status section: current status, tracking ID, action buttons per role (initiate / approve / view status)
- Enforce SoD in controller: initiator cannot approve

**Files:**
- `app/Http/Controllers/PaymentController.php`
- `resources/views/finance/payments/show.blade.php`
- `routes/web.php`

**Done when:** Full 3-step PesaPal flow (Maker → Checker → Director) operable through the UI

---

### T-5.5 — Seed PaymentGatewayRole with correct SCIS role mapping

**Type:** BUILD | **Effort:** S | **Depends on:** T-5.1

**What to do:**
Create `database/seeders/PaymentGatewayRoleSeeder.php`. Seed the `payment_gateway_roles` table with:
- `initiator` → maps to `accountant` Spatie role
- `approver` → maps to `procurement-officer` Spatie role (PM/Checker)
- `processor` → maps to `principal` Spatie role (Director/Approver)
- `reconciler` → maps to `finance-manager` Spatie role
- `admin` → maps to `super-admin` Spatie role

Register seeder in `DatabaseSeeder.php`.

**File:** New `database/seeders/PaymentGatewayRoleSeeder.php`

**Done when:** `php artisan db:seed --class=PaymentGatewayRoleSeeder` populates the table correctly

---

### T-5.6 — Implement eTIMS invoice verification job

**Type:** BUILD | **Effort:** L | **Depends on:** T-1.1

**What to do:**
Create `app/Jobs/VerifyEtimsInvoiceJob.php`:
- Accepts a `SupplierInvoice` instance
- Calls KRA eTIMS API `POST /api/verify` with `etims_control_number`
- On success: sets `etims_verified = true`, `etims_verified_at`, `etims_qr_code` on the invoice
- On failure: logs the failure, sets `etims_verified = false`, dispatches retry after exponential backoff (up to 3 attempts per config)

In `InvoiceService::create()` or `InvoiceController::store()`, dispatch `VerifyEtimsInvoiceJob::dispatch($invoice)` immediately after invoice creation.

Update `PaymentService::validateEtimsCompliance()` to check `etims_verified = true`, not just the presence of `etims_control_number`.

**Files:**
- New: `app/Jobs/VerifyEtimsInvoiceJob.php`
- `app/Modules/Finance/Services/InvoiceService.php`
- `app/Modules/Finance/Services/PaymentService.php`

**Done when:** Invoice creation dispatches the job; job calls real KRA API (when `ETIMS_ENABLED=true`); payment blocked until `etims_verified = true`

---

### T-5.7 — Add eTIMS credentials to config and env

**Type:** BUILD | **Effort:** S | **Depends on:** T-1.1

**What to do:**
Confirm the merged `etims` config block (from T-1.1) contains `api_url`, `api_key`, `pin`. Add to `.env` and `.env.example`:
```
ETIMS_ENABLED=false
ETIMS_ENFORCE_ON_PAYMENT=true
ETIMS_API_URL=https://etims-sbx.kra.go.ke/api   # sandbox
ETIMS_API_KEY=
ETIMS_PIN=
```

**Files:** `.env`, `.env.example`

**Done when:** `config('procurement.etims.api_url')` resolves; `ETIMS_ENABLED=false` keeps job a no-op in development

---

## PHASE 6 — Test Coverage

---

### T-6.1 — Fix and verify existing test suite baseline

**Type:** BUG | **Effort:** S | **No dependencies**

**What to do:**
Run `php artisan test` and record current pass/fail count. Fix any pre-existing test failures unrelated to the tasks above (missing DB columns, wrong factory definitions, etc.) so Phase 1 work starts from a clean baseline.

**Done when:** `php artisan test` baseline is documented and all pre-existing failures are either fixed or explicitly noted as known failures

---

### T-6.2 — Add SoD enforcement tests for payment workflow

**Type:** BUILD | **Effort:** S | **No dependencies**

**What to do:**
In `tests/Feature/Workflows/PaymentSegregationOfDutiesTest.php`, add tests:
- The same user cannot prepare and verify a payment
- The same user cannot verify and approve a payment
- The same user cannot prepare and process a payment
- The CA role is required to initiate; PM role required to approve PesaPal step

**File:** `tests/Feature/Workflows/PaymentSegregationOfDutiesTest.php`

**Done when:** All new SoD tests pass

---

### T-6.3 — Verify audit immutability tests

**Type:** BUILD | **Effort:** S | **No dependencies**

**What to do:**
In `tests/Unit/Core/AuditServiceTest.php`, confirm tests cover: audit records cannot be updated after creation; audit records cannot be deleted; all workflow transitions create an audit entry. Add any missing coverage.

**File:** `tests/Unit/Core/AuditServiceTest.php`

**Done when:** Audit immutability and completeness are fully tested

---

## Task Summary by Phase

| Phase | Tasks | Type Breakdown |
|---|---|---|
| 1 — Core bugs | T-1.1 to T-1.8 (8 tasks) | 7 BUG, 1 TEST |
| 2 — Acceptance + GRN tracking | T-2.1 to T-2.8 (8 tasks) | 8 BUILD |
| 3 — Enforcement features | T-3.1 to T-3.19 (19 tasks) | 19 BUILD |
| 4 — Wire backends to UI | T-4.1 to T-4.6 (6 tasks) | 6 WIRE |
| 5 — External integrations | T-5.1 to T-5.7 (7 tasks) | 7 BUILD |
| 6 — Test coverage | T-6.1 to T-6.3 (3 tasks) | 3 TEST |
| **Total** | **51 tasks** | |

## Recommended Start Order

The following tasks have no dependencies and can be started immediately in parallel:

- T-1.1 (config fix)
- T-1.2 (method signature fix)
- T-1.4 (budget validation mock)
- T-2.1 (GRN acceptance migration)
- T-2.7 (GRN item tracking migration)
- T-3.1 (cash band config)
- T-3.6 (ASL migration)
- T-3.15 (supplier model stub removal — do today)
- T-3.16 (CoI declaration route)
- T-3.18 (payment policy guard)
- T-4.1 (APP controller)
- T-4.3 (CAPA controller)
- T-4.5 (KPI dashboard route)
- T-5.1 (PesaPal config)
- T-5.5 (gateway role seeder)
- T-5.7 (eTIMS env vars)
- T-6.1 (test baseline)
