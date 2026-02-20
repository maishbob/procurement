<?php

namespace Tests\Unit\Core;

use Tests\TestCase;
use App\Core\Audit\AuditService;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = app(AuditService::class);
        $this->actingAs(User::factory()->create());
    }

    /**
     * Test audit log creation via service.
     */
    public function test_audit_log_is_created(): void
    {
        $this->auditService->log('TEST_ACTION', 'Requisition', 1, null, null, null, []);

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'TEST_ACTION',
            'auditable_type' => 'Requisition',
            'auditable_id'   => 1,
        ]);
    }

    /**
     * T-6.3: Audit records cannot be updated (immutability guard).
     */
    public function test_audit_log_cannot_be_updated(): void
    {
        // Use DB::table() to bypass fillable guard (created_at not in $fillable)
        $id = DB::table('audit_logs')->insertGetId([
            'user_id'        => auth()->id(),
            'user_name'      => 'Test',
            'user_email'     => 'test@example.com',
            'action'         => 'ORIGINAL_ACTION',
            'auditable_type' => 'Test',
            'auditable_id'   => 1,
            'ip_address'     => '127.0.0.1',
            'created_at'     => now(),
        ]);

        $log = AuditLog::find($id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/immutable/i');

        $log->update(['action' => 'TAMPERED']);
    }

    /**
     * T-6.3: Audit records cannot be deleted (immutability guard).
     */
    public function test_audit_log_cannot_be_deleted(): void
    {
        // Use DB::table() to bypass fillable guard (created_at not in $fillable)
        $id = DB::table('audit_logs')->insertGetId([
            'user_id'        => auth()->id(),
            'user_name'      => 'Test',
            'user_email'     => 'test@example.com',
            'action'         => 'TEST_IMMUTABLE',
            'auditable_type' => 'Test',
            'auditable_id'   => 1,
            'ip_address'     => '127.0.0.1',
            'created_at'     => now(),
        ]);

        $log = AuditLog::find($id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/immutable/i');

        $log->delete();
    }

    /**
     * T-6.3: Every workflow transition via AuditService creates an audit entry.
     */
    public function test_state_transition_creates_audit_entry(): void
    {
        $this->auditService->logStateTransition(
            'Requisition',
            42,
            'draft',
            'submitted',
            'Submitted by user'
        );

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'state_transition',
            'auditable_type' => 'Requisition',
            'auditable_id'   => 42,
        ]);
    }

    /**
     * Test audit log captures metadata.
     */
    public function test_audit_log_captures_metadata(): void
    {
        $metadata = [
            'po_number' => 'PO/2026/001',
            'amount'    => 150000,
            'supplier'  => 'ABC Supplies',
        ];

        $this->auditService->log(
            'PO_CREATED',
            'PurchaseOrder',
            1,
            null,
            null,
            null,
            $metadata
        );

        $log = AuditLog::where('action', 'PO_CREATED')->first();

        $this->assertNotNull($log);
        $this->assertEquals($metadata, $log->metadata);
    }

    /**
     * Test audit log records the authenticated user.
     */
    public function test_audit_log_records_user(): void
    {
        $user = auth()->user();

        $this->auditService->log('USER_ACTION', 'Test');

        $log = AuditLog::where('action', 'USER_ACTION')->latest('id')->first();

        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals($user->name, $log->user_name);
    }

    /**
     * Test audit log records IP address.
     */
    public function test_audit_log_records_ip_address(): void
    {
        $this->auditService->log('IP_TEST', 'Test');

        $log = AuditLog::where('action', 'IP_TEST')->latest('id')->first();

        $this->assertNotNull($log->ip_address);
    }

    /**
     * Test filtering audit logs by action.
     */
    public function test_filter_logs_by_action(): void
    {
        $this->auditService->log('CREATE', 'Test');
        $this->auditService->log('UPDATE', 'Test');
        $this->auditService->log('DELETE', 'Test');

        $logs = AuditLog::where('action', 'UPDATE')->get();

        $this->assertEquals(1, $logs->count());
        $this->assertEquals('UPDATE', $logs->first()->action);
    }
}
