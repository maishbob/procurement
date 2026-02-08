<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed Roles and Permissions for Kenya School Procurement System
     * 
     * This enforces segregation of duties and governance controls
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $now = Carbon::now();

        // 1. Create Roles
        $roles = [
            [
                'name' => 'Super Administrator',
                'slug' => 'super-admin',
                'description' => 'Full system access - IT/System Administration',
                'level' => 1,
                'is_system_role' => true,
            ],
            [
                'name' => 'Principal',
                'slug' => 'principal',
                'description' => 'School Principal - High-level approvals',
                'level' => 2,
                'is_system_role' => true,
            ],
            [
                'name' => 'Deputy Principal',
                'slug' => 'deputy-principal',
                'description' => 'Deputy Principal - Budget oversight',
                'level' => 3,
                'is_system_role' => true,
            ],
            [
                'name' => 'Finance Manager',
                'slug' => 'finance-manager',
                'description' => 'Finance Department Head - Payment processing & budget management',
                'level' => 4,
                'is_system_role' => true,
            ],
            [
                'name' => 'Accountant',
                'slug' => 'accountant',
                'description' => 'Accountant - Invoice verification & payment preparation',
                'level' => 5,
                'is_system_role' => true,
            ],
            [
                'name' => 'Procurement Officer',
                'slug' => 'procurement-officer',
                'description' => 'Procurement Department - Sourcing, PO creation',
                'level' => 6,
                'is_system_role' => true,
            ],
            [
                'name' => 'Procurement Assistant',
                'slug' => 'procurement-assistant',
                'description' => 'Procurement support - RFQ preparation, bid collection',
                'level' => 7,
                'is_system_role' => true,
            ],
            [
                'name' => 'Stores Manager',
                'slug' => 'stores-manager',
                'description' => 'Store Keeper - Receiving, inventory management',
                'level' => 8,
                'is_system_role' => true,
            ],
            [
                'name' => 'Head of Department',
                'slug' => 'hod',
                'description' => 'Department heads - Approve department requisitions',
                'level' => 9,
                'is_system_role' => true,
            ],
            [
                'name' => 'Budget Owner',
                'slug' => 'budget-owner',
                'description' => 'Budget line approval authority',
                'level' => 10,
                'is_system_role' => true,
            ],
            [
                'name' => 'Department Staff',
                'slug' => 'staff',
                'description' => 'Regular staff - Create requisitions',
                'level' => 11,
                'is_system_role' => true,
            ],
            [
                'name' => 'Auditor',
                'slug' => 'auditor',
                'description' => 'Internal/External auditor - Read-only access to all records',
                'level' => 12,
                'is_system_role' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::create(array_merge($roleData, ['guard_name' => 'web']));
        }

        // 2. Create Permissions
        $permissions = [
            // Requisition Permissions
            ['name' => 'requisitions.create', 'description' => 'Create requisitions', 'module' => 'requisitions'],
            ['name' => 'requisitions.view', 'description' => 'View requisitions', 'module' => 'requisitions'],
            ['name' => 'requisitions.view-all', 'description' => 'View all requisitions', 'module' => 'requisitions'],
            ['name' => 'requisitions.edit', 'description' => 'Edit requisitions', 'module' => 'requisitions'],
            ['name' => 'requisitions.delete', 'description' => 'Delete requisitions', 'module' => 'requisitions'],
            ['name' => 'requisitions.approve-hod', 'description' => 'Approve as HOD', 'module' => 'requisitions'],
            ['name' => 'requisitions.approve-budget', 'description' => 'Approve budget allocation', 'module' => 'requisitions'],
            ['name' => 'requisitions.approve-principal', 'description' => 'Approve as Principal', 'module' => 'requisitions'],
            ['name' => 'requisitions.reject', 'description' => 'Reject requisitions', 'module' => 'requisitions'],
            ['name' => 'requisitions.cancel', 'description' => 'Cancel requisitions', 'module' => 'requisitions'],

            // Procurement Permissions
            ['name' => 'procurement.create', 'description' => 'Create procurement processes', 'module' => 'procurement'],
            ['name' => 'procurement.view', 'description' => 'View procurement processes', 'module' => 'procurement'],
            ['name' => 'procurement.edit', 'description' => 'Edit procurement processes', 'module' => 'procurement'],
            ['name' => 'procurement.issue-rfq', 'description' => 'Issue RFQ/RFP', 'module' => 'procurement'],
            ['name' => 'procurement.evaluate-bids', 'description' => 'Evaluate supplier bids', 'module' => 'procurement'],
            ['name' => 'procurement.recommend-award', 'description' => 'Recommend contract award', 'module' => 'procurement'],
            ['name' => 'procurement.approve-award', 'description' => 'Approve contract award', 'module' => 'procurement'],

            // Purchase Orders
            ['name' => 'purchase-orders.create', 'description' => 'Create purchase orders', 'module' => 'purchase-orders'],
            ['name' => 'purchase-orders.view', 'description' => 'View purchase orders', 'module' => 'purchase-orders'],
            ['name' => 'purchase-orders.view-all', 'description' => 'View all purchase orders', 'module' => 'purchase-orders'],
            ['name' => 'purchase-orders.edit', 'description' => 'Edit purchase orders', 'module' => 'purchase-orders'],
            ['name' => 'purchase-orders.approve', 'description' => 'Approve purchase orders', 'module' => 'purchase-orders'],
            ['name' => 'purchase-orders.issue', 'description' => 'Issue purchase orders to suppliers', 'module' => 'purchase-orders'],
            ['name' => 'purchase-orders.cancel', 'description' => 'Cancel purchase orders', 'module' => 'purchase-orders'],

            // Receiving & GRN
            ['name' => 'grn.create', 'description' => 'Create GRN', 'module' => 'receiving'],
            ['name' => 'grn.view', 'description' => 'View GRNs', 'module' => 'receiving'],
            ['name' => 'grn.inspect', 'description' => 'Perform quality inspection', 'module' => 'receiving'],
            ['name' => 'grn.approve', 'description' => 'Approve GRN', 'module' => 'receiving'],
            ['name' => 'grn.post', 'description' => 'Post GRN to inventory', 'module' => 'receiving'],

            // Inventory
            ['name' => 'inventory.view', 'description' => 'View inventory', 'module' => 'inventory'],
            ['name' => 'inventory.manage', 'description' => 'Manage inventory items', 'module' => 'inventory'],
            ['name' => 'inventory.issue', 'description' => 'Issue stock to departments', 'module' => 'inventory'],
            ['name' => 'inventory.adjust', 'description' => 'Create stock adjustments', 'module' => 'inventory'],
            ['name' => 'inventory.approve-adjustment', 'description' => 'Approve stock adjustments', 'module' => 'inventory'],
            ['name' => 'inventory.transfer', 'description' => 'Transfer stock between stores', 'module' => 'inventory'],
            ['name' => 'inventory.count', 'description' => 'Perform stock counts', 'module' => 'inventory'],

            // Suppliers
            ['name' => 'suppliers.create', 'description' => 'Register suppliers', 'module' => 'suppliers'],
            ['name' => 'suppliers.view', 'description' => 'View suppliers', 'module' => 'suppliers'],
            ['name' => 'suppliers.edit', 'description' => 'Edit supplier information', 'module' => 'suppliers'],
            ['name' => 'suppliers.approve', 'description' => 'Approve supplier registration', 'module' => 'suppliers'],
            ['name' => 'suppliers.blacklist', 'description' => 'Blacklist suppliers', 'module' => 'suppliers'],
            ['name' => 'suppliers.rate', 'description' => 'Rate supplier performance', 'module' => 'suppliers'],

            // Invoices & Payments
            ['name' => 'invoices.create', 'description' => 'Create supplier invoices', 'module' => 'finance'],
            ['name' => 'invoices.view', 'description' => 'View invoices', 'module' => 'finance'],
            ['name' => 'invoices.verify', 'description' => 'Verify invoice three-way match', 'module' => 'finance'],
            ['name' => 'invoices.approve', 'description' => 'Approve invoices for payment', 'module' => 'finance'],

            ['name' => 'payments.create', 'description' => 'Create payment vouchers', 'module' => 'finance'],
            ['name' => 'payments.view', 'description' => 'View payments', 'module' => 'finance'],
            ['name' => 'payments.view-all', 'description' => 'View all payments', 'module' => 'finance'],
            ['name' => 'payments.approve', 'description' => 'Approve payments', 'module' => 'finance'],
            ['name' => 'payments.process', 'description' => 'Process payments (banking)', 'module' => 'finance'],

            ['name' => 'wht.manage', 'description' => 'Manage WHT certificates', 'module' => 'finance'],

            // Budget
            ['name' => 'budget.view', 'description' => 'View budgets', 'module' => 'budget'],
            ['name' => 'budget.manage', 'description' => 'Manage budget lines', 'module' => 'budget'],
            ['name' => 'budget.approve', 'description' => 'Approve budget allocations', 'module' => 'budget'],

            // Reports
            ['name' => 'reports.procurement', 'description' => 'View procurement reports', 'module' => 'reports'],
            ['name' => 'reports.financial', 'description' => 'View financial reports', 'module' => 'reports'],
            ['name' => 'reports.inventory', 'description' => 'View inventory reports', 'module' => 'reports'],
            ['name' => 'reports.compliance', 'description' => 'View compliance reports', 'module' => 'reports'],
            ['name' => 'reports.audit', 'description' => 'View audit reports', 'module' => 'reports'],

            // Audit Logs
            ['name' => 'audit.view', 'description' => 'View audit logs', 'module' => 'audit'],
            ['name' => 'audit.view-all', 'description' => 'View all audit logs', 'module' => 'audit'],

            // System Administration
            ['name' => 'users.manage', 'description' => 'Manage users', 'module' => 'admin'],
            ['name' => 'roles.manage', 'description' => 'Manage roles', 'module' => 'admin'],
            ['name' => 'permissions.manage', 'description' => 'Manage permissions', 'module' => 'admin'],
            ['name' => 'departments.manage', 'description' => 'Manage departments', 'module' => 'admin'],
            ['name' => 'system.configure', 'description' => 'Configure system settings', 'module' => 'admin'],
        ];

        foreach ($permissions as $permData) {
            Permission::create(array_merge($permData, ['guard_name' => 'web']));
        }

        // 3. Assign Permissions to Roles
        // This mapping enforces segregation of duties

        $this->assignPermissionsToRole('super-admin', [
            // Super admin gets everything
            'requisitions.*',
            'procurement.*',
            'purchase-orders.*',
            'grn.*',
            'inventory.*',
            'suppliers.*',
            'invoices.*',
            'payments.*',
            'wht.*',
            'budget.*',
            'reports.*',
            'audit.*',
            'users.*',
            'roles.*',
            'permissions.*',
            'departments.*',
            'system.*'
        ]);

        $this->assignPermissionsToRole('principal', [
            'requisitions.view-all',
            'requisitions.approve-principal',
            'procurement.view',
            'procurement.approve-award',
            'purchase-orders.view-all',
            'purchase-orders.approve',
            'payments.view-all',
            'payments.approve',
            'budget.view',
            'budget.approve',
            'reports.*',
            'suppliers.view',
            'suppliers.blacklist',
        ]);

        $this->assignPermissionsToRole('deputy-principal', [
            'requisitions.view-all',
            'requisitions.approve-budget',
            'procurement.view',
            'purchase-orders.view-all',
            'budget.view',
            'budget.approve',
            'reports.procurement',
            'reports.financial',
            'reports.inventory',
        ]);

        $this->assignPermissionsToRole('finance-manager', [
            'invoices.view',
            'invoices.verify',
            'invoices.approve',
            'payments.view-all',
            'payments.create',
            'payments.approve',
            'payments.process',
            'wht.manage',
            'budget.view',
            'budget.manage',
            'requisitions.view-all',
            'purchase-orders.view-all',
            'reports.financial',
            'reports.compliance',
            'reports.procurement',
        ]);

        $this->assignPermissionsToRole('accountant', [
            'invoices.create',
            'invoices.view',
            'invoices.verify',
            'payments.create',
            'payments.view',
            'wht.manage',
            'budget.view',
            'requisitions.view',
            'purchase-orders.view',
            'grn.view',
            'reports.financial',
        ]);

        $this->assignPermissionsToRole('procurement-officer', [
            'requisitions.view-all',
            'procurement.create',
            'procurement.view',
            'procurement.edit',
            'procurement.issue-rfq',
            'procurement.evaluate-bids',
            'procurement.recommend-award',
            'purchase-orders.create',
            'purchase-orders.view-all',
            'purchase-orders.edit',
            'purchase-orders.issue',
            'suppliers.create',
            'suppliers.view',
            'suppliers.edit',
            'suppliers.rate',
            'reports.procurement',
        ]);

        $this->assignPermissionsToRole('procurement-assistant', [
            'requisitions.view',
            'procurement.create',
            'procurement.view',
            'procurement.edit',
            'procurement.issue-rfq',
            'purchase-orders.view',
            'suppliers.view',
        ]);

        $this->assignPermissionsToRole('stores-manager', [
            'grn.create',
            'grn.view',
            'grn.inspect',
            'grn.post',
            'inventory.view',
            'inventory.manage',
            'inventory.issue',
            'inventory.adjust',
            'inventory.transfer',
            'inventory.count',
            'purchase-orders.view',
            'reports.inventory',
        ]);

        $this->assignPermissionsToRole('hod', [
            'requisitions.create',
            'requisitions.view',
            'requisitions.edit',
            'requisitions.approve-hod',
            'purchase-orders.view',
            'inventory.view',
            'budget.view',
            'reports.procurement',
        ]);

        $this->assignPermissionsToRole('budget-owner', [
            'requisitions.view',
            'requisitions.approve-budget',
            'budget.view',
            'reports.financial',
        ]);

        $this->assignPermissionsToRole('staff', [
            'requisitions.create',
            'requisitions.view',
            'requisitions.edit',
            'inventory.view',
        ]);

        $this->assignPermissionsToRole('auditor', [
            'requisitions.view-all',
            'procurement.view',
            'purchase-orders.view-all',
            'grn.view',
            'inventory.view',
            'suppliers.view',
            'invoices.view',
            'payments.view-all',
            'budget.view',
            'reports.*',
            'audit.view-all',
        ]);
    }

    /**
     * Helper to assign permissions to a role based on pattern matching
     */
    private function assignPermissionsToRole(string $roleSlug, array $permissionPatterns): void
    {
        // Find existing role using Spatie model, query by custom slug or create
        $role = Role::where('slug', $roleSlug)->where('guard_name', 'web')->first();
        if (!$role) return;

        $allPermissions = Permission::where('guard_name', 'web')->get();
        $permissionsToAssign = [];

        foreach ($permissionPatterns as $pattern) {
            if (str_ends_with($pattern, '.*')) {
                // Module wildcard
                $module = str_replace('.*', '', $pattern);
                $matching = $allPermissions->where('module', $module);
            } else {
                // Exact match
                $matching = $allPermissions->where('name', $pattern);
            }

            foreach ($matching as $perm) {
                $permissionsToAssign[] = $perm;
            }
        }

        if (!empty($permissionsToAssign)) {
            $role->syncPermissions($permissionsToAssign);
        }
    }
}
