<?php

namespace App\Modules\Quality\Services;

use App\Modules\Quality\Models\CapaAction;
use App\Modules\Quality\Models\CapaActionUpdate;
use App\Core\Audit\AuditService;
use App\Core\Workflow\WorkflowEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Carbon\Carbon;

/**
 * CAPA (Corrective and Preventive Action) Service
 * 
 * Manages CAPA workflow per ISO 9001:2015 requirements
 */
class CapaService
{
    protected AuditService $auditService;
    protected WorkflowEngine $workflowEngine;

    public function __construct(
        AuditService $auditService,
        WorkflowEngine $workflowEngine
    ) {
        $this->auditService = $auditService;
        $this->workflowEngine = $workflowEngine;
    }

    /**
     * Create new CAPA action
     */
    public function create(array $data): CapaAction
    {
        return DB::transaction(function () use ($data) {
            $data['capa_number'] = $this->generateCapaNumber($data['type']);
            $data['raised_by'] = Auth::id();
            $data['status'] = 'draft';

            $capa = CapaAction::create($data);

            $this->auditService->logCreate(
                CapaAction::class,
                $capa->id,
                $capa->toArray(),
                [
                    'module' => 'quality',
                    'type' => $capa->type,
                    'source' => $capa->source,
                ]
            );

            return $capa;
        });
    }

    /**
     * Update CAPA action
     */
    public function update(CapaAction $capa, array $data): CapaAction
    {
        return DB::transaction(function () use ($capa, $data) {
            $oldData = $capa->toArray();
            $capa->update($data);

            $this->auditService->logUpdate(
                CapaAction::class,
                $capa->id,
                $oldData,
                $capa->fresh()->toArray(),
                ['module' => 'quality']
            );

            return $capa->fresh();
        });
    }

    /**
     * Add progress update
     */
    public function addUpdate(CapaAction $capa, string $description, float $progressPercentage, array $attachments = []): CapaActionUpdate
    {
        return DB::transaction(function () use ($capa, $description, $progressPercentage, $attachments) {
            $update = CapaActionUpdate::create([
                'capa_action_id' => $capa->id,
                'user_id' => Auth::id(),
                'update_description' => $description,
                'progress_percentage' => $progressPercentage,
                'attachments' => $attachments,
            ]);

            $this->auditService->logCustom(
                CapaAction::class,
                $capa->id,
                'progress_update_added',
                [
                    'update_id' => $update->id,
                    'progress_percentage' => $progressPercentage,
                    'updated_by' => Auth::id(),
                ]
            );

            return $update;
        });
    }

    /**
     * Submit CAPA for approval
     */
    public function submitForApproval(CapaAction $capa): CapaAction
    {
        if (!$capa->isDraft()) {
            throw new Exception("Only draft CAPAs can be submitted");
        }

        return DB::transaction(function () use ($capa) {
            $this->workflowEngine->transition(
                $capa,
                'capa',
                'draft',
                'pending_approval',
                'Submitted for approval'
            );

            $this->auditService->logCustom(
                CapaAction::class,
                $capa->id,
                'submitted_for_approval',
                [
                    'submitted_by' => Auth::id(),
                    'submitted_at' => Carbon::now(),
                ]
            );

            return $capa->fresh();
        });
    }

    /**
     * Approve CAPA
     */
    public function approve(CapaAction $capa, ?string $comments = null): CapaAction
    {
        if (!$capa->canBeApproved()) {
            throw new Exception("CAPA cannot be approved in {$capa->status} status");
        }

        return DB::transaction(function () use ($capa, $comments) {
            $this->workflowEngine->transition(
                $capa,
                'capa',
                'pending_approval',
                'approved',
                $comments ?? 'Approved'
            );

            $capa->approved_by = Auth::id();
            $capa->approved_at = Carbon::now();
            $capa->approval_comments = $comments;
            $capa->save();

            $this->auditService->logApproval(
                CapaAction::class,
                $capa->id,
                'approved',
                'approver',
                $comments,
                ['approver_id' => Auth::id()]
            );

            return $capa->fresh();
        });
    }

    /**
     * Reject CAPA
     */
    public function reject(CapaAction $capa, string $reason): CapaAction
    {
        if (!$capa->canBeApproved()) {
            throw new Exception("CAPA cannot be rejected in {$capa->status} status");
        }

        return DB::transaction(function () use ($capa, $reason) {
            $this->workflowEngine->transition(
                $capa,
                'capa',
                'pending_approval',
                'rejected',
                $reason
            );

            $this->auditService->logCustom(
                CapaAction::class,
                $capa->id,
                'rejected',
                [
                    'rejected_by' => Auth::id(),
                    'rejected_at' => Carbon::now(),
                    'reason' => $reason,
                ]
            );

            return $capa->fresh();
        });
    }

    /**
     * Start implementation (move to in_progress)
     */
    public function startImplementation(CapaAction $capa): CapaAction
    {
        if (!$capa->isApproved() || $capa->status !== 'approved') {
            throw new Exception("Only approved CAPAs can be started");
        }

        return DB::transaction(function () use ($capa) {
            $this->workflowEngine->transition(
                $capa,
                'capa',
                'approved',
                'in_progress',
                'Implementation started'
            );

            $this->auditService->logCustom(
                CapaAction::class,
                $capa->id,
                'implementation_started',
                [
                    'started_by' => Auth::id(),
                    'started_at' => Carbon::now(),
                ]
            );

            return $capa->fresh();
        });
    }

    /**
     * Submit for verification
     */
    public function submitForVerification(CapaAction $capa): CapaAction
    {
        if (!$capa->isInProgress()) {
            throw new Exception("Only in-progress CAPAs can be submitted for verification");
        }

        return DB::transaction(function () use ($capa) {
            $this->workflowEngine->transition(
                $capa,
                'capa',
                'in_progress',
                'pending_verification',
                'Submitted for verification'
            );

            $capa->actual_completion_date = Carbon::now();
            $capa->save();

            $this->auditService->logCustom(
                CapaAction::class,
                $capa->id,
                'submitted_for_verification',
                [
                    'submitted_by' => Auth::id(),
                    'completed_at' => $capa->actual_completion_date,
                ]
            );

            return $capa->fresh();
        });
    }

    /**
     * Verify CAPA completion
     */
    public function verify(CapaAction $capa, bool $passed, ?string $comments = null): CapaAction
    {
        if (!$capa->canBeVerified()) {
            throw new Exception("CAPA cannot be verified in {$capa->status} status");
        }

        return DB::transaction(function () use ($capa, $passed, $comments) {
            $newStatus = $passed ? 'verified' : 'in_progress';

            $this->workflowEngine->transition(
                $capa,
                'capa',
                'pending_verification',
                $newStatus,
                $comments ?? ($passed ? 'Verification passed' : 'Verification failed - returned to implementation')
            );

            $capa->verified_by = Auth::id();
            $capa->verified_at = Carbon::now();
            $capa->verification_passed = $passed;
            $capa->verification_comments = $comments;
            $capa->save();

            $this->auditService->logCustom(
                CapaAction::class,
                $capa->id,
                $passed ? 'verification_passed' : 'verification_failed',
                [
                    'verified_by' => Auth::id(),
                    'verified_at' => Carbon::now(),
                    'passed' => $passed,
                    'comments' => $comments,
                ]
            );

            return $capa->fresh();
        });
    }

    /**
     * Conduct effectiveness review
     */
    public function effectivenessReview(CapaAction $capa, string $rating, string $comments): CapaAction
    {
        if ($capa->status !== 'verified') {
            throw new Exception("Only verified CAPAs can be reviewed for effectiveness");
        }

        return DB::transaction(function () use ($capa, $rating, $comments) {
            $capa->reviewed_by = Auth::id();
            $capa->reviewed_at = Carbon::now();
            $capa->effectiveness_rating = $rating;
            $capa->effectiveness_review_comments = $comments;
            $capa->save();

            $this->auditService->logCustom(
                CapaAction::class,
                $capa->id,
                'effectiveness_reviewed',
                [
                    'reviewed_by' => Auth::id(),
                    'rating' => $rating,
                    'comments' => $comments,
                ]
            );

            return $capa->fresh();
        });
    }

    /**
     * Close CAPA
     */
    public function close(CapaAction $capa, ?string $lessonsLearned = null): CapaAction
    {
        if (!$capa->canBeClosed()) {
            throw new Exception("CAPA cannot be closed in {$capa->status} status");
        }

        return DB::transaction(function () use ($capa, $lessonsLearned) {
            $this->workflowEngine->transition(
                $capa,
                'capa',
                'verified',
                'closed',
                'CAPA closed'
            );

            if ($lessonsLearned) {
                $capa->lessons_learned = $lessonsLearned;
                $capa->save();
            }

            $this->auditService->logCustom(
                CapaAction::class,
                $capa->id,
                'closed',
                [
                    'closed_by' => Auth::id(),
                    'closed_at' => Carbon::now(),
                    'has_lessons_learned' => !empty($lessonsLearned),
                ]
            );

            return $capa->fresh();
        });
    }

    /**
     * Generate CAPA number
     */
    protected function generateCapaNumber(string $type): string
    {
        $prefix = strtoupper($type) === 'CORRECTIVE' ? 'CA' : 'PA';
        $year = date('Y');
        $prefix = "{$prefix}-{$year}";

        $lastCapa = CapaAction::where('capa_number', 'LIKE', "{$prefix}%")
            ->orderBy('capa_number', 'desc')
            ->first();

        if ($lastCapa) {
            $lastNumber = (int) substr($lastCapa->capa_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create CAPA from variance
     */
    public function createFromVariance($entity, string $entityType, array $varianceData): CapaAction
    {
        $data = [
            'type' => 'corrective',
            'title' => "Procurement Variance: {$varianceData['description']}",
            'description' => "Variance detected in {$entityType}: {$varianceData['details']}",
            'source' => 'variance_analysis',
            'source_entity_type' => get_class($entity),
            'source_entity_id' => $entity->id,
            'source_reference' => $varianceData['reference'] ?? null,
            'problem_statement' => $varianceData['problem_statement'] ?? "Unexplained variance requiring investigation",
            'priority' => $varianceData['priority'] ?? 'medium',
            'assigned_to' => $varianceData['assigned_to'] ?? null,
            'department_id' => $varianceData['department_id'] ?? null,
        ];

        return $this->create($data);
    }
}
