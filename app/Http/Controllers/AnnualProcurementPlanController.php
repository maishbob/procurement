<?php

namespace App\Http\Controllers;

use App\Modules\Planning\Services\AnnualProcurementPlanService;
use Illuminate\Http\Request;
use App\Modules\Planning\Models\AnnualProcurementPlan;
use Illuminate\Support\Facades\Gate;

class AnnualProcurementPlanController extends Controller
{
    protected $service;

    public function __construct(AnnualProcurementPlanService $service)
    {
        $this->service = $service;
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('viewAny', AnnualProcurementPlan::class);
        $plans = $this->service->getAll();
        return view('planning.index', compact('plans'));
    }

    public function create()
    {
        $this->authorize('create', AnnualProcurementPlan::class);
        return view('planning.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', AnnualProcurementPlan::class);
        $validated = $request->validate([
            'fiscal_year' => 'required|string|max:20',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.category' => 'required|string|max:255',
            'items.*.description' => 'required|string|max:255',
            'items.*.planned_quarter' => 'required|string|max:255',
            'items.*.estimated_value' => 'required|numeric|min:0',
            'items.*.sourcing_method' => 'required|string|max:255',
        ]);
        $data = $validated;
        $items = $data['items'];
        // Map estimated_value to estimated_total and budgeted_amount for each item
        foreach ($items as &$item) {
            if (isset($item['estimated_value'])) {
                $item['estimated_total'] = $item['estimated_value'];
                $item['budgeted_amount'] = $item['estimated_value'];
            }
        }
        unset($data['items']);
        $plan = $this->service->create($data, $items);
        return redirect()->route('annual-procurement-plans.show', $plan)
            ->with('success', 'Annual Procurement Plan created successfully.');
    }

    public function show(AnnualProcurementPlan $annualProcurementPlan)
    {
        $this->authorize('view', $annualProcurementPlan);
        return view('planning.show', compact('annualProcurementPlan'));
    }

    public function edit(AnnualProcurementPlan $annualProcurementPlan)
    {
        $this->authorize('update', $annualProcurementPlan);
        return view('planning.edit', compact('annualProcurementPlan'));
    }

    public function update(Request $request, AnnualProcurementPlan $annualProcurementPlan)
    {
        $this->authorize('update', $annualProcurementPlan);
        $validated = $request->validate([
            'fiscal_year' => 'required|string|max:20',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.category' => 'required|string|max:255',
            'items.*.description' => 'required|string|max:255',
            'items.*.planned_quarter' => 'required|string|max:255',
            'items.*.estimated_value' => 'required|numeric|min:0',
            'items.*.sourcing_method' => 'required|string|max:255',
        ]);
        $this->service->update($annualProcurementPlan, $validated);
        return redirect()->route('annual-procurement-plans.show', $annualProcurementPlan)
            ->with('success', 'Annual Procurement Plan updated successfully.');
    }

    public function submit(AnnualProcurementPlan $annualProcurementPlan)
    {
        $this->authorize('submit', $annualProcurementPlan);
        $this->service->submit($annualProcurementPlan);
        return redirect()->route('annual-procurement-plans.show', $annualProcurementPlan)
            ->with('success', 'Plan submitted for review.');
    }

    public function approve(AnnualProcurementPlan $annualProcurementPlan)
    {
        $this->authorize('approve', $annualProcurementPlan);
        $this->service->approve($annualProcurementPlan);
        return redirect()->route('annual-procurement-plans.show', $annualProcurementPlan)
            ->with('success', 'Plan approved successfully.');
    }

    public function reject(AnnualProcurementPlan $annualProcurementPlan)
    {
        $this->authorize('reject', $annualProcurementPlan);
        $this->service->reject($annualProcurementPlan);
        return redirect()->route('annual-procurement-plans.show', $annualProcurementPlan)
            ->with('error', 'Plan rejected.');
    }
}
