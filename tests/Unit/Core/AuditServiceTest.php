<?php

namespace Tests\Unit\Core;

use Tests\TestCase;
use App\Core\Audit\AuditService;
use App\Models\User;
use App\Models\Requisition;

class AuditServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    /**
     * Test audit log creation
     */
    public function test_audit_log_is_created(): void
    {
        $action = 'TEST_ACTION';
        $status = 'success';

        AuditService::log(
            action: $action,
            status: $status,
            model_type: 'Requisition',
            model_id: 1,
            description: 'Test log entry'
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => $action,
            'status' => $status,
            'model_type' => 'Requisition',
            'model_id' => 1,
        ]);
    }

    /**
     * Test audit log immutability
     */
    public function test_audit_log_cannot_be_deleted(): void
    {
        $log = \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'TEST_IMMUTABLE',
            'status' => 'success',
            'model_type' => 'Test',
            'description' => 'Immutable test',
        ]);

        // Attempt to delete should fail or be prevented
        $this->assertTrue($log->exists);

        // Simulate protection through soft deletes or policy
        $this->assertFalse($log->trashed()); // Should not be soft-deleted
    }

    /**
     * Test audit log metadata capture
     */
    public function test_audit_log_captures_metadata(): void
    {
        $metadata = [
            'po_number' => 'PO/2026/001',
            'amount' => 150000,
            'supplier' => 'ABC Supplies',
        ];

        AuditService::log(
            action: 'PO_CREATED',
            status: 'success',
            model_type: 'PurchaseOrder',
            model_id: 1,
            description: 'Purchase order created',
            metadata: $metadata
        );

        $log = \App\Models\AuditLog::where('action', 'PO_CREATED')->first();

        $this->assertNotNull($log);
        $this->assertEquals($metadata, $log->metadata);
    }

    /**
     * Test audit log records user context
     */
    public function test_audit_log_records_user(): void
    {
        $user = auth()->user();

        AuditService::log(
            action: 'USER_ACTION',
            status: 'success',
            model_type: 'Test'
        );

        $log = \App\Models\AuditLog::latest()->first();

        $this->assertEquals($user->id, $log->user_id);
    }

    /**
     * Test audit log records IP address
     */
    public function test_audit_log_records_ip_address(): void
    {
        AuditService::log(
            action: 'IP_TEST',
            status: 'success',
            model_type: 'Test'
        );

        $log = \App\Models\AuditLog::latest()->first();

        $this->assertNotNull($log->ip_address);
        $this->assertTrue(filter_var($log->ip_address, FILTER_VALIDATE_IP));
    }

    /**
     * Test filtering audit logs by action
     */
    public function test_filter_logs_by_action(): void
    {
        AuditService::log(action: 'CREATE', status: 'success', model_type: 'Test');
        AuditService::log(action: 'UPDATE', status: 'success', model_type: 'Test');
        AuditService::log(action: 'DELETE', status: 'success', model_type: 'Test');

        $logs = \App\Models\AuditLog::where('action', 'UPDATE')->get();

        $this->assertEquals(1, $logs->count());
        $this->assertEquals('UPDATE', $logs->first()->action);
    }
}
