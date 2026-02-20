<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    /**
     * Store a newly created fiscal year
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:fiscal_years,name',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        // If setting as active, deactivate all other fiscal years
        if ($request->has('is_active')) {
            FiscalYear::where('is_active', true)->update(['is_active' => false]);
            $validated['is_active'] = true;
        } else {
            $validated['is_active'] = false;
        }

        FiscalYear::create($validated);

        return redirect()
            ->route('budgets.setup')
            ->with('success', 'Fiscal year created successfully');
    }

    /**
     * Set a fiscal year as active
     */
    public function setActive(FiscalYear $fiscalYear)
    {
        // Deactivate all other fiscal years
        FiscalYear::where('is_active', true)->update(['is_active' => false]);

        // Activate the selected one
        $fiscalYear->update(['is_active' => true]);

        return redirect()
            ->route('budgets.setup')
            ->with('success', 'Fiscal year ' . $fiscalYear->name . ' is now active');
    }
}
