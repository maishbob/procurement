<?php

namespace App\Listeners;

use App\Events\RequisitionApprovedEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Notifications\RequisitionApprovedNotification;

class NotifyRequesterListener
{
    /**
     * Handle the event.
     */
    public function handle(RequisitionApprovedEvent $event): void
    {
        $requisition = $event->requisition;

        // Find the person who created the requisition
        $requester = \App\Models\User::find($requisition->created_by);

        if (!$requester) {
            return;
        }

        // Send notification to requester
        dispatch(new SendEmailNotificationJob(
            $requester,
            new RequisitionApprovedNotification($requisition, $event->approvalLevel, $event->approverName)
        ));

        // Audit log
        \App\Core\Audit\AuditService::log(
            action: 'REQUESTER_NOTIFIED',
            status: 'success',
            model_type: 'Requisition',
            model_id: $requisition->id,
            description: "Notified requester {$requester->email} that requisition {$requisition->requisition_number} was approved",
            metadata: [
                'requester_id' => $requester->id,
                'approval_level' => $event->approvalLevel,
                'approver_name' => $event->approverName,
            ]
        );
    }
}
