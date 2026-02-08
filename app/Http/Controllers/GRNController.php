<?php

namespace App\Http\Controllers;

use App\Models\GRN;
use App\Models\PurchaseOrder;
use App\Services\GRNService;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class GRNController extends Controller
{
    public function __construct(
        private GRNService $grnService,
        private InventoryService $inventoryService
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
            'grn_date' => 'required|date',
            'received_by' => 'required|string',
            'items.*.item_id' => 'required|exists:item_categories,id',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.quantity_accepted' => 'required|numeric|min:0',
            'items.*.quantity_rejected' => 'required|numeric|min:0',
            'items.*.condition' => 'required|in:good,damaged,expired',
            'notes' => 'nullable|string',
        ]);

        try {
            $grn = $this->grnService->createGRN($validated, auth()->user());

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
        $inspection = $grn->inspection;

        return view('grn.show', compact('grn', 'items', 'purchaseOrder', 'discrepancies', 'inspection'));
    }

    /**
     * Show edit form for pending GRN
     */
    public function edit(GRN $grn)
    {
        $this->authorize('update', $grn);

        if ($grn->status !== 'pending') {
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

        if ($grn->status !== 'pending') {
            return back()->with('error', 'Only pending GRNs can be updated');
        }

        $validated = $request->validate([
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.quantity_accepted' => 'required|numeric|min:0',
            'items.*.quantity_rejected' => 'required|numeric|min:0',
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

        if ($grn->status !== 'pending') {
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

        if ($grn->status !== 'pending') {
            return back()->with('error', 'GRN must be pending for inspection');
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
            'items.*.inspection_status' => 'required|in:pass,fail,partial',
            'items.*.notes' => 'nullable|string',
            'inspection_notes' => 'nullable|string',
        ]);

        try {
            $this->grnService->recordInspection($grn, $validated, auth()->user());

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
            $this->grnService->postToInventory($grn, auth()->user());

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
     * Record discrepancy
     */
    public function recordDiscrepancy(Request $request, GRN $grn)
    {
        $this->authorize('update', $grn);

        $validated = $request->validate([
            'type' => 'required|in:shortage,overage,damage,quality',
            'item_id' => 'required|exists:item_categories,id',
            'quantity' => 'required|numeric',
            'amount' => 'required|numeric',
            'description' => 'required|string|min:10',
        ]);

        try {
            $this->grnService->recordDiscrepancy($grn, $validated, auth()->user());

            return back()->with('success', 'Discrepancy recorded');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to record discrepancy: ' . $e->getMessage());
        }
    }
}
