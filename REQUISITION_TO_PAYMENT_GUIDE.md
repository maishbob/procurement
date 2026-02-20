
# Requisition to Payment — Step-by-Step System Guide

**Kenya School Procurement, Inventory & Finance Governance System**
*Technical operator guide for all roles involved in the procurement cycle*

---

## Table of Contents

1. [Prerequisites — Roles Needed](#1-prerequisites--roles-needed)
2. [Approval Thresholds at a Glance](#2-approval-thresholds-at-a-glance)
3. [Phase 1 — Requisition](#phase-1--requisition)
   - [Step 1 · Create a Requisition](#step-1--create-a-requisition)
   - [Step 2 · Submit the Requisition](#step-2--submit-the-requisition)
   - [Step 3 · HOD Approval](#step-3--hod-approval)
   - [Step 4 · Budget Review & Approval](#step-4--budget-review--approval)
   - [Step 5 · Executive Head Approval (high-value)](#step-5--principal-approval-high-value)
4. [Phase 2 — Sourcing & Purchase Order](#phase-2--sourcing--purchase-order)
   - [Step 6 · Procurement Officer Picks Up the Requisition](#step-6--procurement-officer-picks-up-the-requisition)
   - [Step 7 · Evaluate Bids and Award](#step-7--evaluate-bids-and-award)
   - [Step 8 · Create the Purchase Order](#step-8--create-the-purchase-order)
   - [Step 9 · Issue the PO to the Supplier](#step-9--issue-the-po-to-the-supplier)
5. [Phase 3 — Goods Receipt](#phase-3--goods-receipt)
   - [Step 10 · Create a Goods Received Note (GRN)](#step-10--create-a-goods-received-note-grn)
   - [Step 11 · Inspection](#step-11--inspection)
   - [Step 12 · Post GRN to Inventory](#step-12--post-grn-to-inventory)
   - [Step 13 · Department Acceptance (MANDATORY)](#step-13--department-acceptance-mandatory)
6. [Phase 4 — Invoice & Payment](#phase-4--invoice--payment)
   - [Step 14 · Create and Verify Invoice](#step-14--create-and-verify-invoice)
   - [Step 15 · Create the Payment](#step-15--create-the-payment)
   - [Step 16 · Submit the Payment](#step-16--submit-the-payment)
   - [Step 17 · Verify the Payment](#step-17--verify-the-payment)
   - [Step 18 · Approve the Payment](#step-18--approve-the-payment)
   - [Step 19 · Process (Pay) the Payment](#step-19--process-pay-the-payment)
7. [Complete Status Flow Reference](#complete-status-flow-reference)
8. [Quick-Reference: Who Does What](#quick-reference-who-does-what)
9. [Compliance Controls Summary](#compliance-controls-summary)

---

## 1. Prerequisites — Roles Needed

The full procurement cycle requires **at least four distinct users** due to mandatory segregation of duties. The same person cannot occupy more than one of the roles below on the same transaction.

| Role | System Slug | What They Do in This Workflow |
|---|---|---|
| Staff or HOD | `staff` / `hod` | Creates the requisition |
| Head of Department | `hod` | Approves at HOD level (different person from requester) |
| Budget Owner | `budget-owner` | Reviews and approves budget commitment |
| Executive Head | `principal` | Approves high-value requests (>= KES 200,000) |
| Procurement Officer | `procurement-officer` | Runs sourcing, creates and issues Purchase Orders |
| Stores Manager | `stores-manager` | Creates GRN, inspects goods, posts to inventory |
| Accountant | `accountant` | Creates and verifies invoices; creates payment drafts |
| Finance Manager | `finance-manager` | Approves and processes payments |

> **Segregation of duties is enforced by the system and cannot be bypassed.** Use separate user accounts at each step.

---

## 2. Approval Thresholds at a Glance

Approval tiers are determined automatically by the requisition's estimated total.

| Amount (KES) | Sourcing Method | Approvers Required | Min Quotes |
|---|---|---|---|
| Up to 50,000 | Spot buy | HOD | 1 |
| 50,001 – 250,000 | RFQ | HOD + Budget Owner + Executive Head | 3 |
| 250,001 – 1,000,000 | Formal RFQ | HOD + Budget Owner + Executive Head | 3 |
| 1,000,001 – 5,000,000 | Tender | Board | Full tender |
| Above 5,000,000 | Strategic Tender | Board | Full tender |

---

## Phase 1 — Requisition

### Step 1 · Create a Requisition

**Who:** Any staff member or HOD
**URL:** `GET /requisitions/create`

1. Click **Requisitions → New Requisition** in the sidebar.
2. Fill in the form:
   - **Title** — describe what you need (e.g. "Stationery for Term 1")
   - **Department** — select your department
   - **Budget Line** — select an approved budget line (must have `approved` status and available funds)
   - **Priority** — `low`, `normal`, `high`, or `emergency`
   - **Required Date** — when items are needed by
   - **Items** — add each line item: description, quantity, unit price
   - **Justification** — required business justification
   - **Supporting Documents** — attach any supporting files (PDF or image, max 5 MB each)
3. Click **Save as Draft** (status = `draft`) to save without submitting, or **Submit for Approval** to submit immediately (status = `submitted`).
4. A requisition number is generated automatically: `REQ-YYYYMM-NNNN`.

> The system reads the estimated total and automatically determines which approval tiers apply. No manual configuration is needed.

---

### Step 2 · Submit the Requisition

**Who:** The requester (the person who created the draft)
**URL:** `GET /requisitions/{id}` — click **Submit for Approval**

1. Open your draft requisition.
2. Review all items and totals carefully — the form locks after submission.
3. Click **Submit for Approval**.
4. Status changes: `draft → submitted`.
5. Designated approvers are notified by email.

---

### Step 3 · HOD Approval

**Who:** The Head of Department of the requester's department — must be a **different person** from the requester
**URL:** `GET /requisitions/{id}`

1. Log in as the HOD.
2. Navigate to **Requisitions** and filter by status `submitted`.
3. Open the requisition. Review the items, estimated cost, and justification.
4. Click **Approve** or **Reject**.
   - If approving: confirm and save.
   - If rejecting: a reason of at least 10 characters is required. The requester is notified and can revise and resubmit.
5. Status changes: `submitted → hod_review → hod_approved`.

---

### Step 4 · Budget Review & Approval

**Who:** A user with the `budget-owner` role
**Permission required:** `requisitions.approve-budget`
**URL:** `GET /requisitions/{id}`

1. Log in as the Budget Owner.
2. The system checks automatically: `available budget = allocated − committed − spent`.
3. If funds are sufficient, click **Approve Budget**.
4. Status changes: `hod_approved → budget_review → budget_approved`.
5. The requested amount is **ring-fenced (committed)** on the budget line so it cannot be used elsewhere.

> If `BUDGET_OVERRUN_ALLOWED=false` (the default), the system blocks approval and displays an error when available funds are insufficient. The Finance Manager or Budget Owner must resolve the shortfall before the workflow can continue.

---

### Step 5 · Executive Head Approval (high-value)

**Who:** A user with the `principal` role
**Applies when:** Estimated total >= KES 200,000
**URL:** `GET /requisitions/{id}`

1. Log in as the Executive Head.
2. Open the requisition (it appears in the approvals queue on the dashboard).
3. Review all supporting documentation.
4. Click **Approve** or **Reject**.
5. Status remains `budget_approved` after this tier.

---

## Phase 2 — Sourcing & Purchase Order

### Step 6 · Procurement Officer Picks Up the Requisition

**Who:** `procurement-officer`
**URL:** `GET /requisitions` — filter by status `budget_approved`

1. Open the approved requisition.
2. Status moves: `budget_approved → procurement_queue → sourcing`.
3. Based on the total amount, conduct sourcing using the appropriate method:

| Amount | Method | URL |
|---|---|---|
| Up to KES 50,000 | Spot buy (1 quote) | Direct to PO creation |
| KES 50,001 – 250,000 | RFQ (3 quotes min) | `POST /procurement/rfq` |
| KES 250,001 – 1,000,000 | Formal RFQ (3 quotes min) | `POST /procurement/rfq` |
| Above KES 1,000,000 | Full tender | `POST /procurement/tender` |

4. Create the RFQ or tender, linking it to the requisition. Status moves to `quoted` when bids are received.

> If any evaluation committee member has a connection to a supplier, they must file a **Conflict of Interest declaration** at `/procurement/{id}/coi-declaration` before evaluation begins. Declared conflicts automatically exclude that member.

---

### Step 7 · Evaluate Bids and Award

**Who:** `procurement-officer`
**URL:** `POST /procurement/bids/{id}/evaluation` then `POST /procurement/tender/{id}/award`

1. Record bid evaluation scores for each supplier (price, quality, delivery, compliance).
2. Select the winning supplier.
3. Submit the award decision.
4. Status changes: `quoted → evaluated → awarded`.

---

### Step 8 · Create the Purchase Order

**Who:** `procurement-officer`
**Permission required:** `purchase-orders.create`
**URL:** `GET /purchase-orders/create` (append `?requisition_id={id}` to pre-fill)

1. Go to **Purchase Orders → New PO**.
2. Select the approved requisition.
3. Select the awarded supplier. The supplier must be:
   - `is_approved = true` on the Approved Supplier List (ASL)
   - Not blacklisted
   - Have a valid KRA PIN on file
4. Confirm line items, unit prices, delivery terms, and delivery address.
5. Save — a PO number is generated: `PO-YYYYMM-NNNN`.
6. PO status: `draft`. Requisition status moves to `po_created`.

---

### Step 9 · Issue the PO to the Supplier

**Who:** `procurement-officer` whose `approval_limit >= PO total_amount`
**URL:** `GET /purchase-orders/{id}` — click **Approve** then **Issue**

1. Open the draft PO.
2. Click **Approve** (status: `draft → approved`).
3. Click **Issue to Supplier** (status: `approved → issued`).
4. The supplier receives an automated email with the PO PDF attached.
5. A PDF copy is available for internal records via **Download PDF**.

---

## Phase 3 — Goods Receipt

### Step 10 · Create a Goods Received Note (GRN)

**Who:** `stores-manager` (or `procurement-officer`)
**URL:** `GET /grn/create` (append `?po_id={id}` to pre-fill from the PO)

Do this when the supplier delivers the goods to the school stores.

1. Go to **GRN → Create GRN**.
2. Select the issued Purchase Order.
3. For each line item record:
   - **Quantity Received**
   - **Condition:** `good`, `damaged`, or `expired`
   - Batch number and expiry date if applicable
4. Record any discrepancies (shortages, overages, damaged items) in the discrepancy fields.
5. Submit the GRN.
6. Status changes: `draft → submitted → inspection_pending`.

---

### Step 11 · Inspection

**Who:** `stores-manager` or `quality-inspector`
**URL:** `GET /grn/{id}` — click **Record Inspection**

1. Open the GRN in `inspection_pending` status.
2. Record the inspection result for each item.
3. Click the appropriate outcome:
   - **Pass Inspection** → all items acceptable → `inspection_passed`
   - **Partial Acceptance** → some items acceptable → `partial_acceptance`
   - **Fail Inspection** → all items rejected → `inspection_failed` (terminal — goods returned to supplier, new delivery required)

---

### Step 12 · Post GRN to Inventory

**Who:** `stores-manager`
**URL:** `GET /grn/{id}` — click **Post to Inventory**

1. After inspection passes (or partial acceptance is recorded), click **Post to Inventory**.
2. Stock levels update automatically in the inventory module.
3. Status changes: `inspection_passed → approved → posted → completed`.

---

### Step 13 · Department Acceptance (MANDATORY)

**Who:** The requester or HOD (the department that raised the requisition)
**URL:** `GET /grn/{id}/accept`

> This step is **required before any payment can be processed**. The payment chain guard enforces a strict "No Acceptance = No Pay" rule. If this step is skipped, payment creation will fail.

1. The requesting department physically inspects what was delivered against what was ordered.
2. Open the GRN and click **Accept Delivery**.
3. Complete the acceptance form:
   - **Acceptance Decision:** `accepted` or `partially_accepted`
   - **Acceptance Notes** — record any observations
   - **Completion Certificate** — optional upload (PDF or image, max 10 MB)
4. Click **Confirm Acceptance**.
5. GRN `acceptance_status` is set to `accepted` (or `partially_accepted`).

**If the department rejects delivery:**
`POST /grn/{id}/reject-acceptance` — goods must be returned to the supplier and a new delivery arranged before the workflow can continue.

---

## Phase 4 — Invoice & Payment

### Step 14 · Create and Verify Invoice

**Who (create):** `accountant`
**Who (verify):** `finance-manager` — must be a different person from the accountant who created it
**URL (create):** `GET /invoices/create`

1. Log in as the accountant.
2. Go to **Finance → Invoices → New Invoice**.
3. Link the invoice to the Purchase Order.
4. Enter the supplier's invoice number, invoice date, and line items exactly as shown on the supplier's document.
5. The system performs an automatic **three-way match** check:
   - Invoice quantities vs GRN quantities
   - Invoice amounts vs PO amounts
   - All three must agree within a **2% tolerance**
   - If they do not match, the invoice is flagged and cannot proceed to payment until the discrepancy is resolved
6. If `ETIMS_ENABLED=true`: the invoice must carry a valid **KRA eTIMS control number** (`etims_verified = true`) before it can be approved for payment.
7. Submit the invoice for verification.
8. Log in as the Finance Manager and open the invoice.
9. Click **Verify Invoice**.
10. Invoice status: `draft → submitted → verified`.

---

### Step 15 · Create the Payment

**Who:** `finance-manager` or `accountant`
**URL:** `GET /payments/create` (append `?invoice_id={id}` to pre-fill)

1. Go to **Finance → Payments → New Payment**.
2. Select the verified invoice.
3. The system runs the **Payment Chain Guard** before allowing creation. It will block payment if:
   - There is no linked Purchase Order in a valid status
   - There is no linked GRN
   - The GRN has not been accepted by the department (Step 13 must be complete)
4. Review the payment details:
   - **Gross Amount** — total from the invoice
   - **WHT (Withholding Tax)** — auto-calculated if the supplier is flagged as `subject_to_wht = true`:

     | Payment Type | WHT Rate |
     |---|---|
     | Services | 5% |
     | Professional Fees | 5% |
     | Training | 5% |
     | Management Fees | 2% |
     | Rent | 10% |
     | Dividends | 5% |
     | Interest | 15% |

   - **Net Amount** = Gross − WHT (this is what the supplier receives)
5. Select payment method (bank transfer, cheque, EFT, etc.).
6. Save — status: `draft`.

---

### Step 16 · Submit the Payment

**Who:** The person who created the payment (Step 15)
**URL:** `GET /payments/{id}` — click **Submit for Verification**

1. Review the payment one final time — check the supplier bank details, amounts, and invoice reference.
2. Click **Submit for Verification**.
3. Status changes: `draft → submitted → verification_pending`.

---

### Step 17 · Verify the Payment

**Who:** A **different** `accountant` or `finance-manager` — must NOT be the person who created or submitted the payment
**URL:** `GET /payments/{id}` — click **Verify**

> The system checks the audit log. If the current user already appears in a prior step for this payment record, the action is blocked.

1. Cross-check the payment against the invoice, GRN, and PO.
2. Verify the supplier bank account details against the Approved Supplier List.
3. Click **Verify**.
4. Status changes: `verification_pending → verified`.

---

### Step 18 · Approve the Payment

**Who:** `finance-manager` with `approval_limit >= payment amount` — must be a **different person** from the creator and the verifier
**Permission required:** `payments.approve`
**URL:** `GET /payments/{id}` — click **Approve**

1. Review the complete payment record including the three-way match summary and WHT calculations.
2. Click **Approve**.
3. Status changes: `verified → approval_pending → approved`.

---

### Step 19 · Process (Pay) the Payment

**Who:** A **fourth distinct** `finance-manager` — must be different from the creator, verifier, AND approver
**Permission required:** `payments.process`
**URL:** `GET /payments/{id}` — click **Process Payment**

> This is the final segregation-of-duties check. The `GovernanceRules::enforceSegregationOfDuties()` engine reads the complete audit log for this payment. If any person appears in more than one role in the chain, the action is blocked with an exception.

1. Enter the payment reference number (bank transfer reference, cheque number, EFT batch number, etc.).
2. Click **Process Payment**.
3. Status changes: `approved → payment_processing → paid → completed`.

**What happens automatically on completion:**

- Invoice(s) linked to this payment are marked as `paid` (or `partially_paid` for partial payments)
- Budget line updated: committed amount moves to `spent_amount` via `BudgetService::recordExpenditure()`
- WHT certificate generated automatically if WHT was deducted — reference number: `WHT-YYYY-NNNNN` — downloadable at `/payments/{id}/download-wht-certificate`
- Supplier and finance team notified by email (`payment-processed` notification)
- Full audit trail entry written with timestamp (Africa/Nairobi timezone) — retained for 7 years, immutable

---

## Complete Status Flow Reference

```
REQUISITION
  draft → submitted → hod_review → hod_approved → budget_review → budget_approved
        → procurement_queue → sourcing → quoted → evaluated → awarded → po_created → completed
  Any stage: → rejected (requester notified, can revise and resubmit)
  Any stage: → cancelled (terminal)

PURCHASE ORDER
  draft → approved → issued → acknowledged
        → partially_received → fully_received → invoiced → payment_approved → paid → closed
  Any stage: → rejected → draft (can be revised)
  Any stage: → cancelled (terminal)

GRN
  draft → submitted → inspection_pending → inspection_passed → approved → posted → completed
                                         → partial_acceptance → approved → posted → completed
                                         → inspection_failed → rejected (terminal)
  + acceptance_status: accepted / partially_accepted / acceptance_rejected

PAYMENT
  draft → submitted → verification_pending → verified
        → approval_pending → approved → payment_processing → paid → completed
  failed → approval_pending (can retry)
  rejected → draft (can revise)
  cancelled (terminal)
```

---

## Quick-Reference: Who Does What

| Step | Actor | URL |
|---|---|---|
| 1. Create requisition | Staff / HOD | `/requisitions/create` |
| 2. Submit requisition | Staff / HOD (same person) | `/requisitions/{id}` |
| 3. HOD approval | HOD (different person) | `/requisitions/{id}` |
| 4. Budget approval | Budget Owner | `/requisitions/{id}` |
| 5. Executive Head approval | Executive Head | `/requisitions/{id}` |
| 6. Start sourcing / create RFQ | Procurement Officer | `/procurement/rfq` |
| 7. Evaluate bids and award | Procurement Officer | `/procurement/bids/{id}/evaluation` |
| 8. Create Purchase Order | Procurement Officer | `/purchase-orders/create` |
| 9. Issue PO to supplier | Procurement Officer | `/purchase-orders/{id}` |
| 10. Create GRN | Stores Manager | `/grn/create` |
| 11. Inspect GRN | Stores Manager / QC | `/grn/{id}` |
| 12. Post to inventory | Stores Manager | `/grn/{id}` |
| 13. Departmental acceptance | Requester / HOD | `/grn/{id}/accept` |
| 14a. Create invoice | Accountant | `/invoices/create` |
| 14b. Verify invoice | Finance Manager | `/invoices/{id}` |
| 15. Create payment | Finance Manager / Accountant | `/payments/create` |
| 16. Submit payment | Creator (same as step 15) | `/payments/{id}` |
| 17. Verify payment | Different Accountant / FM | `/payments/{id}` |
| 18. Approve payment | Finance Manager (3rd person) | `/payments/{id}` |
| 19. Process payment | Finance Manager (4th person) | `/payments/{id}` |

---

## Compliance Controls Summary

These controls are enforced automatically by the system and **cannot be overridden** by any user action.

| Control | What It Prevents | Where Enforced |
|---|---|---|
| Self-approval prohibited | Requester cannot approve their own requisition | `RequisitionService::approve()` |
| Approver not buyer | The person who approved cannot raise the PO | `GovernanceRules::validateApproverNotBuyer()` |
| Buyer not receiver | The person who created the PO cannot receive the GRN | `GovernanceRules::validateBuyerNotReceiver()` |
| Payment SoD chain | Creator ≠ Verifier ≠ Approver ≠ Processor on the same payment | `PaymentPolicy` + `GovernanceRules::enforceSegregationOfDuties()` |
| Three-way match | Invoice must match PO + GRN within 2% tolerance | `GovernanceRules::validateThreeWayMatch()` + `SupplierInvoiceObserver` |
| No-PO-No-Pay | No payment without a valid linked Purchase Order | `PaymentService::validatePaymentChain()` |
| No-GRN-No-Pay | No payment without a completed Goods Received Note | `PaymentService::validatePaymentChain()` |
| No-Acceptance-No-Pay | No payment unless the department accepted delivery | `PaymentService::validatePaymentChain()` |
| Budget overrun blocked | Requests that exceed available budget are blocked | `GovernanceRules::validateBudgetAvailability()` |
| Audit trail immutable | Every action is permanently logged; records cannot be edited or deleted | `AuditService` (7-year retention) |
| Supplier compliance | Blacklisted or non-compliant suppliers cannot be awarded | `GovernanceRules::validateSupplierCompliance()` |
| Emergency procurement cap | Emergency fast-track capped at KES 100,000 | `GovernanceRules::validateEmergencyProcurement()` |
| eTIMS compliance | If enabled, invoices must have a KRA control number before payment | `PaymentService::validateEtimsCompliance()` |

---

*Last updated: February 20, 2026*
*System: Kenya School Procurement, Inventory & Finance Governance System*
