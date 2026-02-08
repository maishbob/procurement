<?php

namespace App\Modules\Requisitions\Observers;

use App\Modules\Requisitions\Models\Requisition;
use App\Core\Audit\AuditService;
use Illuminate\Support\Facades\Auth;

/**
 * Requisition Observer
 * 
 * Automatic audit logging for all requisition changes
 */
class RequisitionObserver
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the Requisition "created" event.
     */
    public function created(Requisition $requisition): void
    {
        $this->auditService->logCreate(
            Requisition::class,
            $requisition->id,
            $requisition->toArray(),
            [
                'module' => 'requisitions',
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
            ]
        );
    }

    /**
     * Handle the Requisition "updated" event.
     */
    public function updated(Requisition $requisition): void
    {
        // Get original values
        $original = $requisition->getOriginal();
        $changes = $requisition->getChanges();

        // Only log if there are actual changes
        if (empty($changes)) {
            return;
        }

        $this->auditService->logUpdate(
            Requisition::class,
            $requisition->id,
            $original,
            $requisition->toArray(),
            'Requisition updated',
            [
                'module' => 'requisitions',
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
                'changed_fields' => array_keys($changes),
            ]
        );

        // Track status transitions separately
        if (isset($changes['status'])) {
            $this->auditService->logStateTransition(
                Requisition::class,
                $requisition->id,
                $original['status'],
                $changes['status'],
                'Status changed',
                [
                    'module' => 'requisitions',
                    'user_id' => Auth::id(),
                ]
            );
        }
    }

    /**
     * Handle the Requisition "deleted" event.
     */
    public function deleted(Requisition $requisition): void
    {
        $this->auditService->logDelete(
            Requisition::class,
            $requisition->id,
            $requisition->toArray(),
            'Requisition deleted',
            [
                'module' => 'requisitions',
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
            ]
        );
    }

    /**
     * Handle the Requisition "restored" event.
     */
    public function restored(Requisition $requisition): void
    {
        $this->auditService->log(
            Requisition::class,
            $requisition->id,
            'restored',
            'Requisition restored from soft delete',
            [
                'module' => 'requisitions',
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
            ]
        );
    }

    /**
     * Handle the Requisition "force deleted" event.
     */
    public function forceDeleted(Requisition $requisition): void
    {
        $this->auditService->log(
            Requisition::class,
            $requisition->id,
            'force_deleted',
            'Requisition permanently deleted',
            [
                'module' => 'requisitions',
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
                'warning' => 'PERMANENT_DELETION',
            ]
        );
    }
}
