<?php

namespace App\Listeners;

use App\Events\RequisitionApprovedEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Models\User;
use App\Notifications\RequisitionApprovedNotification;

class NotifyRequesterListener
{
    public function handle(RequisitionApprovedEvent $event): void
    {
        $requisition = $event->requisition;

        // Resolve the person who submitted the requisition (field name varies by model layer)
        $requester = User::find($requisition->created_by ?? $requisition->requested_by ?? null);

        if (!$requester) {
            return;
        }

        dispatch(new SendEmailNotificationJob(
            $requester,
            new RequisitionApprovedNotification($requisition, $event->approvalLevel, $event->approverName)
        ));

        app(\App\Core\Audit\AuditService::class)->log(
            action: 'REQUESTER_NOTIFIED_APPROVED',
            model: 'Requisition',
            modelId: $requisition->id,
            metadata: [
                'requester_id'   => $requester->id,
                'approval_level' => $event->approvalLevel,
                'approver_name'  => $event->approverName,
            ],
        );
    }
}
