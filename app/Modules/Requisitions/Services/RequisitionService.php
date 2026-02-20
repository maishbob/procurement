<?php

namespace App\Modules\Requisitions\Services;

use App\Models\User;
use App\Modules\Requisitions\Models\Requisition;
use App\Modules\Requisitions\Models\RequisitionApproval;
use App\Modules\Requisitions\Models\RequisitionItem;
use App\Core\Audit\AuditService;
use App\Core\Workflow\WorkflowEngine;
use App\Core\Rules\GovernanceRules;
use App\Core\CurrencyEngine\CurrencyEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Carbon\Carbon;

/**
 * Requisition Service Layer
 * 
 * Business logic for requisition management
 * Enforces governance rules and workflow controls
 */
class RequisitionService
{
    protected AuditService $auditService;
    protected WorkflowEngine $workflowEngine;
    protected GovernanceRules $governanceRules;
    protected CurrencyEngine $currencyEngine;

    public function __construct(
        AuditService $auditService,
        WorkflowEngine $workflowEngine,
        GovernanceRules $governanceRules,
        CurrencyEngine $currencyEngine
    ) {
        $this->auditService = $auditService;
        $this->workflowEngine = $workflowEngine;
        $this->governanceRules = $governanceRules;
        $this->currencyEngine = $currencyEngine;
    }

    /**
     * Create a new requisition
     */
    public function create(array $data): Requisition
    {
        return DB::transaction(function () use ($data) {
            // Generate requisition number
            $data['requisition_number'] = $this->generateRequisitionNumber();
            $data['status'] = 'draft';
            $data['requested_by'] = $data['requested_by'] ?? Auth::id();
            $data['created_by'] = Auth::id();

            // Convert to base currency if needed
            if (($data['currency'] ?? 'KES') !== 'KES') {
                $rate = $this->currencyEngine->getExchangeRate($data['currency'], 'KES');
                $data['exchange_rate'] = $rate;
                $data['estimated_total_base'] = $data['estimated_total'] * $rate;
            } else {
                $data['estimated_total_base'] = $data['estimated_total'];
            }

            // Determine approval requirements
            $data = $this->setApprovalRequirements($data);

            // Create requisition
            $requisition = Requisition::create($data);

            // Create line items
            if (!empty($data['items'])) {
                $this->createLineItems($requisition, $data['items']);
            }

            // Audit log
            $this->auditService->logCreate(
                Requisition::class,
                $requisition->id,
                $requisition->toArray(),
                ['module' => 'requisitions']
            );

            return $requisition->load('items');
        });
    }

    /**
     * Update requisition (only in draft state)
     */
    public function update(Requisition $requisition, array $data): Requisition
    {
        if (!$requisition->isEditable()) {
            throw new Exception("Requisition cannot be edited in {$requisition->status} state");
        }

        return DB::transaction(function () use ($requisition, $data) {
            $oldValues = $requisition->toArray();

            // Update requisition
            $requisition->update($data);

            // Update line items if provided
            if (isset($data['items'])) {
                $requisition->items()->delete();
                $this->createLineItems($requisition, $data['items']);
            }

            // Audit log
            $this->auditService->logUpdate(
                Requisition::class,
                $requisition->id,
                $oldValues,
                $requisition->fresh()->toArray(),
                'Requisition updated',
                ['module' => 'requisitions']
            );

            return $requisition->load('items');
        });
    }

    /**
     * Submit requisition for approval
     */
    public function submit(Requisition $requisition): Requisition
    {
        if (!$requisition->canBeSubmitted()) {
            throw new Exception("Requisition cannot be submitted");
        }

        return DB::transaction(function () use ($requisition) {
            // Transition to submitted state
            $this->workflowEngine->transition(
                $requisition,
                'requisition',
                'draft',
                'submitted',
                'Submitted for approval'
            );

            // Create approval records
            $this->createApprovalRecords($requisition);

            // Update submission timestamp
            $requisition->submitted_at = Carbon::now();
            $requisition->save();

            // Send notifications (queued)
            $this->notifyApprovers($requisition);

            return $requisition->fresh();
        });
    }

    /**
     * Approve requisition at a specific level
     */
    public function approve(Requisition $requisition, string $level, ?string $comments = null): Requisition
    {
        return DB::transaction(function () use ($requisition, $level, $comments) {
            $approverId = Auth::id();

            // Enforce segregation of duties
            $this->governanceRules->enforceSegregationOfDuties(
                $approverId,
                'approve',
                $requisition,
                ['created', 'updated']
            );

            // Validate requester is not approver
            if ($requisition->requested_by === $approverId) {
                throw new Exception("Cannot approve your own requisition");
            }

            // Find approval record
            $approval = $requisition->approvals()
                ->where('approval_level', $level)
                ->where('approver_id', $approverId)
                ->where('status', 'pending')
                ->firstOrFail();

            // Update approval
            $approval->update([
                'status' => 'approved',
                'comments' => $comments,
                'responded_at' => Carbon::now(),
                'ip_address' => request()->ip(),
            ]);

            // Determine next state
            $nextState = $this->determineNextStateAfterApproval($requisition, $level);

            // Transition workflow
            $this->workflowEngine->transition(
                $requisition,
                'requisition',
                $requisition->status,
                $nextState,
                "Approved by {$level}: {$comments}"
            );

            // Log approval
            $this->auditService->logApproval(
                Requisition::class,
                $requisition->id,
                'approved',
                $level,
                $comments,
                ['approver_id' => $approverId]
            );

            // If fully approved, mark approved_at
            if ($nextState === 'budget_approved') {
                $requisition->approved_at = Carbon::now();
                $requisition->save();
            }

            return $requisition->fresh();
        });
    }

    /**
     * Reject requisition
     */
    public function reject(Requisition $requisition, string $reason, string $level): Requisition
    {
        return DB::transaction(function () use ($requisition, $reason, $level) {
            $approverId = Auth::id();

            // Find approval record
            $approval = $requisition->approvals()
                ->where('approval_level', $level)
                ->where('approver_id', $approverId)
                ->where('status', 'pending')
                ->firstOrFail();

            // Update approval
            $approval->update([
                'status' => 'rejected',
                'comments' => $reason,
                'responded_at' => Carbon::now(),
                'ip_address' => request()->ip(),
            ]);

            // Transition to rejected
            $this->workflowEngine->transition(
                $requisition,
                'requisition',
                $requisition->status,
                'rejected',
                "Rejected by {$level}: {$reason}"
            );

            // Update requisition
            $requisition->rejection_reason = $reason;
            $requisition->rejected_at = Carbon::now();
            $requisition->save();

            // Log rejection
            $this->auditService->logApproval(
                Requisition::class,
                $requisition->id,
                'rejected',
                $level,
                $reason,
                ['approver_id' => $approverId]
            );

            // Notify requester
            $this->notifyRequester($requisition, 'rejected');

            return $requisition->fresh();
        });
    }

    /**
     * Cancel requisition
     */
    public function cancel(Requisition $requisition, string $reason): Requisition
    {
        if (!in_array($requisition->status, ['draft', 'submitted', 'hod_review'])) {
            throw new Exception("Cannot cancel requisition in {$requisition->status} state");
        }

        return DB::transaction(function () use ($requisition, $reason) {
            $this->workflowEngine->transition(
                $requisition,
                'requisition',
                $requisition->status,
                'cancelled',
                $reason
            );

            $requisition->cancellation_reason = $reason;
            $requisition->save();

            return $requisition->fresh();
        });
    }

    /**
     * Generate unique requisition number
     */
    protected function generateRequisitionNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "REQ-{$year}{$month}";

        $lastReq = Requisition::where('requisition_number', 'LIKE', "{$prefix}%")
            ->orderBy('requisition_number', 'desc')
            ->first();

        if ($lastReq) {
            $lastNumber = (int) substr($lastReq->requisition_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create line items for requisition
     */
    protected function createLineItems(Requisition $requisition, array $items): void
    {
        $lineNumber = 1;
        foreach ($items as $item) {
            RequisitionItem::create([
                'requisition_id' => $requisition->id,
                'line_number' => $lineNumber++,
                'description' => $item['description'],
                'specifications' => $item['specifications'] ?? null,
                'quantity' => $item['quantity'],
                'unit_of_measure' => $item['unit_of_measure'],
                'estimated_unit_price' => $item['estimated_unit_price'],
                'estimated_total_price' => $item['quantity'] * $item['estimated_unit_price'],
                'is_vatable' => $item['is_vatable'] ?? true,
                'vat_type' => $item['vat_type'] ?? 'vatable',
                'subject_to_wht' => $item['subject_to_wht'] ?? false,
                'wht_type' => $item['wht_type'] ?? null,
            ]);
        }
    }

    /**
     * Determine approval requirements based on amount and thresholds
     */
    protected function setApprovalRequirements(array $data): array
    {
        $amount = $data['estimated_total_base'];

        $levels = $this->governanceRules->getRequiredApprovalLevel($amount);

        $data['requires_hod_approval'] = in_array('hod', $levels);
        $data['requires_principal_approval'] = in_array('principal', $levels);
        $data['requires_board_approval'] = in_array('board', $levels);
        $data['requires_tender'] = $this->governanceRules->requiresTender($amount);

        return $data;
    }

    /**
     * Create approval records when a requisition is submitted.
     *
     * Approval matrix:
     *   HOD           — always when requires_hod_approval; matched by is_hod + department_id
     *   budget_owner  — always required for budget review; matched by is_budget_owner +
     *                   department_id, falls back to any budget owner in the system
     *   principal     — when requires_principal_approval; matched by Spatie role 'principal'
     *   board         — when requires_board_approval; one record per board-member role holder
     *
     * Records are created with status = 'pending'. Missing approvers are skipped
     * (logged to audit trail so admins can investigate).
     */
    protected function createApprovalRecords(Requisition $requisition): void
    {
        // HOD approval
        if ($requisition->requires_hod_approval) {
            $hod = User::where('is_hod', true)
                ->where('department_id', $requisition->department_id)
                ->first();

            if ($hod) {
                RequisitionApproval::create([
                    'requisition_id' => $requisition->id,
                    'approval_level' => 'hod',
                    'approver_id'    => $hod->id,
                    'status'         => 'pending',
                ]);
            } else {
                $this->auditService->logCreate(
                    Requisition::class,
                    $requisition->id,
                    [],
                    ['warning' => 'No HOD found for department ' . $requisition->department_id]
                );
            }
        }

        // Budget owner approval (always required for budget review stage)
        $budgetOwner = User::where('is_budget_owner', true)
            ->where('department_id', $requisition->department_id)
            ->first()
            ?? User::where('is_budget_owner', true)->first();

        if ($budgetOwner) {
            RequisitionApproval::create([
                'requisition_id' => $requisition->id,
                'approval_level' => 'budget_owner',
                'approver_id'    => $budgetOwner->id,
                'status'         => 'pending',
            ]);
        } else {
            $this->auditService->logCreate(
                Requisition::class,
                $requisition->id,
                [],
                ['warning' => 'No budget owner found — budget review step will be blocked']
            );
        }

        // Principal approval (large-amount requisitions)
        if ($requisition->requires_principal_approval) {
            $principal = User::role('principal')->first();

            if ($principal) {
                RequisitionApproval::create([
                    'requisition_id' => $requisition->id,
                    'approval_level' => 'principal',
                    'approver_id'    => $principal->id,
                    'status'         => 'pending',
                ]);
            }
        }

        // Board approval (highest-value requisitions — one record per board member)
        if ($requisition->requires_board_approval) {
            $boardMembers = User::role('board-member')->get();

            foreach ($boardMembers as $member) {
                RequisitionApproval::create([
                    'requisition_id' => $requisition->id,
                    'approval_level' => 'board',
                    'approver_id'    => $member->id,
                    'status'         => 'pending',
                ]);
            }
        }
    }

    /**
     * Determine next state after approval at a specific level
     */
    protected function determineNextStateAfterApproval(Requisition $requisition, string $level): string
    {
        return match ($level) {
            'hod' => 'hod_approved',
            'budget_owner' => 'budget_approved',
            'principal' => 'budget_approved',
            default => 'submitted',
        };
    }

    /**
     * Notify approvers (queued)
     */
    protected function notifyApprovers(Requisition $requisition): void
    {
        // Queue notification jobs
    }

    /**
     * Notify requester
     */
    protected function notifyRequester(Requisition $requisition, string $event): void
    {
        // Queue notification job
    }
}
