<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    /**
     * Display inventory dashboard with stock status
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', InventoryItem::class);

        $storeId = $request->get('store_id') ?? 1;

        $stats = [
            'total_items' => InventoryItem::whereHas('stockLevels', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })->count(),
            'out_of_stock' => InventoryItem::whereHas('stockLevels', function($q) use ($storeId) {
                $q->where('store_id', $storeId)->where('quantity_on_hand', 0);
            })->count(),
            'low_stock' => InventoryItem::whereHas('stockLevels', function($q) use ($storeId) {
                $q->where('store_id', $storeId)
                  ->where('quantity_on_hand', '>', 0)
                  ->whereRaw('stock_levels.quantity_on_hand <= inventory_items.reorder_point');
            })->count(),
            'adequate_stock' => InventoryItem::whereHas('stockLevels', function($q) use ($storeId) {
                $q->where('store_id', $storeId)
                  ->whereRaw('stock_levels.quantity_on_hand > inventory_items.reorder_point');
            })->count(),
        ];

        $filters = [
            'store_id' => $storeId,
            'status' => $request->get('status'),
            'search' => $request->get('search'),
        ];

        $items = $this->inventoryService->getInventoryItems($filters, 15);

        return view('inventory.index', compact('items', 'stats'));
    }

    /**
     * Show form to create new inventory item
     */
    public function create()
    {
        $this->authorize('create', InventoryItem::class);

        $categories = \App\Models\ItemCategory::orderBy('name')->get();
        $suppliers = \App\Models\Supplier::orderBy('name')->get();

        return view('inventory.create', compact('categories', 'suppliers'));
    }

    /**
     * Store a newly created inventory item
     */
    public function store(Request $request)
    {
        $this->authorize('create', InventoryItem::class);

        $validated = $request->validate([
            'item_code' => 'required|string|unique:inventory_items,item_code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:item_categories,id',
            'unit_of_measure' => 'required|string|max:20',
            'is_consumable' => 'boolean',
            'is_asset' => 'boolean',
            'reorder_point' => 'nullable|numeric|min:0',
            'minimum_stock_level' => 'nullable|numeric|min:0',
            'maximum_stock_level' => 'nullable|numeric|min:0',
            'standard_cost' => 'nullable|numeric|min:0',
            'is_vatable' => 'boolean',
            'preferred_supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        try {
            $item = InventoryItem::create($validated);

            return redirect()->route('inventory.show', $item)
                ->with('success', "Inventory item '{$item->name}' created successfully");
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create inventory item: ' . $e->getMessage());
        }
    }

    /**
     * Display item details and transaction history
     */
    public function show(InventoryItem $inventoryItem)
    {
        $this->authorize('view', $inventoryItem);

        $transactions = $inventoryItem->transactions()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $reorderSuggestion = $this->inventoryService->getReorderSuggestion($inventoryItem);
        $valuation = $this->inventoryService->calculateItemValuation($inventoryItem);

        return view('inventory.show', compact('inventoryItem', 'transactions', 'reorderSuggestion', 'valuation'));
    }

    /**
     * Show stock adjustment form
     */
    public function adjustForm(InventoryItem $inventoryItem)
    {
        $this->authorize('adjust', $inventoryItem);

        return view('inventory.adjust', compact('inventoryItem'));
    }

    /**
     * Record stock adjustment
     */
    public function recordAdjustment(Request $request, InventoryItem $inventoryItem)
    {
        $this->authorize('adjust', $inventoryItem);

        $validated = $request->validate([
            'adjustment_type' => 'required|in:increase,decrease,correction',
            'quantity_delta' => 'required|numeric',
            'reason' => 'required|string|min:10',
            'approved_by' => 'nullable|exists:users,id',
        ]);

        try {
            $this->inventoryService->adjustStock(
                $inventoryItem,
                $validated['reason'],
                (int) $validated['quantity_delta'],
                $validated['adjustment_type'],
                $validated['approved_by'] ?? null,
                auth()->user()
            );

            return redirect()->route('inventory.show', $inventoryItem)
                ->with('success', 'Stock adjustment recorded');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to adjust stock: ' . $e->getMessage());
        }
    }

    /**
     * Show stock issue form (requisition fulfillment)
     */
    public function issueForm(InventoryItem $inventoryItem)
    {
        $this->authorize('issue', $inventoryItem);

        return view('inventory.issue', compact('inventoryItem'));
    }

    /**
     * Record stock issue
     */
    public function recordIssue(Request $request, InventoryItem $inventoryItem)
    {
        $this->authorize('issue', $inventoryItem);

        $validated = $request->validate([
            'quantity' => 'required|numeric|min:1',
            'requisition_id' => 'nullable|exists:requisitions,id',
            'approved_by' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->inventoryService->issueStock(
                $inventoryItem,
                (int) $validated['quantity'],
                $validated['requisition_id'] ?? null,
                $validated['approved_by'],
                auth()->user()
            );

            return redirect()->route('inventory.show', $inventoryItem)
                ->with('success', 'Stock issued successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to issue stock: ' . $e->getMessage());
        }
    }

    /**
     * Transfer stock between stores
     */
    public function transfer(Request $request, InventoryItem $inventoryItem)
    {
        $this->authorize('transfer', $inventoryItem);

        $validated = $request->validate([
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id|different:from_store_id',
            'quantity' => 'required|numeric|min:1',
            'approved_by' => 'required|exists:users,id',
        ]);

        try {
            $this->inventoryService->transferStock(
                $inventoryItem,
                (int) $validated['from_store_id'],
                (int) $validated['to_store_id'],
                (int) $validated['quantity'],
                $validated['approved_by'],
                auth()->user()
            );

            return back()->with('success', 'Stock transferred successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to transfer stock: ' . $e->getMessage());
        }
    }

    /**
     * Display items requiring reorder
     */
    public function reorderReport(Request $request)
    {
        $this->authorize('viewAny', InventoryItem::class);

        $storeId = $request->get('store_id') ?? 1;

        $items = $this->inventoryService->getReorderItems($storeId);

        return view('inventory.reorder-report', compact('items', 'storeId'));
    }

    /**
     * Display inventory valuation report
     */
    public function valuationReport(Request $request)
    {
        $this->authorize('viewAny', InventoryItem::class);

        $storeId = $request->get('store_id');
        $valuationDate = $request->get('date') ?? now()->format('Y-m-d');

        $items = InventoryItem::whereHas('stockLevels', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->with(['stockLevels' => function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            }])
            ->get()
            ->map(function ($item) {
                return array_merge(
                    $item->toArray(),
                    ['valuation' => $this->inventoryService->calculateItemValuation($item)]
                );
            });

        $totalValuation = $items->sum(fn($item) => $item['valuation']['total_value'] ?? 0);

        return view('inventory.valuation-report', compact('items', 'storeId', 'valuationDate', 'totalValuation'));
    }

    /**
     * Display inventory movements report
     */
    public function movementsReport(Request $request)
    {
        $this->authorize('viewAny', InventoryItem::class);

        $filters = [
            'store_id' => $request->get('store_id'),
            'transaction_type' => $request->get('transaction_type'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $movements = $this->inventoryService->getMovements($filters);

        return view('inventory.movements-report', compact('movements', 'filters'));
    }

    /**
     * Display item transaction history
     */
    public function history(InventoryItem $inventoryItem)
    {
        $this->authorize('view', $inventoryItem);

        $transactions = $inventoryItem->transactions()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('inventory.history', compact('inventoryItem', 'transactions'));
    }

    /**
     * Search inventory items via AJAX
     */
    public function searchItems(Request $request)
    {
        $query = $request->get('q');
        $storeId = $request->get('store_id');

        $items = InventoryItem::query()
            ->whereHas('stockLevels', function($q) use ($storeId) {
                if ($storeId) {
                    $q->where('store_id', $storeId);
                }
            })
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('item_code', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->with(['stockLevels' => function($q) use ($storeId) {
                if ($storeId) {
                    $q->where('store_id', $storeId);
                }
                $q->limit(1);
            }])
            ->limit(20)
            ->get()
            ->map(function($item) {
                $stockLevel = $item->stockLevels->first();
                return [
                    'id' => $item->id,
                    'item_code' => $item->item_code,
                    'name' => $item->name,
                    'quantity_on_hand' => $stockLevel?->quantity_on_hand ?? 0,
                    'unit_of_measure' => $item->unit_of_measure,
                ];
            });

        return response()->json($items);
    }

    /**
     * Get current stock level via AJAX
     */
    public function getStockLevel(InventoryItem $inventoryItem)
    {
        $stockLevel = $inventoryItem->stockLevels()->first();
        
        return response()->json([
            'id' => $inventoryItem->id,
            'name' => $inventoryItem->name,
            'quantity_on_hand' => $stockLevel?->quantity_on_hand ?? 0,
            'reorder_point' => $inventoryItem->reorder_point,
            'standard_cost' => $inventoryItem->standard_cost,
            'can_issue' => ($stockLevel?->quantity_on_hand ?? 0) > 0,
        ]);
    }

    /**
     * Check if item can be issued
     */
    public function checkAvailability(Request $request)
    {
        $itemId = $request->get('item_id');
        $quantity = $request->get('quantity', 1);
        $storeId = $request->get('store_id');

        $item = InventoryItem::findOrFail($itemId);
        $stockLevel = $item->stockLevels()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->first();
        
        $onHand = $stockLevel?->quantity_on_hand ?? 0;
        $available = $onHand >= $quantity;

        return response()->json([
            'available' => $available,
            'requested' => $quantity,
            'on_hand' => $onHand,
            'message' => $available ? 'Stock available' : 'Insufficient stock',
        ]);
    }
}
