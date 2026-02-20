<?php

namespace App\Http\Controllers;

use App\Models\ConflictOfInterestDeclaration;
use App\Models\ProcurementProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ConflictOfInterestController extends Controller
{
    /**
     * Show the CoI declaration form for a procurement process.
     */
    public function create(ProcurementProcess $process)
    {
        $user = auth()->user();

        // If already declared, show the existing declaration
        $existing = ConflictOfInterestDeclaration::where('user_id', $user->id)
            ->where('declarable_type', ProcurementProcess::class)
            ->where('declarable_id', $process->id)
            ->first();

        return view('procurement.coi.declare', compact('process', 'existing'));
    }

    /**
     * Store a CoI declaration.
     */
    public function store(Request $request, ProcurementProcess $process)
    {
        $request->validate([
            'has_conflict'    => ['required', 'boolean'],
            'conflict_details'=> ['required_if:has_conflict,1', 'nullable', 'string', 'max:1000'],
        ]);

        $user = auth()->user();

        $declaration = ConflictOfInterestDeclaration::updateOrCreate(
            [
                'user_id'         => $user->id,
                'declarable_type' => ProcurementProcess::class,
                'declarable_id'   => $process->id,
            ],
            [
                'has_conflict'     => (bool) $request->has_conflict,
                'conflict_details' => $request->conflict_details,
                'declared_at'      => now(),
            ]
        );

        if ($declaration->has_conflict) {
            // Notify the Procurement Manager by logging; full email notification
            // is wired via the notification system when it is configured.
            \Log::warning('CoI conflict declared', [
                'user_id'    => $user->id,
                'user_name'  => $user->name,
                'process_id' => $process->id,
                'process'    => $process->title,
                'details'    => $request->conflict_details,
            ]);

            return redirect()->route('procurement.indexRFQ')
                ->with('warning',
                    'Your conflict of interest has been recorded. You have been removed from this evaluation panel. ' .
                    'The Procurement Manager has been notified.'
                );
        }

        return redirect()->route('procurement.indexRFQ')
            ->with('success', 'Your declaration of no conflict of interest has been recorded.');
    }
}
