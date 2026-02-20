<?php

namespace App\Services;

use App\Core\Audit\AuditService;
use App\Core\Rules\GovernanceRules;
use App\Core\Workflow\WorkflowEngine;
use App\Models\ProcurementProcess;
use App\Models\SupplierBid;
use App\Models\BidEvaluation;
use App\Models\ConflictOfInterestDeclaration;
use App\Modules\Suppliers\Models\Supplier;
use App\Modules\Suppliers\Services\SupplierService;
use Carbon\Carbon;
use Exception;

class ProcurementService
{
    public function __construct(
        private AuditService $auditService,
        private WorkflowEngine $workflowEngine,
        private GovernanceRules $governanceRules,
        private SupplierService $supplierService
    ) {}

    /**
     * Create a new RFQ (Request for Quote)
     */
    public function createRFQ(array $data, $user = null): ProcurementProcess
    {
        $amount = (float) ($data['budget_allocation'] ?? 0);
        if ($amount > 0) {
            $required = $this->governanceRules->getRequiredSourcingMethod($amount);
            if (in_array($required, ['rfq_formal', 'tender'])) {
                $band = $this->governanceRules->determineCashBand($amount);
                throw new Exception(
                    "This purchase value (KES " . number_format($amount, 2) . ") falls in the '{$band['label']}' band " .
                    "and requires a {$required} — an RFQ is not permitted."
                );
            }
        }

        $rfq = ProcurementProcess::create([
            'type' => 'rfq',
            'requisition_id' => $data['requisition_id'] ?? null,
            'title' => $data['process_name'] ?? $data['title'] ?? null,
            'description' => $data['description'],
            'budget_allocation' => $data['budget_allocation'] ?? null,
            'submission_deadline' => $data['quote_deadline'] ?? $data['submission_deadline'] ?? null,
            'evaluation_method' => 'price', // RFQ is typically price-based
            'status' => 'draft',
            'created_by' => $user?->id ?? auth()->id(),
        ]);

        // Add invited suppliers (ASL enforcement)
        $supplierIds = $data['invited_suppliers'] ?? $data['supplier_ids'] ?? [];
        if (!empty($supplierIds)) {
            $this->validateSupplierASL($supplierIds);
            $now = now();
            $pivotData = [];
            foreach ($supplierIds as $sid) {
                $pivotData[$sid] = ['invited_at' => $now];
            }
            $rfq->invitedSuppliers()->sync($pivotData);
        }

        try {
            // Initial state is 'draft', transition to 'rfq_issued' (example, adjust as needed)
            $this->workflowEngine->transition($rfq, 'ProcurementWorkflow', 'draft', 'rfq_issued');
        } catch (\Exception $e) {
            // Workflow is optional, continue if it fails
            \Log::info('Workflow transition skipped: ' . $e->getMessage());
        }

        return $rfq->fresh();
    }

    /**
     * Create a new RFP (Request for Proposal)
     */
    public function createRFP(array $data, $user = null): ProcurementProcess
    {
        $amount = (float) ($data['budget_allocation'] ?? 0);
        if ($amount > 0) {
            $required = $this->governanceRules->getRequiredSourcingMethod($amount);
            if ($required === 'tender') {
                $band = $this->governanceRules->determineCashBand($amount);
                throw new Exception(
                    "This purchase value (KES " . number_format($amount, 2) . ") falls in the '{$band['label']}' band " .
                    "and requires a formal tender — an RFP is not permitted."
                );
            }
        }

        $rfp = ProcurementProcess::create([
            'type' => 'rfp',
            'requisition_id' => $data['requisition_id'] ?? null,
            'title' => $data['process_name'] ?? $data['title'] ?? null,
            'description' => $data['description'],
            'budget_allocation' => $data['budget_allocation'] ?? null,
            'submission_deadline' => $data['proposal_deadline'] ?? $data['submission_deadline'] ?? null,
            'evaluation_method' => 'weighted', // RFP uses technical + financial scoring
            'technical_weight' => $data['technical_weight'] ?? 0.4,
            'financial_weight' => $data['financial_weight'] ?? 0.6,
            'status' => 'draft',
            'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
            'created_by' => $user?->id ?? auth()->id(),
        ]);

        $supplierIds = $data['invited_suppliers'] ?? $data['supplier_ids'] ?? [];
        if (!empty($supplierIds)) {
            $this->validateSupplierASL($supplierIds);
            $rfp->invitedSuppliers()->sync($supplierIds);
        }

        try {
            $this->workflowEngine->transition($rfp, 'ProcurementWorkflow', 'create_rfp');
        } catch (\Exception $e) {
            \Log::info('Workflow transition skipped: ' . $e->getMessage());
        }

        return $rfp->fresh();
    }

    /**
     * Create a Tender announcement
     */
    public function createTender(array $data, $user = null): ProcurementProcess
    {
        $tender = ProcurementProcess::create([
            'type' => 'tender',
            'requisition_id' => $data['requisition_id'] ?? null,
            'title' => $data['process_name'] ?? $data['title'] ?? null,
            'description' => $data['description'],
            'budget_allocation' => $data['budget_allocation'] ?? null,
            'submission_deadline' => $data['bid_deadline'] ?? $data['submission_deadline'] ?? null,
            'public_announcement' => $data['public_announcement'] ?? true,
            'evaluation_method' => 'weighted',
            'technical_weight' => $data['technical_weight'] ?? 0.3,
            'financial_weight' => $data['financial_weight'] ?? 0.7,
            'status' => 'draft',
            'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
            'created_by' => $user?->id ?? auth()->id(),
        ]);

        // Add invited suppliers (ASL enforcement)
        $supplierIds = $data['invited_suppliers'] ?? $data['supplier_ids'] ?? [];
        if (!empty($supplierIds)) {
            $this->validateSupplierASL($supplierIds);
            $tender->invitedSuppliers()->sync($supplierIds);
        }

        try {
            $this->workflowEngine->transition($tender, 'ProcurementWorkflow', 'create_tender');
        } catch (\Exception $e) {
            \Log::info('Workflow transition skipped: ' . $e->getMessage());
        }

        return $tender->fresh();
    }

    /**
     * Publish procurement process (draft → published)
     */
    public function publishProcess(ProcurementProcess $process): ProcurementProcess
    {
        // Set status based on process type
        $status = match($process->type) {
            'rfq' => 'rfq_issued',
            'rfp' => 'rfq_issued',  // or create separate rfp_issued status
            'tender' => 'rfq_issued',
            default => 'rfq_issued',
        };

        $process->update([
            'status' => $status,
            'issue_date' => now(),
        ]);

        try {
            $this->workflowEngine->transition($process, 'ProcurementWorkflow', 'publish');
        } catch (\Exception $e) {
            \Log::info('Workflow transition skipped: ' . $e->getMessage());
        }

        try {
            $this->auditService->log(
                action: 'PROCUREMENT_PROCESS_PUBLISHED',
                status: 'success',
                model_type: 'ProcurementProcess',
                model_id: $process->id,
                description: "Procurement process \"{$process->title}\" published for supplier bidding",
            );
        } catch (\Exception $e) {
            \Log::info('Audit log skipped: ' . $e->getMessage());
        }

        return $process->fresh();
    }

    /**
     * Record supplier bid submission
     */
    public function recordBidSubmission(ProcurementProcess $process, int $supplierId, array $bidData): SupplierBid
    {
        if (!in_array($process->status, ['rfq_issued', 'bids_received'])) {
            throw new \Exception('Bids can only be submitted for published processes');
        }

        if (now() > $process->submission_deadline) {
            throw new \Exception('Bid submission deadline has passed');
        }

        $bid = SupplierBid::create([
            'procurement_process_id' => $process->id,
            'supplier_id' => $supplierId,
            'bid_price' => $bidData['bid_price'],
            'bid_currency' => $bidData['bid_currency'] ?? 'KES',
            'technical_score' => $bidData['technical_score'] ?? null,
            'delivery_timeline' => $bidData['delivery_timeline'] ?? null,
            'payment_terms' => $bidData['payment_terms'] ?? null,
            'warranty_period' => $bidData['warranty_period'] ?? null,
            'submitted_at' => now(),
            'submission_notes' => $bidData['notes'] ?? null,
        ]);

        $this->auditService->log(
            action: 'SUPPLIER_BID_SUBMITTED',
            status: 'success',
            model_type: 'SupplierBid',
            model_id: $bid->id,
            description: "Bid submitted by supplier {$bid->supplier->name} for {$process->title}: KES " . number_format($bid->bid_price, 2),
        );

        return $bid;
    }

    /**
     * Evaluate bids (record scores and rankings)
     */
    public function evaluateBids(ProcurementProcess $process, array $evaluationScores): void
    {
        // Enforce Conflict of Interest check for evaluator
        $evaluatorId = auth()->id();
        $this->enforceConflictOfInterestCheck($process, $evaluatorId);

        // Enforce minimum quote count per cash band
        $amount = (float) ($process->budget_allocation ?? 0);
        if ($amount > 0) {
            $minQuotes = $this->governanceRules->getMinimumQuotes($amount);
            $bidCount  = $process->bids()->count();
            if ($minQuotes > 0 && $bidCount < $minQuotes) {
                $band = $this->governanceRules->determineCashBand($amount);
                throw new Exception(
                    "Evaluation blocked — {$minQuotes} quote(s) required for the '{$band['label']}' band; " .
                    "only {$bidCount} received."
                );
            }
        }

        $bids = $process->bids()->get();

        foreach ($evaluationScores as $bidId => $scores) {
            $bid = $bids->find($bidId);

            if (!$bid) {
                continue;
            }

            // Additional CoI check: ensure evaluator has no conflict with this specific supplier
            if (ConflictOfInterestDeclaration::hasConflict($evaluatorId, get_class($bid->supplier), $bid->supplier_id)) {
                throw new Exception(
                    "Evaluation blocked: You have declared a conflict of interest with supplier '{$bid->supplier->name}'. " .
                    "Please recuse yourself from this evaluation."
                );
            }

            // Calculate weighted score
            $weightedScore = 0;
            if ($process->type !== 'rfq') { // RFQ is price-only
                $technicalScore = $scores['technical_score'] ?? 0;
                $financialScore = $scores['financial_score'] ?? 0;

                $weightedScore = ($technicalScore * $process->technical_weight) +
                    ($financialScore * $process->financial_weight);
            } else {
                $weightedScore = $scores['financial_score'] ?? 0; // RFQ uses price only
            }

            // Record evaluation
            BidEvaluation::create([
                'supplier_bid_id' => $bid->id,
                'technical_score' => $scores['technical_score'] ?? null,
                'financial_score' => $scores['financial_score'] ?? null,
                'weighted_score' => $weightedScore,
                'evaluator_id' => auth()->id(),
                'evaluation_notes' => $scores['notes'] ?? null,
            ]);
        }

        $process->update(['status' => 'evaluated']);

        $this->workflowEngine->transition($process, 'ProcurementWorkflow', 'evaluate_bids');

        $this->auditService->log(
            action: 'BIDS_EVALUATED',
            status: 'success',
            model_type: 'ProcurementProcess',
            model_id: $process->id,
            description: "Bids evaluated for {$process->title}. {$process->bids->count()} bids scored.",
        );
    }

    /**
     * Award contract to winning supplier
     */
    public function awardContract(ProcurementProcess $process, int $winningBidId): void
    {
        $winningBid = $process->bids()->find($winningBidId);

        if (!$winningBid) {
            throw new \Exception('Winning bid not found');
        }

        $process->update([
            'status' => 'awarded',
            'awarded_supplier_id' => $winningBid->supplier_id,
            'awarded_amount' => $winningBid->bid_price,
            'awarded_date' => now(),
        ]);

        $process->bids()->update(['award_status' => 'unsuccessful']);
        $winningBid->update(['award_status' => 'successful']);

        $this->workflowEngine->transition($process, 'ProcurementWorkflow', 'award_contract');

        $this->auditService->log(
            action: 'CONTRACT_AWARDED',
            status: 'success',
            model_type: 'ProcurementProcess',
            model_id: $process->id,
            description: "Contract awarded to supplier {$winningBid->supplier->name} for KES " . number_format($winningBid->bid_price, 2),
        );
    }

    /**
     * Close procurement process
     */
    public function closeProcess(ProcurementProcess $process): ProcurementProcess
    {
        $process->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $this->workflowEngine->transition($process, 'ProcurementWorkflow', 'close');

        $this->auditService->log(
            action: 'PROCUREMENT_PROCESS_CLOSED',
            status: 'success',
            model_type: 'ProcurementProcess',
            model_id: $process->id,
            description: "Procurement process \"{$process->title}\" closed",
        );

        return $process->fresh();
    }

    /**
     * Get bid summary for evaluation
     */
    public function getBidSummary(ProcurementProcess $process): array
    {
        $bids = $process->bids()->with('supplier')->get();

        return [
            'process' => $process,
            'bid_count' => $bids->count(),
            'lowest_price' => $bids->min('bid_price'),
            'highest_price' => $bids->max('bid_price'),
            'average_price' => $bids->avg('bid_price'),
            'bids' => $bids->map(function ($bid) {
                return [
                    'supplier_name' => $bid->supplier->name,
                    'bid_price' => $bid->bid_price,
                    'evaluation' => $bid->evaluation?->weighted_score,
                    'status' => $bid->award_status ?? 'pending',
                ];
            }),
        ];
    }

    /**
     * Calculate best value (considering price and quality factors)
     */
    public function calculateBestValue(ProcurementProcess $process): int
    {
        $bids = $process->bids()->with('evaluations')->get();

        $bestValue = $bids->sortByDesc(function ($bid) {
            $evaluation = $bid->evaluation();
            return $evaluation?->weighted_score ?? 0;
        })->first();

        return $bestValue?->id ?? 0;
    }

    /**
     * Get all procurement processes with filters and pagination
     */
    public function getAllProcesses(string $type, array $filters = [], int $perPage = 15)
    {
        $query = ProcurementProcess::query()
            ->where('type', strtolower($type))
            ->with(['creator', 'requisition']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['requisition_id'])) {
            $query->where('requisition_id', $filters['requisition_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get all bids with filters and pagination
     */
    public function getAllBids(array $filters = [], int $perPage = 15)
    {
        $query = SupplierBid::query()
            ->with(['procurementProcess', 'supplier']);

        // Apply filters
        if (!empty($filters['process_id'])) {
            $query->where('procurement_process_id', $filters['process_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['process_type'])) {
            $query->whereHas('procurementProcess', function ($q) use ($filters) {
                $q->where('type', strtolower($filters['process_type']));
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Publish RFQ
     */
    public function publishRFQ(ProcurementProcess $process): ProcurementProcess
    {
        return $this->publishProcess($process);
    }

    /**
     * Publish RFP
     */
    public function publishRFP(ProcurementProcess $process): ProcurementProcess
    {
        return $this->publishProcess($process);
    }

    /**
     * Publish Tender
     */
    public function publishTender(ProcurementProcess $process): ProcurementProcess
    {
        return $this->publishProcess($process);
    }

    /**
     * Award tender to winning supplier
     */
    public function awardTender(ProcurementProcess $process, int $winningBidId, string $awardCriteria, $user): void
    {
        $this->awardContract($process, $winningBidId);
        
        // Log the award criteria
        $this->auditService->log(
            action: 'TENDER_AWARD_CRITERIA',
            status: 'success',
            model_type: 'ProcurementProcess',
            model_id: $process->id,
            description: "Award criteria: {$awardCriteria}",
        );
    }

    /**
     * Validate that all invited supplier IDs are on the Approved Supplier List.
     * Throws Exception listing any non-approved supplier names.
     */
    protected function validateSupplierASL(array $supplierIds): void
    {
        $blocked = Supplier::whereIn('id', $supplierIds)
            ->where('asl_status', '!=', 'approved')
            ->get();

        if ($blocked->isNotEmpty()) {
            $names = $blocked->map(fn($s) => $s->display_name ?? $s->business_name)->implode(', ');
            throw new Exception(
                "The following supplier(s) are not on the Approved Supplier List and cannot be invited: {$names}."
            );
        }
    }

    /**
     * Enforce Conflict of Interest check for procurement process evaluator
     * 
     * @throws Exception if evaluator has declared conflict of interest
     */
    protected function enforceConflictOfInterestCheck(ProcurementProcess $process, int $evaluatorId): void
    {
        $coiEnabled = config('procurement.governance.conflict_of_interest.enforce', true);
        
        if (!$coiEnabled) {
            return;
        }

        // Check if evaluator has declared conflict with the procurement process
        if (ConflictOfInterestDeclaration::hasConflict($evaluatorId, get_class($process), $process->id)) {
            throw new Exception(
                "Evaluation blocked: You have declared a conflict of interest with this procurement process. " .
                "Please recuse yourself from participating in this evaluation. " .
                "Contact your supervisor if you believe this is in error."
            );
        }

        // Check if evaluator has conflicts with any participating suppliers
        $supplierIds = $process->bids()->pluck('supplier_id')->toArray();
        
        foreach ($supplierIds as $supplierId) {
            if (ConflictOfInterestDeclaration::hasConflict($evaluatorId, 'App\\Modules\\Finance\\Models\\Supplier', $supplierId)) {
                $supplier = \App\Modules\Finance\Models\Supplier::find($supplierId);
                throw new Exception(
                    "Evaluation blocked: You have declared a conflict of interest with supplier '{$supplier->name}' " .
                    "who has submitted a bid for this procurement. You must recuse yourself from this evaluation."
                );
            }
        }

        // Log CoI check
        $this->auditService->log(
            action: 'COI_CHECK_PERFORMED',
            status: 'success',
            model_type: 'ProcurementProcess',
            model_id: $process->id,
            description: "Conflict of interest check passed for evaluator ID {$evaluatorId}",
        );
    }
}
