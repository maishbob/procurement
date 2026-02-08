<?php

namespace App\Http\Controllers;

use App\Models\ProcurementProcess;
use App\Models\SupplierBid;
use App\Services\ProcurementService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ProcurementController extends Controller
{
    public function __construct(
        private ProcurementService $procurementService,
        private NotificationService $notificationService
    ) {}

    /**
     * Display procurement dashboard with all process types
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ProcurementProcess::class);

        $tab = $request->get('tab', 'rfq');

        switch($tab) {
            case 'rfp':
                return redirect()->route('procurement.indexRFP');
            case 'tender':
                return redirect()->route('procurement.indexTender');
            case 'bids':
                return redirect()->route('procurement.indexBids');
            default:
                return redirect()->route('procurement.indexRFQ');
        }
    }

    // ========== RFQ METHODS ==========

    /**
     * List all RFQs
     */
    public function indexRFQ(Request $request)
    {
        $filters = [
            'status' => $request->get('status'),
            'requisition_id' => $request->get('requisition_id'),
        ];

        $rfqs = $this->procurementService->getAllProcesses('RFQ', $filters, 15);

        return view('procurement.rfq.index', compact('rfqs'));
    }

    /**
     * Show create RFQ form
     */
    public function createRFQ(Request $request)
    {
        $requisitionId = $request->get('requisition_id');
        $requisition = $requisitionId ? \App\Models\Requisition::find($requisitionId) : null;

        return view('procurement.rfq.create', compact('requisition'));
    }

    /**
     * Store new RFQ
     */
    public function storeRFQ(Request $request)
    {
        $validated = $request->validate([
            'requisition_id' => 'required|exists:requisitions,id',
            'process_name' => 'required|string|max:255',
            'description' => 'required|string',
            'quote_deadline' => 'required|date|after:today',
            'supplier_ids' => 'required|array|min:1',
            'supplier_ids.*' => 'exists:suppliers,id',
            'evaluation_criteria' => 'nullable|array',
        ]);

        try {
            $process = $this->procurementService->createRFQ($validated, auth()->user());

            return redirect()->route('procurement.rfq.show', $process)
                ->with('success', 'RFQ created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create RFQ: ' . $e->getMessage());
        }
    }

    /**
     * Display RFQ details
     */
    public function showRFQ(ProcurementProcess $process)
    {
        if ($process->process_type !== 'RFQ') {
            return back()->with('error', 'Invalid process type');
        }

        $bids = $process->bids()->get();
        $requisition = $process->requisition;

        return view('procurement.rfq.show', compact('process', 'bids', 'requisition'));
    }

    /**
     * Show edit RFQ form
     */
    public function editRFQ(ProcurementProcess $process)
    {
        if ($process->process_type !== 'RFQ' || $process->status !== 'draft') {
            return back()->with('error', 'Only draft RFQs can be edited');
        }

        return view('procurement.rfq.edit', compact('process'));
    }

    /**
     * Update RFQ
     */
    public function updateRFQ(Request $request, ProcurementProcess $process)
    {
        if ($process->process_type !== 'RFQ' || $process->status !== 'draft') {
            return back()->with('error', 'Only draft RFQs can be updated');
        }

        $validated = $request->validate([
            'process_name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'quote_deadline' => 'sometimes|date|after:today',
        ]);

        try {
            $process->update($validated);

            return redirect()->route('procurement.rfq.show', $process)
                ->with('success', 'RFQ updated');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    /**
     * Publish RFQ to suppliers
     */
    public function publishRFQ(ProcurementProcess $process)
    {
        if ($process->process_type !== 'RFQ' || $process->status !== 'draft') {
            return back()->with('error', 'Only draft RFQs can be published');
        }

        try {
            $this->procurementService->publishRFQ($process);

            return back()->with('success', 'RFQ published to suppliers');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to publish: ' . $e->getMessage());
        }
    }

    /**
     * Close RFQ (stop accepting bids)
     */
    public function closeRFQ(ProcurementProcess $process)
    {
        if ($process->process_type !== 'RFQ' || !in_array($process->status, ['rfq_issued', 'bids_received'])) {
            return back()->with('error', 'Only open RFQs can be closed');
        }

        try {
            $process->update(['status' => 'evaluation']);

            return back()->with('success', 'RFQ closed to new bids');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to close: ' . $e->getMessage());
        }
    }

    // ========== RFP METHODS ==========

    /**
     * List all RFPs
     */
    public function indexRFP(Request $request)
    {
        $filters = [
            'status' => $request->get('status'),
            'requisition_id' => $request->get('requisition_id'),
        ];

        $rfps = $this->procurementService->getAllProcesses('RFP', $filters, 15);

        return view('procurement.rfp.index', compact('rfps'));
    }

    /**
     * Show create RFP form
     */
    public function createRFP(Request $request)
    {
        $requisitionId = $request->get('requisition_id');
        $requisition = $requisitionId ? \App\Models\Requisition::find($requisitionId) : null;

        return view('procurement.rfp.create', compact('requisition'));
    }

    /**
     * Store new RFP
     */
    public function storeRFP(Request $request)
    {
        $validated = $request->validate([
            'requisition_id' => 'required|exists:requisitions,id',
            'process_name' => 'required|string|max:255',
            'description' => 'required|string',
            'proposal_deadline' => 'required|date|after:today',
            'supplier_ids' => 'required|array|min:1',
            'supplier_ids.*' => 'exists:suppliers,id',
            'evaluation_criteria' => 'nullable|array',
        ]);

        try {
            $process = $this->procurementService->createRFP($validated, auth()->user());

            return redirect()->route('procurement.rfp.show', $process)
                ->with('success', 'RFP created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create RFP: ' . $e->getMessage());
        }
    }

    /**
     * Display RFP details
     */
    public function showRFP(ProcurementProcess $process)
    {
        if ($process->process_type !== 'RFP') {
            return back()->with('error', 'Invalid process type');
        }

        $bids = $process->bids()->get();

        return view('procurement.rfp.show', compact('process', 'bids'));
    }

    /**
     * Show edit RFP form
     */
    public function editRFP(ProcurementProcess $process)
    {
        if ($process->process_type !== 'RFP' || $process->status !== 'draft') {
            return back()->with('error', 'Only draft RFPs can be edited');
        }

        return view('procurement.rfp.edit', compact('process'));
    }

    /**
     * Update RFP
     */
    public function updateRFP(Request $request, ProcurementProcess $process)
    {
        if ($process->process_type !== 'RFP' || $process->status !== 'draft') {
            return back()->with('error', 'Only draft RFPs can be updated');
        }

        $validated = $request->validate([
            'process_name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'proposal_deadline' => 'sometimes|date|after:today',
        ]);

        try {
            $process->update($validated);

            return redirect()->route('procurement.rfp.show', $process)
                ->with('success', 'RFP updated');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    /**
     * Publish RFP
     */
    public function publishRFP(ProcurementProcess $process)
    {
        if ($process->process_type !== 'RFP' || $process->status !== 'draft') {
            return back()->with('error', 'Only draft RFPs can be published');
        }

        try {
            $this->procurementService->publishRFP($process);

            return back()->with('success', 'RFP published to suppliers');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to publish: ' . $e->getMessage());
        }
    }

    /**
     * Close RFP
     */
    public function closeRFP(ProcurementProcess $process)
    {
        if ($process->process_type !== 'RFP' || !in_array($process->status, ['rfq_issued', 'bids_received'])) {
            return back()->with('error', 'Only open RFPs can be closed');
        }

        try {
            $process->update(['status' => 'evaluation']);

            return back()->with('success', 'RFP closed');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to close: ' . $e->getMessage());
        }
    }

    // ========== TENDER METHODS ==========

    /**
     * List all tenders
     */
    public function indexTender(Request $request)
    {
        $filters = [
            'status' => $request->get('status'),
            'requisition_id' => $request->get('requisition_id'),
        ];

        $tenders = $this->procurementService->getAllProcesses('Tender', $filters, 15);

        return view('procurement.tender.index', compact('tenders'));
    }

    /**
     * Show create tender form
     */
    public function createTender(Request $request)
    {
        $requisitionId = $request->get('requisition_id');
        $requisition = $requisitionId ? \App\Models\Requisition::find($requisitionId) : null;

        return view('procurement.tender.create', compact('requisition'));
    }

    /**
     * Store new tender
     */
    public function storeTender(Request $request)
    {
        $validated = $request->validate([
            'requisition_id' => 'required|exists:requisitions,id',
            'process_name' => 'required|string|max:255',
            'description' => 'required|string',
            'bid_deadline' => 'required|date|after:today',
            'supplier_ids' => 'required|array|min:1',
            'supplier_ids.*' => 'exists:suppliers,id',
            'evaluation_criteria' => 'nullable|array',
        ]);

        try {
            $process = $this->procurementService->createTender($validated, auth()->user());

            return redirect()->route('procurement.tender.show', $process)
                ->with('success', 'Tender created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create tender: ' . $e->getMessage());
        }
    }

    /**
     * Display tender details
     */
    public function showTender(ProcurementProcess $process)
    {
        if ($process->process_type !== 'Tender') {
            return back()->with('error', 'Invalid process type');
        }

        $bids = $process->bids()->get();
        $evaluations = $process->evaluations()->get();

        return view('procurement.tender.show', compact('process', 'bids', 'evaluations'));
    }

    /**
     * Show edit tender form
     */
    public function editTender(ProcurementProcess $process)
    {
        if ($process->process_type !== 'Tender' || $process->status !== 'draft') {
            return back()->with('error', 'Only draft tenders can be edited');
        }

        return view('procurement.tender.edit', compact('process'));
    }

    /**
     * Update tender
     */
    public function updateTender(Request $request, ProcurementProcess $process)
    {
        if ($process->process_type !== 'Tender' || $process->status !== 'draft') {
            return back()->with('error', 'Only draft tenders can be updated');
        }

        $validated = $request->validate([
            'process_name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'bid_deadline' => 'sometimes|date|after:today',
        ]);

        try {
            $process->update($validated);

            return redirect()->route('procurement.tender.show', $process)
                ->with('success', 'Tender updated');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    /**
     * Publish tender
     */
    public function publishTender(ProcurementProcess $process)
    {
        if ($process->process_type !== 'Tender' || $process->status !== 'draft') {
            return back()->with('error', 'Only draft tenders can be published');
        }

        try {
            $this->procurementService->publishTender($process);

            return back()->with('success', 'Tender published');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to publish: ' . $e->getMessage());
        }
    }

    /**
     * Close tender (stop accepting bids)
     */
    public function closeTender(ProcurementProcess $process)
    {
        if ($process->process_type !== 'Tender' || !in_array($process->status, ['rfq_issued', 'bids_received'])) {
            return back()->with('error', 'Only open tenders can be closed');
        }

        try {
            $process->update(['status' => 'evaluation']);

            return back()->with('success', 'Tender closed to new bids');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to close: ' . $e->getMessage());
        }
    }

    /**
     * Show tender evaluation form
     */
    public function evaluateTender(ProcurementProcess $process)
    {
        if ($process->process_type !== 'Tender' || !in_array($process->status, ['evaluation', 'evaluation_complete'])) {
            return back()->with('error', 'Tender must be closed before evaluation');
        }

        $bids = $process->bids()->get();
        $evaluations = $process->evaluations()->get();

        return view('procurement.tender.evaluate', compact('process', 'bids', 'evaluations'));
    }

    /**
     * Award tender to winning supplier
     */
    public function awardTender(Request $request, ProcurementProcess $process)
    {
        if ($process->process_type !== 'Tender') {
            return back()->with('error', 'Invalid process type');
        }

        $validated = $request->validate([
            'winning_bid_id' => 'required|exists:supplier_bids,id',
            'award_criteria' => 'required|string',
        ]);

        try {
            $this->procurementService->awardTender(
                $process,
                $validated['winning_bid_id'],
                $validated['award_criteria'],
                auth()->user()
            );

            return redirect()->route('procurement.tender.show', $process)
                ->with('success', 'Tender awarded successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to award tender: ' . $e->getMessage());
        }
    }

    // ========== BID METHODS ==========

    /**
     * List all bids
     */
    public function indexBids(Request $request)
    {
        $filters = [
            'process_id' => $request->get('process_id'),
            'supplier_id' => $request->get('supplier_id'),
            'process_type' => $request->get('process_type'),
        ];

        $bids = $this->procurementService->getAllBids($filters, 15);

        return view('procurement.bids.index', compact('bids'));
    }

    /**
     * Display bid details
     */
    public function showBid(SupplierBid $bid)
    {
        $evaluations = $bid->evaluations()->get();

        return view('procurement.bids.show', compact('bid', 'evaluations'));
    }

    /**
     * Show bid evaluation form
     */
    public function evaluateBidForm(SupplierBid $bid)
    {
        $criteria = $bid->procurementProcess->evaluation_criteria ?? [];

        return view('procurement.bids.evaluate', compact('bid', 'criteria'));
    }

    /**
     * Record bid evaluation
     */
    public function recordEvaluation(Request $request, SupplierBid $bid)
    {
        $validated = $request->validate([
            'criterion' => 'required|string',
            'score' => 'required|numeric|min:0|max:100',
            'weight' => 'required|numeric|min:0|max:100',
            'comments' => 'nullable|string',
        ]);

        try {
            $bid->evaluations()->create([
                'criterion' => $validated['criterion'],
                'score' => $validated['score'],
                'weight' => $validated['weight'],
                'comments' => $validated['comments'] ?? '',
                'evaluated_by' => auth()->id(),
                'evaluated_at' => now(),
            ]);

            return back()->with('success', 'Evaluation recorded');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to record evaluation: ' . $e->getMessage());
        }
    }
}
