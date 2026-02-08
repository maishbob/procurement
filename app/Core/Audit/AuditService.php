<?php

namespace App\Core\Audit;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Immutable Audit Logging Service
 * 
 * Purpose: Provide comprehensive, tamper-proof audit trails for all system actions
 * Kenya Context: Supports compliance requirements for financial and procurement audits
 */
class AuditService
{
    /**
     * Log an auditable action
     * 
     * @param string $action
     * @param string $model
     * @param mixed $modelId
     * @param array|null $oldValues
     * @param array|null $newValues
     * @param string|null $justification
     * @param array $metadata
     * @return int Audit log ID
     */
    public function log(
        string $action,
        string $model,
        $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $justification = null,
        array $metadata = []
    ): int {
        $user = Auth::user();

        $auditData = [
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'user_email' => $user?->email ?? 'system@internal',
            'action' => $action,
            'auditable_type' => $model,
            'auditable_id' => $modelId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'justification' => $justification,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'metadata' => !empty($metadata) ? json_encode($metadata) : null,
            'created_at' => Carbon::now(),
        ];

        return DB::table('audit_logs')->insertGetId($auditData);
    }

    /**
     * Log a create action
     */
    public function logCreate(string $model, $modelId, array $values, array $metadata = []): int
    {
        return $this->log('created', $model, $modelId, null, $values, null, $metadata);
    }

    /**
     * Log an update action
     */
    public function logUpdate(string $model, $modelId, array $oldValues, array $newValues, ?string $justification = null, array $metadata = []): int
    {
        return $this->log('updated', $model, $modelId, $oldValues, $newValues, $justification, $metadata);
    }

    /**
     * Log a delete action
     */
    public function logDelete(string $model, $modelId, array $oldValues, ?string $justification = null, array $metadata = []): int
    {
        return $this->log('deleted', $model, $modelId, $oldValues, null, $justification, $metadata);
    }

    /**
     * Log a state transition
     */
    public function logStateTransition(string $model, $modelId, string $oldState, string $newState, ?string $justification = null, array $metadata = []): int
    {
        return $this->log(
            'state_transition',
            $model,
            $modelId,
            ['state' => $oldState],
            ['state' => $newState],
            $justification,
            $metadata
        );
    }

    /**
     * Log an approval action
     */
    public function logApproval(string $model, $modelId, string $decision, string $level, ?string $justification = null, array $metadata = []): int
    {
        return $this->log(
            'approval',
            $model,
            $modelId,
            null,
            ['decision' => $decision, 'level' => $level],
            $justification,
            array_merge($metadata, ['approval_decision' => $decision, 'approval_level' => $level])
        );
    }

    /**
     * Log a policy violation
     */
    public function logPolicyViolation(string $policy, string $model, $modelId, string $reason, array $metadata = []): int
    {
        return $this->log(
            'policy_violation',
            $model,
            $modelId,
            null,
            ['policy' => $policy, 'reason' => $reason],
            null,
            array_merge($metadata, ['violation_type' => 'policy', 'policy_name' => $policy])
        );
    }

    /**
     * Log an override action
     */
    public function logOverride(string $model, $modelId, string $rule, string $justification, array $metadata = []): int
    {
        return $this->log(
            'override',
            $model,
            $modelId,
            null,
            ['rule' => $rule],
            $justification,
            array_merge($metadata, ['override_type' => 'rule', 'rule_name' => $rule])
        );
    }

    /**
     * Log a compliance event
     */
    public function logCompliance(string $event, string $model, $modelId, array $data, array $metadata = []): int
    {
        return $this->log(
            'compliance',
            $model,
            $modelId,
            null,
            $data,
            null,
            array_merge($metadata, ['compliance_event' => $event])
        );
    }

    /**
     * Log an exception/error
     */
    public function logException(string $model, $modelId, string $exception, string $message, array $metadata = []): int
    {
        return $this->log(
            'exception',
            $model,
            $modelId,
            null,
            ['exception' => $exception, 'message' => $message],
            null,
            array_merge($metadata, ['error_type' => 'exception'])
        );
    }

    /**
     * Archive old audit logs
     * 
     * @param int $daysToKeep
     * @return int Number of records archived
     */
    public function archiveOldLogs(int $daysToKeep = 365): int
    {
        if (!config('procurement.audit.auto_archive')) {
            return 0;
        }

        $cutoffDate = Carbon::now()->subDays($daysToKeep);

        // Move to archive table
        $archived = DB::table('audit_logs')
            ->where('created_at', '<', $cutoffDate)
            ->get();

        if ($archived->isNotEmpty()) {
            DB::table('audit_logs_archive')->insert($archived->toArray());
            DB::table('audit_logs')->where('created_at', '<', $cutoffDate)->delete();
        }

        return $archived->count();
    }

    /**
     * Get audit trail for a model
     */
    public function getAuditTrail(string $model, $modelId, int $limit = 100): array
    {
        return DB::table('audit_logs')
            ->where('auditable_type', $model)
            ->where('auditable_id', $modelId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get user activity log
     */
    public function getUserActivity(int $userId, int $days = 30, int $limit = 100): array
    {
        $since = Carbon::now()->subDays($days);

        return DB::table('audit_logs')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
