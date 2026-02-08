# Kenya School Procurement, Inventory & Finance System

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![Laravel](https://img.shields.io/badge/Laravel-10.x-red)
![PHP](https://img.shields.io/badge/PHP-8.1+-purple)
![License](https://img.shields.io/badge/license-Proprietary-yellow)

**Digital Institutional Governance Infrastructure**

A comprehensive, compliance-first procurement, inventory, and financial control system designed specifically for educational institutions in Kenya, with built-in support for KRA compliance, eTIMS integration readiness, WHT calculations, and VAT handling.

---

## 🎯 Purpose

This is **not a generic web application**. This is **governance infrastructure** that enforces:

- ✅ Process control and workflow discipline
- ✅ Segregation of duties
- ✅ Approval hierarchies
- ✅ Financial integrity
- ✅ Budget controls
- ✅ Three-way matching (PO + GRN + Invoice)
- ✅ Immutable audit trails
- ✅ Kenya tax compliance (VAT, WHT, KRA)
- ✅ Institutional accountability

---

## 🇰🇪 Kenya-Specific Features

### Tax & Compliance

- **KRA PIN Validation**: Mandatory supplier KRA PIN capture and validation
- **WHT (Withholding Tax)**: Automatic calculation at payment (5% services, 2% management, etc.)
- **VAT Handling**: 16% VAT with support for exempt and zero-rated items
- **eTIMS Integration Ready**: Fields for eTIMS control numbers, invoice references, QR codes
- **Tax Compliance Certificates**: Track supplier tax compliance certificate expiry

### Currency Management

- **Base Currency**: KES (Kenyan Shilling)
- **Multi-Currency Support**: USD, GBP, EUR
- **Exchange Rate Locking**: Rates locked at transaction time
- **FX Variance Tracking**: Monitor exchange rate impacts

### Regulatory Alignment

- Procurement thresholds aligned with public procurement best practices
- Quotation requirements (minimum 3 quotes above threshold)
- Tender process for large procurements
- Emergency procurement controls with retrospective approval

---

## 🏗️ System Architecture

### Architecture Style

**Modular Monolith** - Process-driven, governance-first design

### Core Modules

```
/app
  /Modules
    ├── Users              - User management, roles
    ├── Roles              - RBAC system
    ├── Requisitions       - Purchase requisitions workflow
    ├── Approvals          - Multi-level approval engine
    ├── Procurement        - RFQ, RFP, Tender management
    ├── Suppliers          - Supplier registry (KRA compliant)
    ├── PurchaseOrders     - PO creation and management
    ├── Receiving          - GRN and quality inspection
    ├── Inventory          - Stock management and control
    ├── Finance            - Invoices, payments, WHT
    ├── Reports            - Comprehensive reporting
    └── Admin              - System administration

  /Core
    ├── Audit              - Immutable audit logging
    ├── Workflow           - State machine engine
    ├── Rules              - Governance rules engine
    ├── TaxEngine          - VAT and WHT calculations
    ├── CurrencyEngine     - Multi-currency support
    ├── ComplianceEngine   - Kenya compliance logic
    └── Notifications      - Email and SMS alerts
```

---

## 🔐 Security & Governance

### Segregation of Duties

The system **enforces** that:

- Requester ≠ Approver
- Approver ≠ Buyer
- Buyer ≠ Receiver
- Receiver ≠ Payment Processor

### Three-Way Match

**No payment** is allowed unless:

1. Purchase Order exists
2. Goods Received Note (GRN) exists
3. Supplier Invoice matches both PO and GRN (within tolerance)

### Approval Hierarchy

Automatic routing based on amount thresholds:

- **< 50,000 KES**: HOD approval
- **50,000 - 200,000 KES**: HOD + Budget Owner
- **200,000 - 1,000,000 KES**: HOD + Budget Owner + Principal
- **> 1,000,000 KES**: HOD + Budget Owner + Principal + Board

### Immutable Audit Logs

Every action is logged with:

- User identity
- Timestamp
- Action performed
- State before/after
- Justification (where required)
- IP address and metadata

**Audit logs cannot be edited or deleted** (database-enforced immutability)

---

## 📊 Complete Workflow

### Procurement Workflow

```
Requisition → HOD Approval → Budget Approval → Procurement Queue
    ↓
RFQ/RFP/Tender → Supplier Bids → Evaluation → Award Approval
    ↓
Purchase Order → PO Approval → Issue to Supplier → Acknowledge
    ↓
Delivery → Inspection → GRN → Post to Inventory
    ↓
Supplier Invoice → Three-Way Match → Invoice Approval
    ↓
Payment Processing → WHT Deduction → Payment Approval → Payment Execution
    ↓
Closure → Audit Trail Complete
```

### Inventory Workflow

```
GRN Posted → Stock Updated → Issue to Department
    ↓
Stock Movement Logged → Reorder Point Monitoring
    ↓
Cycle Counts → Adjustments (Approved) → Asset Register Updated
```

---

## 👥 User Roles

| Role                    | Primary Responsibilities                   |
| ----------------------- | ------------------------------------------ |
| **Super Administrator** | System configuration, user management      |
| **Principal**           | High-value approvals, strategic oversight  |
| **Finance Manager**     | Payment processing, budget management      |
| **Accountant**          | Invoice verification, WHT management       |
| **Procurement Officer** | Sourcing, PO creation, supplier management |
| **Stores Manager**      | Receiving, inventory management            |
| **Head of Department**  | Department requisition approval            |
| **Budget Owner**        | Budget allocation approval                 |
| **Staff**               | Create requisitions                        |
| **Auditor**             | Read-only access to all records            |

---

## 🚀 Key Features

### Requisition Management

- Multi-level approval workflows
- Budget validation before approval
- Emergency procurement support
- Single-source procurement (with justification)
- Attachment support

### Procurement & Sourcing

- RFQ, RFP, Tender processes
- Supplier bid collection and evaluation
- Weighted evaluation criteria
- Conflict of interest declarations
- Award recommendation and approval

### Purchase Orders

- System-generated PO numbers
- Immutable once issued
- Partial and full delivery tracking
- Currency and exchange rate locking
- Acknowledgement tracking

### Goods Receiving

- GRN with inspection workflow
- Quality acceptance/rejection
- Partial delivery support
- Discrepancy tracking
- Photo attachment support

### Inventory Management

- Multi-store support
- Real-time stock levels
- Min/max/reorder point alerts
- Stock issues to departments
- Cycle counting and adjustments
- Asset register for capital items

### Financial Management

- Invoice three-way matching
- Automatic WHT calculation
- VAT handling per line item
- Payment approval workflow
- WHT certificate generation
- Budget utilization tracking

### Reporting

- Spend analysis
- Budget vs Actual
- Supplier performance
- Procurement cycle times
- Compliance reports
- Audit trails
- Tax reports (VAT, WHT)

---

## 🛠️ Technology Stack

- **Framework**: Laravel 10.x
- **PHP**: 8.1+
- **Database**: MySQL 8.0
- **Frontend**: Blade Templates, Tailwind CSS
- **Authentication**: Laravel Auth + Custom RBAC
- **Queue**: Database queue driver
- **Cache**: File/Redis
- **PDF Generation**: DomPDF
- **Excel Export**: Maatwebsite Excel

---

## 📦 Installation

See [DEPLOYMENT.md](DEPLOYMENT.md) for complete installation and deployment instructions.

### Quick Start (Development)

```bash
# Clone repository
git clone [repository-url]
cd procurement

# Install dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_DATABASE=procurement_db
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed roles and permissions
php artisan db:seed --class=RolesAndPermissionsSeeder

# Start development server
php artisan serve
```

---

## 📋 Configuration

### Kenya-Specific Settings

Edit `/config/procurement.php`:

```php
'currency' => [
    'default' => 'KES',
    'supported' => ['KES', 'USD', 'GBP', 'EUR'],
],

'tax' => [
    'vat' => ['default_rate' => 16],
    'wht' => [
        'rates' => [
            'services' => 5,
            'professional_fees' => 5,
            'management_fees' => 2,
            // ... more rates
        ],
    ],
],

'thresholds' => [
    'hod_approval' => 50000,
    'principal_approval' => 200000,
    'board_approval' => 1000000,
    'tender_required' => 500000,
],
```

---

## 🔍 Usage Examples

### Creating a Requisition

```php
use App\Modules\Requisitions\Services\RequisitionService;

$requisitionService = new RequisitionService();

$requisition = $requisitionService->create([
    'department_id' => 5,
    'budget_line_id' => 12,
    'requested_by' => auth()->id(),
    'title' => 'Office Stationery',
    'justification' => 'Monthly supplies for admin department',
    'required_by_date' => '2026-03-15',
    'items' => [
        [
            'description' => 'A4 Paper (Ream)',
            'quantity' => 50,
            'unit_of_measure' => 'ream',
            'estimated_unit_price' => 450,
        ],
        // ... more items
    ],
]);
```

### Processing a Payment with WHT

```php
use App\Core\TaxEngine\TaxEngine;
use App\Modules\Finance\Services\PaymentService;

$taxEngine = new TaxEngine();
$paymentService = new PaymentService();

// Calculate WHT
$taxCalc = $taxEngine->calculateComprehensive(
    baseAmount: 100000,
    includeVAT: true,
    vatType: 'vatable',
    includeWHT: true,
    whtType: 'professional_fees'
);

// Result:
// gross_amount: 116,000 (100k + 16% VAT)
// wht_amount: 5,800 (5% of gross)
// net_payable: 110,200

$payment = $paymentService->create([
    'supplier_id' => $supplier->id,
    'gross_amount' => $taxCalc['gross_amount'],
    'wht_amount' => $taxCalc['wht']['amount'],
    'net_amount' => $taxCalc['net_payable'],
    // ... other fields
]);
```

---

## 🧪 Testing

```bash
# Run tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Generate coverage report
php artisan test --coverage
```

---

## 📊 Database Schema

### Core Tables

- `users`, `roles`, `permissions` - RBAC
- `departments`, `cost_centers`, `budget_lines` - Organization
- `audit_logs`, `audit_logs_archive` - Immutable audit trail
- `exchange_rates`, `locked_exchange_rates` - Currency

### Procurement Tables

- `requisitions`, `requisition_items`, `requisition_approvals`
- `procurement_processes`, `supplier_bids`, `bid_evaluations`
- `purchase_orders`, `purchase_order_items`

### Inventory Tables

- `inventory_items`, `stock_levels`, `stock_transactions`
- `goods_received_notes`, `grn_items`
- `stock_issues`, `stock_adjustments`

### Finance Tables

- `supplier_invoices`, `supplier_invoice_items`
- `payments`, `payment_invoices`, `payment_approvals`
- `wht_certificates`, `budget_transactions`

### Supplier Tables

- `suppliers`, `supplier_documents`, `supplier_performance_reviews`
- `supplier_blacklist_history`

---

## 🔄 Workflow States

### Requisition States

```
draft → submitted → hod_review → hod_approved → budget_review
→ budget_approved → procurement_queue → sourcing → quoted
→ evaluated → awarded → po_created → completed
```

### Purchase Order States

```
draft → submitted → approved → issued → acknowledged
→ [partially_received | fully_received] → invoiced
→ payment_approved → paid → closed
```

### Payment States

```
draft → submitted → verification_pending → verified
→ approval_pending → approved → payment_processing
→ paid → completed
```

---

## 📖 Documentation

- **[DEPLOYMENT.md](DEPLOYMENT.md)**: Complete deployment guide
- **[ARCHITECTURE.md](ARCHITECTURE.md)**: System architecture details
- `/docs/API.md`: API documentation (Phase 2)
- `/docs/USER_GUIDE.md`: End-user documentation (Phase 2)

---

## 🤝 Support

For technical support:

- **System Issues**: Contact IT Administrator
- **Process Questions**: Contact Procurement Manager
- **Financial Queries**: Contact Finance Manager

---

## 📝 License

Proprietary - School Internal Use Only

---

## 🎯 Roadmap

### Phase 1 (Current)

- ✅ Core procurement workflow
- ✅ Inventory management
- ✅ Financial controls
- ✅ Kenya compliance foundation
- ✅ Audit logging

### Phase 2 (Planned)

- [ ] eTIMS API integration
- [ ] SMS notification integration (Africa's Talking)
- [ ] Advanced reporting dashboards
- [ ] Mobile app for approvals
- [ ] Accounting system integration
- [ ] Supplier portal

### Phase 3 (Future)

- [ ] AI-powered spend analysis
- [ ] Predictive inventory management
- [ ] OCR for invoice processing
- [ ] Blockchain-based audit trail
- [ ] Multi-school deployment support

---

## ⚠️ Important Notes

### This is NOT

- ❌ A generic e-commerce platform
- ❌ A simple CRUD application
- ❌ A UI-first system
- ❌ A shortcut/workaround tool

### This IS

- ✅ Institutional governance infrastructure
- ✅ Policy enforcement system
- ✅ Compliance automation
- ✅ Audit-ready by design
- ✅ Process discipline enforcer

---

## 💡 Design Philosophy

> "Governance over convenience"  
> "Process over pages"  
> "Auditability over speed"  
> "Control over aesthetics"  
> "Integrity over flexibility"

This system prioritizes **institutional control** and **compliance** above user convenience. Every shortcut removed is a control gained. Every approval level is accountability enforced.

---

**Built for Kenya. Built for Schools. Built for Governance.**

`v1.0.0 | February 2026 | School Procurement System`
