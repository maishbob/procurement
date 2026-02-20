# Kenya School Procurement, Inventory & Finance Governance System

## User Guide

This guide provides an overview and step-by-step instructions for users of the Kenya School Procurement, Inventory & Finance Governance System. The system enforces compliance with Kenya procurement law (PPADA), KRA tax rules, and institutional financial governance for educational institutions.

---

## Table of Contents

1. Introduction
2. User Roles & Permissions
3. Key Modules & Features
4. Common Workflows
5. Budget Controls
6. Compliance & Audit
7. Frequently Asked Questions (FAQ)
8. Support

---

## 1. Introduction

This system is designed to manage procurement, inventory, and finance processes in compliance with Kenyan regulations. It is not a generic CRUD app, but a governance platform for educational institutions.

---

## 2. User Roles & Permissions

Roles determine access and actions within the system. Common roles include:

- Super Admin
- Executive Head
- Finance Manager
- Procurement Officer
- Stores Manager
- Head of Department (HOD)
- Budget Owner
- Staff
- Auditor
- Accountant

Permissions are enforced via policies. Segregation of duties is mandatory: the requester, approver, buyer, receiver, and payment processor must be different users.

---

## 3. Key Modules & Features

- **Requisitions:** Create, submit, and track purchase requests through multi-stage approval workflows.
- **Purchase Orders:** Manage and issue purchase orders to suppliers.
- **Finance:** Handle payments, invoices, and tax compliance (VAT, WHT, eTIMS planned).
- **Suppliers:** Register and evaluate suppliers.
- **GRN (Goods Received Notes):** Record and verify received goods.
- **Inventory:** Manage stock, stores, and inventory transactions.
- **Planning:** Annual procurement planning (in progress).
- **Quality:** Corrective and Preventive Actions (CAPA).
- **Reporting:** Access dashboards and compliance reports.

---

## 4. Common Workflows

### Requisition Workflow

1. Draft → Submitted
2. HOD Review → HOD Approved
3. Budget Review → Budget Approved
4. Procurement Queue → Sourcing → Quoted → Evaluated → Awarded
5. PO Created → Completed
6. (Rejected/Cancelled possible at any stage)

### Payment Workflow

1. Draft → Submitted
2. Verification Pending → Verified
3. Approval Pending → Approved
4. Payment Processing → Paid → Completed

All transitions are logged and must go through the workflow engine.

---

## 5. Budget Controls

- Budget lines use commitment accounting: available = allocated - committed - spent.
- System blocks requests that exceed available budget (unless overrun is allowed).
- Approval thresholds:
  - HOD: up to KES 50,000
  - Executive Head: up to KES 200,000
  - Board: above KES 1,000,000

---

## 6. Compliance & Audit

- All actions are logged for 7 years (immutable audit trail).
- Three-way matching: PO, GRN, and Invoice must align within 2% tolerance for payments.
- Tax calculations (VAT 16%, WHT by category) are automatic.
- All workflow and financial actions are subject to compliance checks.

---

## 7. Frequently Asked Questions (FAQ)

**Q: How do I reset my password?**
A: Use the "Forgot Password" link on the login page or contact your system administrator.

**Q: Why can't I approve my own requisition?**
A: The system enforces segregation of duties; you cannot approve your own requests.

**Q: What if my budget is insufficient?**
A: The system will block the request. Contact your finance manager or budget owner.

---

## 8. Support

For technical support or questions, contact your institution's IT administrator or the system support team.

---

_Last updated: February 20, 2026_

---

## 9. Step-by-Step Instructions

### Submitting a Requisition

1. Log in to the system.
2. Navigate to the "Requisitions" module.
3. Click "Create New Requisition".
4. Fill in the required details (item, quantity, justification, etc.).
5. Attach supporting documents if needed.
6. Click "Submit". The requisition will move to HOD review.

### Approving a Purchase Order (PO)

1. Go to the "Purchase Orders" module.
2. Review pending POs assigned to your role.
3. Click on a PO to view details and supporting documents.
4. Approve or reject the PO with comments.

### Recording a Goods Received Note (GRN)

1. Open the "GRN" module.
2. Select the relevant PO.
3. Enter received quantities and any discrepancies.
4. Attach delivery notes or inspection reports.
5. Save the GRN. The system will update inventory and trigger next workflow steps.

---

## 10. Troubleshooting & Tips

- **Cannot log in:** Check your username/password. Use "Forgot Password" or contact admin.
- **Action button disabled:** You may lack the required role or the item is not in the correct workflow state.
- **Budget error:** Ensure sufficient funds are available in the relevant budget line.
- **Document upload fails:** Check file size/type and your internet connection.
- **Approval not possible:** Segregation of duties may prevent you from approving your own request.

---

## 11. Glossary of Key Terms

- **Requisition:** A formal request to purchase goods or services.
- **PO (Purchase Order):** An official order issued to a supplier.
- **GRN (Goods Received Note):** Document confirming receipt of goods.
- **Budget Line:** A specific allocation of funds for a purpose.
- **Segregation of Duties:** Ensuring different people perform requesting, approving, buying, and receiving.
- **Three-Way Matching:** Matching PO, GRN, and Invoice before payment.
- **Audit Trail:** Record of all actions for compliance and review.

---

## 12. Data Security & User Responsibilities

- Never share your password or login credentials.
- Log out after each session, especially on shared computers.
- Report suspicious activity or unauthorized access immediately.
- Only upload documents relevant to procurement or finance processes.
- Follow your institution’s IT and data protection policies.

---

## 13. Additional Frequently Asked Questions (FAQ)

**Q: Can I delegate my approval authority?**
A: Only users with the appropriate role can approve. Delegation must be set up by an administrator if allowed.

**Q: How do I view the audit trail for a transaction?**
A: Open the relevant record and click the "Audit Trail" or "History" tab to see all actions taken.

**Q: What happens if I make a mistake in a requisition?**
A: If the requisition is still in draft or review, you can edit or cancel it. After approval, contact your procurement officer.

**Q: How do I update my profile or contact details?**
A: Go to your user profile/settings and make the necessary changes. Some fields may require admin approval.

**Q: Who do I contact for urgent support?**
A: Refer to the Support section or your institution’s IT helpdesk.

---

## 14. Contact & Escalation Procedures

- For routine issues, contact your department administrator or IT helpdesk.
- For urgent procurement or finance issues, escalate to the finance manager or Executive Head.
- For system outages or data security incidents, notify IT support immediately.

---

_End of User Guide_
