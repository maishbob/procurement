<?php

namespace App\Modules\Suppliers\Services;

use App\Core\Audit\AuditService;
use App\Models\User;
use App\Modules\Suppliers\Models\Supplier;
use Carbon\Carbon;
use Exception;

class SupplierService
{
    // Required documents for onboarding approval
    const REQUIRED_DOCUMENTS = [
        'kra_pin_certificate',
        'tax_compliance_certificate',
        'bank_letter',
        'business_registration',
    ];

    public function __construct(private AuditService $auditService) {}

    // -------------------------------------------------------------------------
    // Approved Supplier List (ASL)
    // -------------------------------------------------------------------------

    public function submitForASLReview(Supplier $supplier): Supplier
    {
        if (!in_array($supplier->asl_status, ['not_applied', 'removed'])) {
            throw new Exception(
                "Supplier '{$supplier->display_name}' cannot be submitted for review — current ASL status: {$supplier->asl_status}."
            );
        }

        $supplier->update(['asl_status' => 'pending_review']);

        $this->auditService->log(
            action: 'ASL_REVIEW_SUBMITTED',
            model: Supplier::class,
            modelId: $supplier->id,
            newValues: [
                'description' => "Supplier '{$supplier->display_name}' submitted for ASL review."
            ]
        );

        return $supplier->fresh();
    }

    public function approveForASL(Supplier $supplier, User $approver, array $categories = []): Supplier
    {
        if ($supplier->asl_status !== 'pending_review') {
            throw new Exception(
                "Supplier '{$supplier->display_name}' must be in 'pending_review' status before ASL approval."
            );
        }

        $completeness = $this->calculateOnboardingCompleteness($supplier);
        if (!$completeness['complete']) {
            $missing  = implode(', ', $completeness['missing']);
            $expired  = implode(', ', $completeness['expired']);
            $problems = array_filter([$missing ? "Missing: {$missing}" : null, $expired ? "Expired: {$expired}" : null]);
            throw new Exception(
                "Cannot approve supplier '{$supplier->display_name}' — onboarding incomplete. " .
                    implode('; ', $problems) . '.'
            );
        }

        $supplier->update([
            'asl_status'       => 'approved',
            'asl_approved_at'  => now(),
            'asl_approved_by'  => $approver->id,
            'asl_review_due_at' => now()->addYear(),
            'asl_categories'   => $categories ?: $supplier->asl_categories,
            'onboarding_status' => 'approved',
        ]);

        $this->auditService->log(
            action: 'ASL_APPROVED',
            model: Supplier::class,
            modelId: $supplier->id,
            newValues: [
                'description' => "Supplier '{$supplier->display_name}' approved for ASL by {$approver->name}."
            ]
        );

        return $supplier->fresh();
    }

    public function suspendFromASL(Supplier $supplier, string $reason): Supplier
    {
        if ($supplier->asl_status !== 'approved') {
            throw new Exception("Only approved suppliers can be suspended from the ASL.");
        }

        $supplier->update(['asl_status' => 'suspended']);

        $this->auditService->log(
            action: 'ASL_SUSPENDED',
            model: Supplier::class,
            modelId: $supplier->id,
            newValues: [
                'description' => "Supplier '{$supplier->display_name}' suspended from ASL. Reason: {$reason}"
            ]
        );

        return $supplier->fresh();
    }

    public function removeFromASL(Supplier $supplier, string $reason): Supplier
    {
        $supplier->update(['asl_status' => 'removed']);

        $this->auditService->log(
            action: 'ASL_REMOVED',
            model: Supplier::class,
            modelId: $supplier->id,
            newValues: [
                'description' => "Supplier '{$supplier->display_name}' removed from ASL. Reason: {$reason}"
            ]
        );

        return $supplier->fresh();
    }

    public function isApprovedSupplier(Supplier $supplier): bool
    {
        return $supplier->asl_status === 'approved';
    }

    // -------------------------------------------------------------------------
    // Onboarding Completeness
    // -------------------------------------------------------------------------

    /**
     * Calculate onboarding document completeness.
     *
     * Returns:
     *   complete    — true only when all required docs are present, verified, and not expired
     *   percentage  — 0–100
     *   missing     — required doc types with no uploaded file
     *   expired     — uploaded docs whose expiry_date is in the past
     */
    public function calculateOnboardingCompleteness(Supplier $supplier): array
    {
        $documents = $supplier->documents()->get()->keyBy('document_type');

        $missing = [];
        $expired = [];

        foreach (self::REQUIRED_DOCUMENTS as $docType) {
            if (!isset($documents[$docType])) {
                $missing[] = $docType;
                continue;
            }

            $doc = $documents[$docType];
            if ($doc->isExpired()) {
                $expired[] = $docType;
            }
        }

        $total      = count(self::REQUIRED_DOCUMENTS);
        $problems   = count($missing) + count($expired);
        $percentage = $total > 0 ? (int) round((($total - $problems) / $total) * 100) : 0;

        return [
            'complete'   => empty($missing) && empty($expired),
            'percentage' => $percentage,
            'missing'    => $missing,
            'expired'    => $expired,
        ];
    }

    /**
     * Verify a supplier document (Procurement Manager action).
     */
    public function verifyDocument(
        \App\Modules\Suppliers\Models\SupplierDocument $document,
        User $verifier
    ): \App\Modules\Suppliers\Models\SupplierDocument {
        $document->update([
            'verified'    => true,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);

        return $document->fresh();
    }
}
