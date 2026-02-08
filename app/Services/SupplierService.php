<?php

namespace App\Services;

use App\Core\Audit\AuditService;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierDocument;
use App\Models\SupplierPerformanceReview;
use App\Models\SupplierBlacklistHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierService
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Create a new supplier with complete onboarding workflow
     */
    public function createSupplier(array $data): Supplier
    {
        $supplier = Supplier::create([
            'name' => $data['name'],
            'kra_pin' => $data['kra_pin'],
            'tax_file_number' => $data['tax_file_number'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city'],
            'country' => $data['country'] ?? 'Kenya',
            'supplier_category_id' => $data['supplier_category_id'],
            'tax_compliance_status' => 'pending',
            'is_approved' => false,
            'is_blacklisted' => false,
        ]);

        // Create supplier contacts
        if (!empty($data['contacts'])) {
            foreach ($data['contacts'] as $contact) {
                SupplierContact::create([
                    'supplier_id' => $supplier->id,
                    'name' => $contact['name'],
                    'email' => $contact['email'],
                    'phone' => $contact['phone'],
                    'department' => $contact['department'] ?? null,
                ]);
            }
        }

        // Store tax compliance documents
        if (!empty($data['tax_compliance_documents'])) {
            foreach ($data['tax_compliance_documents'] as $document) {
                SupplierDocument::create([
                    'supplier_id' => $supplier->id,
                    'document_type' => $document['type'],
                    'file_path' => $document['path'],
                    'expires_at' => $document['expires_at'] ?? null,
                ]);
            }
        }

        return $supplier->fresh();
    }

    /**
     * Update supplier information (draft only)
     */
    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        $supplier->update([
            'name' => $data['name'] ?? $supplier->name,
            'email' => $data['email'] ?? $supplier->email,
            'phone' => $data['phone'] ?? $supplier->phone,
            'address' => $data['address'] ?? $supplier->address,
            'tax_compliance_status' => $data['tax_compliance_status'] ?? $supplier->tax_compliance_status,
        ]);

        return $supplier->fresh();
    }

    /**
     * Approve supplier after compliance verification
     */
    public function approveSupplier(Supplier $supplier, string $notes = null): Supplier
    {
        $supplier->update([
            'is_approved' => true,
            'approval_notes' => $notes,
            'approved_at' => now(),
        ]);

        $this->auditService->log(
            action: 'SUPPLIER_APPROVED',
            status: 'success',
            model_type: 'Supplier',
            model_id: $supplier->id,
            description: "Supplier {$supplier->name} approved for procurement",
        );

        return $supplier->fresh();
    }

    /**
     * Blacklist supplier due to compliance/performance issues
     */
    public function blacklistSupplier(Supplier $supplier, string $reason): Supplier
    {
        $supplier->update([
            'is_blacklisted' => true,
            'blacklist_reason' => $reason,
            'blacklisted_at' => now(),
        ]);

        // Record blacklist history
        SupplierBlacklistHistory::create([
            'supplier_id' => $supplier->id,
            'reason' => $reason,
            'blacklisted_by' => auth()->id(),
            'blacklist_date' => now(),
        ]);

        $supplier->fireModelEvent('blacklisted');

        return $supplier->fresh();
    }

    /**
     * Unblacklist supplier (restore to active status)
     */
    public function unblacklistSupplier(Supplier $supplier): Supplier
    {
        $supplier->update([
            'is_blacklisted' => false,
            'blacklisted_at' => null,
        ]);

        SupplierBlacklistHistory::create([
            'supplier_id' => $supplier->id,
            'reason' => 'Unblacklist - restoration',
            'blacklisted_by' => auth()->id(),
            'blacklist_date' => now(),
        ]);

        $supplier->fireModelEvent('unblacklisted');

        return $supplier->fresh();
    }

    /**
     * Record supplier performance review
     */
    public function recordPerformanceReview(Supplier $supplier, array $data): SupplierPerformanceReview
    {
        return SupplierPerformanceReview::create([
            'supplier_id' => $supplier->id,
            'review_period' => $data['review_period'],
            'on_time_delivery_percent' => $data['on_time_delivery_percent'],
            'quality_rating' => $data['quality_rating'],
            'compliance_rating' => $data['compliance_rating'],
            'communication_rating' => $data['communication_rating'],
            'overall_rating' => ($data['quality_rating'] + $data['compliance_rating'] + $data['communication_rating']) / 3,
            'notes' => $data['notes'] ?? null,
            'reviewed_by' => auth()->id(),
        ]);
    }

    /**
     * Get suppliers with filtering and pagination
     */
    public function getAllSuppliers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Supplier::query();

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'approved') {
                $query->where('is_approved', true)->where('is_blacklisted', false);
            } elseif ($filters['status'] === 'blacklisted') {
                $query->where('is_blacklisted', true);
            } elseif ($filters['status'] === 'pending') {
                $query->where('is_approved', false)->where('is_blacklisted', false);
            }
        }

        if (!empty($filters['category_id'])) {
            $query->where('supplier_category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('kra_pin', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['tax_compliance'])) {
            $query->where('tax_compliance_status', $filters['tax_compliance']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get supplier performance metrics
     */
    public function getPerformanceMetrics(Supplier $supplier): array
    {
        $reviews = $supplier->performanceReviews()->latest()->limit(5)->get();

        return [
            'average_rating' => $reviews->avg('overall_rating') ?? 0,
            'average_on_time_delivery' => $reviews->avg('on_time_delivery_percent') ?? 0,
            'total_transactions' => $supplier->purchaseOrders()->count(),
            'total_spent' => $supplier->invoices()->sum('total_amount'),
            'payment_performance' => $this->calculatePaymentPerformance($supplier),
            'compliance_status' => $supplier->tax_compliance_status,
            'blacklist_status' => $supplier->is_blacklisted ? 'blacklisted' : 'active',
        ];
    }

    /**
     * Calculate supplier's payment performance (on-time payment percentage)
     */
    private function calculatePaymentPerformance(Supplier $supplier): float
    {
        $payments = $supplier->payments()->whereNotNull('processed_at')->get();

        if ($payments->isEmpty()) {
            return 100;
        }

        $onTimeCount = $payments->filter(function ($payment) {
            return $payment->processed_at <= $payment->due_date;
        })->count();

        return ($onTimeCount / $payments->count()) * 100;
    }

    /**
     * Verify supplier tax compliance
     */
    public function verifyTaxCompliance(Supplier $supplier): bool
    {
        $documents = $supplier->documents()->whereNotNull('expires_at')->get();

        foreach ($documents as $document) {
            if ($document->expires_at < now()) {
                $supplier->update(['tax_compliance_status' => 'expired']);
                return false;
            }
        }

        $supplier->update(['tax_compliance_status' => 'compliant']);
        return true;
    }

    /**
     * Upload supplier document
     */
    public function uploadDocument(Supplier $supplier, string $documentType, string $filePath, ?\DateTime $expiresAt = null): SupplierDocument
    {
        return SupplierDocument::create([
            'supplier_id' => $supplier->id,
            'document_type' => $documentType,
            'file_path' => $filePath,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Check if supplier can transact
     */
    public function canTransact(Supplier $supplier): bool
    {
        return $supplier->is_approved
            && !$supplier->is_blacklisted
            && $supplier->tax_compliance_status === 'compliant';
    }
}
