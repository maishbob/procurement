<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class PurchaseOrderController extends Controller
{
    public function __construct(private PurchaseOrderService $poService) {}

    /**
     * Display all purchase orders
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $filters = [
            'status' => $request->get('status'),
            'supplier_id' => $request->get('supplier_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];

        $purchaseOrders = $this->poService->getAllPurchaseOrders($filters, 15);

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    /**
     * Show create PO form (from requisition)
     */
    public function create(Request $request)
    {
        $this->authorize('create', PurchaseOrder::class);

        $requisitionId = $request->get('requisition_id');
        $requisition = $requisitionId ? Requisition::findOrFail($requisitionId) : null;

        $suppliers = \App\Models\Supplier::where('is_blacklisted', false)
            ->where('is_approved', true)
            ->get();

        return view('purchase-orders.create', compact('requisition', 'suppliers'));
    }

    /**
     * Store new purchase order
     */
    public function store(Request $request)
    {
        $this->authorize('create', PurchaseOrder::class);

        $validated = $request->validate([
            'requisition_id' => 'required|exists:requisitions,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'po_date' => 'required|date',
            'expected_delivery_date' => 'required|date|after:po_date',
            'delivery_address' => 'required|string',
            'items.*.item_id' => 'required|exists:item_categories,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.description' => 'nullable|string',
            'currency' => 'required|in:KES,USD,GBP,EUR',
            'fx_rate' => 'nullable|numeric',
        ]);

        try {
            $purchaseOrder = $this->poService->createPurchaseOrder(
                $validated,
                auth()->user()
            );

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase Order created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create PO: ' . $e->getMessage());
        }
    }

    /**
     * Display PO details
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        $items = $purchaseOrder->items()->get();
        $supplier = $purchaseOrder->supplier;
        $grns = $purchaseOrder->grns()->get();

        return view('purchase-orders.show', compact('purchaseOrder', 'items', 'supplier', 'grns'));
    }

    /**
     * Show edit form for draft PO
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Only draft POs can be edited');
        }

        $suppliers = \App\Models\Supplier::where('is_blacklisted', false)
            ->where('is_approved', true)
            ->get();

        return view('purchase-orders.edit', compact('purchaseOrder', 'suppliers'));
    }

    /**
     * Update draft PO
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Only draft POs can be updated');
        }

        $validated = $request->validate([
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'expected_delivery_date' => 'sometimes|date',
            'delivery_address' => 'sometimes|string',
            'items.*.item_id' => 'required|exists:item_categories,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0.01',
        ]);

        try {
            $updated = $this->poService->updatePurchaseOrder($purchaseOrder, $validated);

            return redirect()->route('purchase-orders.show', $updated)
                ->with('success', 'Purchase Order updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update PO: ' . $e->getMessage());
        }
    }

    /**
     * Delete draft PO
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('delete', $purchaseOrder);

        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Only draft POs can be deleted');
        }

        try {
            $purchaseOrder->delete();

            return redirect()->route('purchase-orders.index')
                ->with('success', 'Purchase Order deleted');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete PO: ' . $e->getMessage());
        }
    }

    /**
     * Issue purchase order to supplier
     */
    public function issue(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('issue', $purchaseOrder);

        try {
            $this->poService->issuePurchaseOrder($purchaseOrder, auth()->user());

            return back()->with('success', 'Purchase Order issued to supplier');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to issue PO: ' . $e->getMessage());
        }
    }

    /**
     * Record supplier acknowledgment
     */
    public function acknowledge(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'delivery_date' => 'required|date|after:today',
            'notes' => 'nullable|string',
        ]);

        try {
            $purchaseOrder->update([
                'supplier_acknowledge_date' => now(),
                'supplier_acknowledged' => true,
                'expected_delivery_date' => $validated['delivery_date'],
            ]);

            return back()->with('success', 'Acknowledgment recorded');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to record acknowledgment: ' . $e->getMessage());
        }
    }

    /**
     * Cancel purchase order
     */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('cancel', $purchaseOrder);

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:10',
        ]);

        try {
            $this->poService->cancelPurchaseOrder(
                $purchaseOrder,
                $validated['cancellation_reason'],
                auth()->user()
            );

            return back()->with('success', 'Purchase Order cancelled');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to cancel PO: ' . $e->getMessage());
        }
    }

    /**
     * Download PO as PDF
     */
    public function downloadPDF(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        try {
            $items = $purchaseOrder->items()->get();
            $supplier = $purchaseOrder->supplier;

            $pdf = Pdf::loadView('purchase-orders.pdf', compact('purchaseOrder', 'items', 'supplier'))
                ->setPaper('a4')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10);

            return $pdf->download("po-{$purchaseOrder->po_number}.pdf");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Show email modal
     */
    public function emailModal(PurchaseOrder $purchaseOrder)
    {
        return view('purchase-orders.email-modal', compact('purchaseOrder'));
    }

    /**
     * Send PO to supplier via email
     */
    public function sendEmail(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            $items = $purchaseOrder->items()->get();
            $supplier = $purchaseOrder->supplier;

            $pdf = Pdf::loadView('purchase-orders.pdf', compact('purchaseOrder', 'items', 'supplier'));
            $pdfContent = $pdf->output();

            Mail::send([], [], function ($message) use ($validated, $pdfContent, $purchaseOrder) {
                $message->to($validated['email'])
                    ->subject($validated['subject'])
                    ->setBody($validated['message'], 'text/plain')
                    ->attachData($pdfContent, "PO-{$purchaseOrder->po_number}.pdf", [
                        'mime' => 'application/pdf',
                    ]);
            });

            \Log::info('PO email sent', [
                'po_number' => $purchaseOrder->po_number,
                'to'        => $validated['email'],
                'sent_by'   => auth()->id(),
            ]);

            return back()->with('success', 'Purchase Order sent to supplier successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send PO: ' . $e->getMessage());
        }
    }

    /**
     * Get PO items via AJAX
     */
    public function getItems(PurchaseOrder $purchaseOrder)
    {
        $items = $purchaseOrder->items()
            ->with(['requisitionItem'])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id, // This is the PO Item ID
                    'item_id' => $item->requisitionItem->catalog_item_id, // This is the Catalog Item ID
                    'description' => $item->description,
                    'po_quantity' => $item->quantity,
                    'received_quantity' => $item->quantity_received,
                    'unit_price' => $item->unit_price,
                    // 'remaining_quantity' => $item->quantity - $item->quantity_received
                ];
            });

        return response()->json($items);
    }
}
