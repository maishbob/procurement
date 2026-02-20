<?php

namespace App\Listeners;

use App\Events\RequisitionSubmittedEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Models\User;
use App\Notifications\RequisitionSubmittedNotification;

class NotifyApproversListener
{
    public function handle(RequisitionSubmittedEvent $event): void
    {
        $requisition  = $event->requisition;
        $amount       = (float) ($requisition->total_amount ?? 0);
        $hodThreshold = (float) config('procurement.approval_thresholds.hod', env('THRESHOLD_HOD_APPROVAL', 50000));

        // Route to HOD for smaller amounts, Principal/Deputy for larger amounts
        $rolesToNotify = $amount <= $hodThreshold
            ? ['Head of Department']
            : ['Principal', 'Deputy Principal'];

        $approvers = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $rolesToNotify))
            ->where('department_id', $requisition->department_id)
            ->where('id', '!=', $requisition->created_by ?? $requisition->requested_by)
            ->get();

        // Fall back to any matching approver organisation-wide if none found in department
        if ($approvers->isEmpty()) {
            $approvers = User::active()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', $rolesToNotify))
                ->where('id', '!=', $requisition->created_by ?? $requisition->requested_by)
                ->get();
        }

        foreach ($approvers as $approver) {
            dispatch(new SendEmailNotificationJob(
                $approver,
                new RequisitionSubmittedNotification($requisition, $approver)
            ));
        }

        app(\App\Core\Audit\AuditService::class)->log(
            action: 'APPROVERS_NOTIFIED',
            model: 'Requisition',
            modelId: $requisition->id,
            metadata: [
                'approver_count' => $approvers->count(),
                'roles_notified' => $rolesToNotify,
                'total_amount'   => $amount,
            ],
        );
    }
}
