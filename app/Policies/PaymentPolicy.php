<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine if the user can view any payments
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payments.view');
    }

    /**
     * Determine if the user can view a specific payment
     */
    public function view(User $user, Payment $payment): bool
    {
        // Finance, procurement, and admin can view all payments
        if ($user->hasAnyRole(['Finance Manager', 'Procurement Officer', 'Super Administrator', 'Super Administrator'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create a payment (draft)
     */
    /**
     * Role/permission check only — the full PO + GRN + acceptance chain guard
     * is enforced in PaymentService::validatePaymentChain(), which throws a
     * descriptive Exception when the chain is broken.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Finance Manager', 'Accountant', 'Super Administrator', 'Super Administrator'])
            && $user->hasPermission('payments.create');
    }

    /**
     * Determine if the user can update a draft payment
     */
    public function update(User $user, Payment $payment): bool
    {
        // Can only update draft payments
        if ($payment->status !== 'draft') {
            return false;
        }

        return $user->hasAnyRole(['Finance Manager', 'Super Administrator', 'Super Administrator']);
    }

    /**
     * Determine if the user can submit a payment (draft → pending_approval)
     */
    public function submit(User $user, Payment $payment): bool
    {
        if ($payment->status !== 'draft') {
            return false;
        }

        return $user->hasAnyRole(['Finance Manager', 'Super Administrator', 'Super Administrator']);
    }

    /**
     * Determine if the user can approve a payment (pending_approval → approved)
     * CRITICAL: Current user MUST be different from submitter (segregation of duties)
     */
    public function approve(User $user, Payment $payment): bool
    {
        if ($payment->status !== 'pending_approval') {
            return false;
        }

        // Segregation of duties: approver must be different from submitter
        if ($payment->submitted_by === $user->id) {
            return false;
        }

        // Check approval authority (based on payment amount and user's approval limit)
        $userApprovalLimit = $user->approval_limit ?? 0;
        if ($payment->total_amount > $userApprovalLimit && !$user->hasRole('Super Administrator')) {
            return false;
        }

        return $user->hasAnyRole(['Finance Manager', 'Super Administrator', 'Super Administrator'])
            && $user->hasPermission('payments.approve');
    }

    /**
     * Determine if the user can reject a payment (pending_approval → rejected)
     * Current user must be different from submitter
     */
    public function reject(User $user, Payment $payment): bool
    {
        if ($payment->status !== 'pending_approval') {
            return false;
        }

        // Segregation of duties: rejector must be different from submitter
        if ($payment->submitted_by === $user->id) {
            return false;
        }

        return $user->hasAnyRole(['Finance Manager', 'Super Administrator', 'Super Administrator']);
    }

    /**
     * Determine if the user can process a payment (approved → processed)
     * Different user from approver (triple segregation of duties)
     */
    public function process(User $user, Payment $payment): bool
    {
        if ($payment->status !== 'approved') {
            return false;
        }

        // Triple segregation of duties: processor must differ from submitter AND approver
        if ($payment->submitted_by === $user->id || $payment->approved_by === $user->id) {
            return false;
        }

        return $user->hasAnyRole(['Finance Manager', 'Super Administrator', 'Super Administrator'])
            && $user->hasPermission('payments.process');
    }

    /**
     * Determine if the user can record payment as reconciled (processed → reconciled)
     */
    public function reconcile(User $user, Payment $payment): bool
    {
        if ($payment->status !== 'processed') {
            return false;
        }

        return $user->hasAnyRole(['Finance Manager', 'Super Administrator', 'Super Administrator']);
    }

    /**
     * Determine if the user can delete a draft payment
     */
    public function delete(User $user, Payment $payment): bool
    {
        // Can only delete draft payments with no transactions
        if ($payment->status !== 'draft') {
            return false;
        }

        return $user->hasRole('Super Administrator');
    }

    /**
     * Determine if the user can view WHT certificate
     */
    public function viewWHTCertificate(User $user, Payment $payment): bool
    {
        return $user->hasAnyRole(['Finance Manager', 'Procurement Officer', 'Super Administrator', 'Super Administrator'])
            && $payment->has_wht;
    }

    /**
     * Determine if the user can download WHT certificate
     */
    public function downloadWHTCertificate(User $user, Payment $payment): bool
    {
        return $this->viewWHTCertificate($user, $payment);
    }

    /**
     * Determine if the user can view payment reconciliation
     */
    public function viewReconciliation(User $user, Payment $payment): bool
    {
        return $user->hasAnyRole(['Finance Manager', 'Super Administrator', 'Super Administrator']);
    }

    /**
     * Determine if the user can view payment approval chain
     */
    public function viewApprovalChain(User $user, Payment $payment): bool
    {
        return $this->view($user, $payment);
    }
}
