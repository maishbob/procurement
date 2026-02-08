<?php

namespace App\Http\Controllers;

use App\Models\Requisition;
use App\Models\PurchaseOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DashboardController
 * 
 * Displays the main dashboard with key metrics, recent activities,
 * and quick action items for the authenticated user.
 * 
 * Tailored to show relevant information based on user's role and permissions.
 */
class DashboardController extends Controller
{
    /**
     * Show the application dashboard
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        return view('dashboard.index', [
            'stats' => [
                'pending_approvals' => 0,
                'active_requisitions' => 0,
                'budget_utilization' => 0,
                'low_stock_items' => 0,
            ],
            'recentRequisitions' => [],
            'pendingApprovals' => [],
            'lowStockItems' => [],
            'pendingInvoices' => [],
            'budgetStatus' => [],
            'activityFeed' => [],
            'fiscalYear' => date('Y'),
        ]);
    }

    /**
     * Get dashboard statistics
     * 
     * Returns key metrics displayed in stat cards:
     * - Pending approvals count
     * - Active requisitions count
     * - Budget utilization percentage
     * - Low stock items count
     */
    private function getStats($user, $fiscalYear): array
    {
        $stats = [
            'pending_approvals' => 0,
            'active_requisitions' => 0,
            'budget_utilization' => 0,
            'low_stock_items' => 0,
            'pending_invoices' => 0,
            'pending_payments' => 0,
        ];

        // Pending approvals - if user is an approver
        if ($user->hasPermission('approve_requisition')) {
            $stats['pending_approvals'] = Requisition::whereHas('approvals', function ($q) {
                $q->where('status', 'pending')
                    ->where('required_level', $this->getApprovalLevel($q->getModel()->user));
            })->count();
        }

        // Active requisitions - user's own or all if have permission
        if ($user->hasPermission('view_all_requisitions')) {
            $stats['active_requisitions'] = Requisition::whereNotIn('status', ['rejected', 'cancelled'])
                ->count();
        } else {
            $stats['active_requisitions'] = Requisition::where('created_by', $user->id)
                ->whereNotIn('status', ['rejected', 'cancelled'])
                ->count();
        }

        // Budget utilization
        if ($user->hasPermission('view_budget')) {
            $budgetUtilization = DB::table('budget_lines')
                ->where('fiscal_year', $fiscalYear)
                ->selectRaw('
                    CASE 
                        WHEN SUM(allocated_amount) = 0 THEN 0
                        ELSE ROUND((SUM(committed_amount + spent_amount) / SUM(allocated_amount)) * 100, 2)
                    END as utilization_percentage
                ')
                ->value('utilization_percentage');
            $stats['budget_utilization'] = $budgetUtilization ?? 0;
        }

        // Low stock items
        if ($user->hasPermission('view_inventory')) {
            $stats['low_stock_items'] = InventoryItem::whereHas('stockLevels', function($q) {
                $q->whereRaw('stock_levels.quantity_on_hand <= inventory_items.reorder_point');
            })->count();
        }

        // Pending invoices - if user is in finance
        if ($user->hasPermission('view_invoices') || $user->hasRole('finance_manager')) {
            $stats['pending_invoices'] = Invoice::where('status', '!=', 'paid')
                ->where('status', '!=', 'rejected')
                ->count();
        }

        // Pending payments - if user is approver
        if ($user->hasPermission('approve_payment')) {
            $stats['pending_payments'] = Payment::where('status', 'pending_approval')->count();
        }

        return $stats;
    }

    /**
     * Get recent requisitions
     */
    private function getRecentRequisitions($user, $limit = 5)
    {
        $query = Requisition::with('creator', 'department')
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        // Filter by user if not admin
        if (!$user->hasPermission('view_all_requisitions')) {
            $query->where('created_by', $user->id);
        }

        return $query->get();
    }

    /**
     * Get pending approvals for the user
     */
    private function getPendingApprovals($user, $limit = 5)
    {
        // Get requisitions pending user's approval
        $approvalLevel = $this->getApprovalLevel($user);

        return Requisition::whereHas('approvals', function ($query) use ($user, $approvalLevel) {
            $query->where('status', 'pending')
                ->where('required_level', $approvalLevel);
        })
            ->with('creator', 'department')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get low stock items
     */
    private function getLowStockItems($limit = 5)
    {
        return InventoryItem::whereHas('stockLevels', function($q) {
            $q->whereRaw('stock_levels.quantity_on_hand <= inventory_items.reorder_point');
        })
            ->with(['stockLevels' => function($q) { }])
            ->limit($limit)
            ->get();
    }

    /**
     * Get pending invoices
     */
    private function getPendingInvoices($user, $limit = 5)
    {
        return Invoice::whereNotIn('status', ['paid', 'rejected'])
            ->with('supplier', 'purchaseOrder')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get budget utilization status
     */
    private function getBudgetStatus($user)
    {
        if (!$user->hasPermission('view_budget')) {
            return [];
        }

        $fiscalYear = session('current_fiscal_year');

        return DB::table('budget_lines')
            ->where('fiscal_year', $fiscalYear)
            ->selectRaw('
                department_id,
                SUM(allocated_amount) as allocated,
                SUM(committed_amount) as committed,
                SUM(spent_amount) as spent,
                ROUND((SUM(committed_amount + spent_amount) / SUM(allocated_amount)) * 100, 2) as utilization_percentage
            ')
            ->groupBy('department_id')
            ->limit(5)
            ->get();
    }

    /**
     * Get recent activity feed
     */
    private function getActivityFeed($user, $limit = 10)
    {
        // Get from audit logs ordered by created_at
        return DB::table('audit_logs')
            ->where('user_id', $user->id)
            ->selectRaw('
                id,
                action,
                model_type,
                model_id,
                created_at,
                status_code,
                CONCAT(action, " - ", model_type) as description
            ')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Determine user's approval level based on role
     */
    private function getApprovalLevel($user): string
    {
        if ($user->hasRole('chief_executive')) {
            return 'ceo';
        } elseif ($user->hasRole('principal')) {
            return 'principal';
        } elseif ($user->hasRole('head_of_department')) {
            return 'hod';
        } else {
            return 'requester';
        }
    }

    /**
     * Get dashboard statistics via AJAX
     */
    public function getStatsAjax(Request $request)
    {
        $user = auth()->user();
        $fiscalYear = session('current_fiscal_year');

        $stats = $this->getStats($user, $fiscalYear);

        return response()->json($stats);
    }

    /**
     * Get user notifications
     */
    public function notifications(Request $request)
    {
        $notifications = auth()->user()->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationRead($notificationId)
    {
        $notification = auth()->user()->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Delete notification
     */
    public function deleteNotification($notificationId)
    {
        auth()->user()->notifications()->find($notificationId)?->delete();

        return response()->json(['success' => true]);
    }
}
