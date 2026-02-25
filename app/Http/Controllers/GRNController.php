<?php

namespace App\Http\Controllers;

use App\Modules\GRN\Models\GoodsReceivedNote as GRN;
use App\Modules\PurchaseOrders\Models\PurchaseOrder;
use App\Services\GRNService;
use App\Services\InventoryService;
use App\Core\Workflow\WorkflowEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GRNController extends Controller
{
    public function __construct(
        private GRNService $grnService,
        private InventoryService $inventoryService,
        private WorkflowEngine $workflowEngine
    ) {}

    /**
     * Display all GRNs
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', GRN::class);

        $filters = [
            'status' => $request->get('status'),
            'purchase_order_id' => $request->get('purchase_order_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];

        $grns = $this->grnService->getAllGRNs($filters, 15);

        return view('grn.index', compact('grns'));
    }

    /**
     * Show create GRN form
     */
    public function create(Request $request)
    {
        $this->authorize('create', GRN::class);

        $poId = $request->get('po_id');
        $purchaseOrder = $poId ? PurchaseOrder::findOrFail($poId) : null;

        $purchaseOrders = PurchaseOrder::where('status', '!=', 'cancelled')
            ->get();

        return view('grn.create', compact('purchaseOrder', 'purchaseOrders'));
    }

    /**
     * Store new GRN
     */
    public function store(Request $request)
    {
        $this->authorize('create', GRN::class);

        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'received_date' => 'sometimes|date', // Changed grn_date to received_date to match service
            'received_by' => 'sometimes|string',
            'items' => 'required|array',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.condition' => 'required|in:good,damaged,expired',
            'delivery_notes' => 'nullable|string',
        ]);

        try {
            $purchaseOrder = PurchaseOrder::findOrFail($validated['purchase_order_id']);
            $grn = $this->grnService->createGRN($purchaseOrder, $validated);

            return redirect()->route('grn.show', $grn)
                ->with('success', 'Goods Received Note created');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create GRN: ' . $e->getMessage());
        }
    }

    /**
     * Display GRN details
     */
    public function show(GRN $grn)
    {
        $this->authorize('view', $grn);

        $items = $grn->items()->get();
        $purchaseOrder = $grn->purchaseOrder;
        $discrepancies = $grn->discrepancies()->get();
        
        // Check if relationship exists before accessing
        $inspection = $grn->inspection ?? null; 

        return view('grn.show', compact('grn', 'items', 'purchaseOrder', 'discrepancies', 'inspection'));
    }

    /**
     * Show edit form for pending GRN
     */
    public function edit(GRN $grn)
    {
        $this->authorize('update', $grn);

        if ($grn->status !== 'received' && $grn->status !== 'pending') {
            return back()->with('error', 'Only pending GRNs can be edited');
        }

        return view('grn.edit', compact('grn'));
    }

    /**
     * Update pending GRN
     */
    public function update(Request $request, GRN $grn)
    {
        $this->authorize('update', $grn);

        if ($grn->status !== 'received' && $grn->status !== 'pending') {
             return back()->with('error', 'Only pending GRNs can be updated');
        }

        $validated = $request->validate([
            'items.*.quantity_received' => 'required|numeric|min:0',
            // 'items.*.quantity_accepted' => 'required|numeric|min:0', // Removed as this is usually inspection phase
            // 'items.*.quantity_rejected' => 'required|numeric|min:0',
             'notes' => 'nullable|string',
        ]);

        try {
            $updated = $this->grnService->updateGRN($grn, $validated);

            return redirect()->route('grn.show', $updated)
                ->with('success', 'GRN updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update GRN: ' . $e->getMessage());
        }
    }

    /**
     * Delete pending GRN
     */
    public function destroy(GRN $grn)
    {
        $this->authorize('delete', $grn);

        if ($grn->status !== 'received' && $grn->status !== 'pending') {
             return back()->with('error', 'Only pending GRNs can be deleted');
        }

        try {
            $grn->delete();

            return redirect()->route('grn.index')
                ->with('success', 'GRN deleted');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete GRN: ' . $e->getMessage());
        }
    }

    /**
     * Show inspection form
     */
    public function inspectForm(GRN $grn)
    {
        $this->authorize('inspect', $grn);

        // Allow inspection if status is 'received' or 'pending' (or whatever initial status is)
        // Adjust status check logic as needed based on workflow
        if ($grn->status === 'approved' || $grn->status === 'posted') {
             return back()->with('error', 'GRN already processed');
        }

        $items = $grn->items()->get();

        return view('grn.inspect', compact('grn', 'items'));
    }

    /**
     * Record quality inspection results
     */
    public function recordInspection(Request $request, GRN $grn)
    {
        $this->authorize('inspect', $grn);

        $validated = $request->validate([
            'items.*.quality_pass' => 'required|boolean', // Changed to match service expectation (quality_pass vs status)
             // Service expects: ['quality_pass' => bool, 'notes' => string]
            'items.*.notes' => 'nullable|string',
            'inspection_notes' => 'nullable|string',
        ]);
        
        // Transform validation to match service expectation if needed, or update service.
        // Service expects 'quality_pass' key in item array.

        try {
            $this->grnService->recordInspection($grn, $validated); // Removed extra user arg

            return redirect()->route('grn.show', $grn)
                ->with('success', 'Inspection recorded successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to record inspection: ' . $e->getMessage());
        }
    }

    /**
     * Post GRN to inventory
     */
    public function postToInventory(Request $request, GRN $grn)
    {
        $this->authorize('postToInventory', $grn);

        if ($grn->status !== 'inspected') {
            return back()->with('error', 'GRN must be inspected before posting to inventory');
        }

        try {
            $this->grnService->postToInventory($grn);

            return back()->with('success', 'GRN posted to inventory');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to post to inventory: ' . $e->getMessage());
        }
    }

    /**
     * Display discrepancies
     */
    public function discrepancies(GRN $grn)
    {
        $this->authorize('view', $grn);

        $discrepancies = $grn->discrepancies()->get();
        $totalAmount = $discrepancies->sum('amount');

        return view('grn.discrepancies', compact('grn', 'discrepancies', 'totalAmount'));
    }

    /**
     * Show acceptance form for an approved GRN
     */
    public function showAcceptForm(GRN $grn)
    {
        $this->authorize('view', $grn);

        if (!$grn->canBeAccepted()) {
            return back()->with('error', 'This GRN is not pending acceptance.');
        }

        $items = $grn->items()->get();

        return view('grn.accept', compact('grn', 'items'));
    }

    /**
     * Accept a delivered GRN (department end-user acceptance)
     */
    public function accept(Request $request, GRN $grn)
    {
        $this->authorize('view', $grn);

        if (!$grn->canBeAccepted()) {
            return back()->with('error', 'This GRN cannot be accepted in its current state.');
        }

        $validated = $request->validate([
            'acceptance_decision' => 'required|in:accepted,partially_accepted',
            'acceptance_notes'    => 'nullable|string|max:2000',
            'completion_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        // Enforce expiry dates: block acceptance if any item expires within 30 days
        $thresholdDate = now()->addDays(30);
        foreach ($grn->items as $item) {
            if ($item->quantity_received > 0 && $item->expiry_date && $item->expiry_date < $thresholdDate) {
                return back()->with('error', "Cannot accept GRN. Item '{$item->description}' expires too soon ({$item->expiry_date->format('Y-m-d')}). Please reject the delivery or request a replacement.");
            }
        }

        $certificatePath = null;
        if ($request->hasFile('completion_certificate')) {
            $certificatePath = $request->file('completion_certificate')
                ->store('grn/certificates', 'public');
        }

        $acceptanceStatus = $validated['acceptance_decision'];

        $grn->update([
            'acceptance_status'           => $acceptanceStatus,
            'accepted_by'                 => auth()->id(),
            'accepted_at'                 => now(),
            'acceptance_notes'            => $validated['acceptance_notes'] ?? null,
            'completion_certificate_path' => $certificatePath,
        ]);

        $this->workflowEngine->transition(
            $grn,
            'grn',
            $grn->getOriginal('status'),
            'accepted',
            "Department acceptance: {$acceptanceStatus}",
            ['accepted_by' => auth()->id(), 'acceptance_status' => $acceptanceStatus]
        );

        return redirect()->route('grn.show', $grn)
            ->with('success', 'Delivery accepted successfully.');
    }

    /**
     * Reject acceptance of a delivered GRN
     */
    public function rejectAcceptance(Request $request, GRN $grn)
    {
        $this->authorize('view', $grn);

        if (!$grn->canBeAccepted()) {
            return back()->with('error', 'This GRN cannot be rejected in its current state.');
        }

        $validated = $request->validate([
            'acceptance_notes' => 'required|string|min:10|max:2000',
        ]);

        $grn->update([
            'acceptance_status' => 'rejected',
            'accepted_by'       => auth()->id(),
            'accepted_at'       => now(),
            'acceptance_notes'  => $validated['acceptance_notes'],
        ]);

        $this->workflowEngine->transition(
            $grn,
            'grn',
            $grn->getOriginal('status'),
            'acceptance_rejected',
            "Department rejected acceptance: {$validated['acceptance_notes']}",
            ['rejected_by' => auth()->id()]
        );

        return redirect()->route('grn.show', $grn)
            ->with('warning', 'Delivery acceptance rejected. Procurement team has been notified.');
    }

    /**
     * Record discrepancy
     */
    public function recordDiscrepancy(Request $request, GRN $grn)
    {
        $this->authorize('update', $grn);

        $validated = $request->validate([
            'type' => 'required|in:shortage,overage,damage,quality',
            'item_id' => 'required|exists:catalog_items,id',
            'quantity' => 'required|numeric',
            'amount' => 'required|numeric',
            'description' => 'required|string|min:10',
        ]);

        try {
            $this->grnService->recordDiscrepancy($grn, $validated);

            return back()->with('success', 'Discrepancy recorded');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to record discrepancy: ' . $e->getMessage());
        }
    }
}
