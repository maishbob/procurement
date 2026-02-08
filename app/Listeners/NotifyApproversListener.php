<?php

namespace App\Listeners;

use App\Events\RequisitionSubmittedEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Notifications\RequisitionSubmittedNotification;

class NotifyApproversListener
{
    /**
     * Handle the event.
     */
    public function handle(RequisitionSubmittedEvent $event): void
    {
        $requisition = $event->requisition;

        // Find approvers based on requisition amount and their approval limits
        $approvers = \App\Models\User::where('approval_limit', '>=', $requisition->total_amount)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['approver', 'manager', 'director', 'admin']);
            })
            ->where('id', '!=', $requisition->created_by)
            ->get();

        // Send notifications to all eligible approvers
        foreach ($approvers as $approver) {
            dispatch(new SendEmailNotificationJob(
                $approver,
                new RequisitionSubmittedNotification($requisition, $approver)
            ));
        }

        // Audit log
        \App\Core\Audit\AuditService::log(
            action: 'APPROVERS_NOTIFIED',
            status: 'success',
            model_type: 'Requisition',
            model_id: $requisition->id,
            description: "Notified {$approvers->count()} approvers for requisition {$requisition->requisition_number}",
            metadata: [
                'approver_count' => $approvers->count(),
                'total_amount' => $requisition->total_amount,
            ]
        );
    }
}
