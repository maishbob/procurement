<?php

namespace App\Core\Workflow;

use App\Core\Audit\AuditService;
use Exception;

/**
 * Workflow State Machine Engine
 * 
 * Purpose: Manage state transitions for workflow-driven entities
 * Enforces valid transitions and logs all state changes
 */
class WorkflowEngine
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Validate if a state transition is allowed
     */
    public function canTransition(string $workflow, string $from, string $to): bool
    {
        $transitions = $this->getWorkflowTransitions($workflow);

        if (!isset($transitions[$from])) {
            return false;
        }

        return in_array($to, $transitions[$from]);
    }

    /**
     * Perform a state transition
     */
    public function transition(
        $model,
        string $workflow,
        string $from,
        string $to,
        ?string $justification = null,
        array $metadata = []
    ): bool {
        if (!$this->canTransition($workflow, $from, $to)) {
            throw new Exception("Invalid state transition from {$from} to {$to} in {$workflow} workflow");
        }

        // Update model state
        $model->status = $to;
        $model->save();

        // Log the transition
        $this->auditService->logStateTransition(
            get_class($model),
            $model->id,
            $from,
            $to,
            $justification,
            array_merge($metadata, ['workflow' => $workflow])
        );

        return true;
    }

    /**
     * Get all possible transitions for current state
     */
    public function getAvailableTransitions(string $workflow, string $currentState): array
    {
        $transitions = $this->getWorkflowTransitions($workflow);
        return $transitions[$currentState] ?? [];
    }

    /**
     * Define workflow transitions
     */
    protected function getWorkflowTransitions(string $workflow): array
    {
        return match ($workflow) {
            'requisition' => [
                'draft' => ['submitted', 'cancelled'],
                'submitted' => ['hod_review', 'rejected', 'cancelled'],
                'hod_review' => ['hod_approved', 'rejected'],
                'hod_approved' => ['budget_review', 'rejected'],
                'budget_review' => ['budget_approved', 'rejected'],
                'budget_approved' => ['procurement_queue', 'rejected'],
                'procurement_queue' => ['sourcing', 'cancelled'],
                'sourcing' => ['quoted', 'cancelled'],
                'quoted' => ['evaluated', 'cancelled'],
                'evaluated' => ['awarded', 'rejected'],
                'awarded' => ['po_created'],
                'po_created' => ['completed'],
                'rejected' => ['draft'], // Can be revised
                'cancelled' => [], // Terminal state
                'completed' => [], // Terminal state
            ],

            'purchase_order' => [
                'draft' => ['submitted', 'cancelled'],
                'submitted' => ['approved', 'rejected'],
                'approved' => ['issued', 'cancelled'],
                'issued' => ['acknowledged', 'cancelled'],
                'acknowledged' => ['partially_received', 'fully_received', 'cancelled'],
                'partially_received' => ['fully_received', 'cancelled'],
                'fully_received' => ['invoiced'],
                'invoiced' => ['payment_approved'],
                'payment_approved' => ['paid'],
                'paid' => ['closed'],
                'rejected' => ['draft'], // Can be revised
                'cancelled' => [], // Terminal state
                'closed' => [], // Terminal state
            ],

            'grn' => [
                'draft' => ['submitted', 'cancelled'],
                'submitted' => ['inspection_pending'],
                'inspection_pending' => ['inspection_passed', 'inspection_failed', 'partial_acceptance'],
                'inspection_passed' => ['approved'],
                'inspection_failed' => ['rejected'],
                'partial_acceptance' => ['approved'],
                'approved' => ['posted'],
                'posted' => ['completed'],
                'rejected' => [], // Terminal state
                'cancelled' => [], // Terminal state
                'completed' => [], // Terminal state
            ],

            'payment' => [
                'draft' => ['submitted', 'cancelled'],
                'submitted' => ['verification_pending'],
                'verification_pending' => ['verified', 'rejected'],
                'verified' => ['approval_pending'],
                'approval_pending' => ['approved', 'rejected'],
                'approved' => ['payment_processing'],
                'payment_processing' => ['paid', 'failed'],
                'paid' => ['completed'],
                'failed' => ['approval_pending'], // Can retry
                'rejected' => ['draft'], // Can be revised
                'cancelled' => [], // Terminal state
                'completed' => [], // Terminal state
            ],

            'procurement_process' => [
                'rfq_preparation' => ['rfq_issued'],
                'rfq_issued' => ['bids_received', 'cancelled'],
                'bids_received' => ['evaluation'],
                'evaluation' => ['evaluation_complete', 'cancelled'],
                'evaluation_complete' => ['award_recommendation'],
                'award_recommendation' => ['award_approved', 'award_rejected'],
                'award_approved' => ['po_generation'],
                'award_rejected' => ['rfq_preparation'], // Can restart
                'po_generation' => ['completed'],
                'cancelled' => [], // Terminal state
                'completed' => [], // Terminal state
            ],

            default => throw new Exception("Unknown workflow: {$workflow}"),
        };
    }

    /**
     * Get initial state for a workflow
     */
    public function getInitialState(string $workflow): string
    {
        return match ($workflow) {
            'requisition' => 'draft',
            'purchase_order' => 'draft',
            'grn' => 'draft',
            'payment' => 'draft',
            'procurement_process' => 'rfq_preparation',
            default => 'draft',
        };
    }

    /**
     * Check if state is terminal (no further transitions)
     */
    public function isTerminalState(string $workflow, string $state): bool
    {
        $transitions = $this->getWorkflowTransitions($workflow);
        return empty($transitions[$state] ?? []);
    }

    /**
     * Get workflow path (all states in order)
     */
    public function getWorkflowPath(string $workflow): array
    {
        // This returns the typical happy path for the workflow
        return match ($workflow) {
            'requisition' => [
                'draft',
                'submitted',
                'hod_review',
                'hod_approved',
                'budget_review',
                'budget_approved',
                'procurement_queue',
                'sourcing',
                'quoted',
                'evaluated',
                'awarded',
                'po_created',
                'completed',
            ],
            'purchase_order' => [
                'draft',
                'submitted',
                'approved',
                'issued',
                'acknowledged',
                'fully_received',
                'invoiced',
                'payment_approved',
                'paid',
                'closed',
            ],
            'grn' => [
                'draft',
                'submitted',
                'inspection_pending',
                'inspection_passed',
                'approved',
                'posted',
                'completed',
            ],
            'payment' => [
                'draft',
                'submitted',
                'verification_pending',
                'verified',
                'approval_pending',
                'approved',
                'payment_processing',
                'paid',
                'completed',
            ],
            default => [],
        };
    }
}
