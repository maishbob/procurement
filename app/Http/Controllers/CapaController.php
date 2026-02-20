<?php

namespace App\Http\Controllers;

use App\Modules\Quality\Models\CapaAction;
use App\Modules\Quality\Services\CapaService;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;

class CapaController extends Controller
{
    public function __construct(private CapaService $capaService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CapaAction::class);

        $query = CapaAction::with(['raisedBy', 'assignedTo', 'department'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $capas = $query->paginate(20)->withQueryString();

        return view('capa.index', compact('capas'));
    }

    public function create()
    {
        $this->authorize('create', CapaAction::class);

        $users = User::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('capa.create', compact('users', 'departments'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', CapaAction::class);

        $validated = $request->validate([
            'type'                    => 'required|in:corrective,preventive',
            'title'                   => 'required|string|max:255',
            'description'             => 'required|string',
            'source'                  => 'required|in:audit_finding,variance_analysis,complaint,non_conformance,process_improvement,management_review,risk_assessment,other',
            'source_reference'        => 'nullable|string|max:255',
            'problem_statement'       => 'required|string',
            'root_cause_analysis'     => 'nullable|string',
            'immediate_action_taken'  => 'nullable|string',
            'proposed_action'         => 'required|string',
            'implementation_plan'     => 'nullable|string',
            'target_completion_date'  => 'nullable|date|after:today',
            'assigned_to'             => 'nullable|exists:users,id',
            'department_id'           => 'nullable|exists:departments,id',
            'priority'                => 'required|in:critical,high,medium,low',
            'estimated_cost'          => 'nullable|numeric|min:0',
        ]);

        try {
            $capa = $this->capaService->create($validated);
            return redirect()->route('capa.show', $capa)
                ->with('success', "CAPA {$capa->capa_number} created successfully.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create CAPA: ' . $e->getMessage());
        }
    }

    public function show(CapaAction $capa)
    {
        $this->authorize('view', $capa);

        $capa->load(['raisedBy', 'assignedTo', 'department', 'approvedBy', 'verifiedBy', 'updates.user']);

        return view('capa.show', compact('capa'));
    }

    public function edit(CapaAction $capa)
    {
        $this->authorize('update', $capa);

        if (!$capa->isDraft()) {
            return redirect()->route('capa.show', $capa)
                ->with('error', 'Only draft CAPAs can be edited.');
        }

        $users = User::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('capa.edit', compact('capa', 'users', 'departments'));
    }

    public function update(Request $request, CapaAction $capa)
    {
        $this->authorize('update', $capa);

        $validated = $request->validate([
            'title'                   => 'required|string|max:255',
            'description'             => 'required|string',
            'problem_statement'       => 'required|string',
            'root_cause_analysis'     => 'nullable|string',
            'immediate_action_taken'  => 'nullable|string',
            'proposed_action'         => 'required|string',
            'implementation_plan'     => 'nullable|string',
            'target_completion_date'  => 'nullable|date',
            'assigned_to'             => 'nullable|exists:users,id',
            'department_id'           => 'nullable|exists:departments,id',
            'priority'                => 'required|in:critical,high,medium,low',
            'estimated_cost'          => 'nullable|numeric|min:0',
        ]);

        try {
            $this->capaService->update($capa, $validated);
            return redirect()->route('capa.show', $capa)
                ->with('success', 'CAPA updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    public function submit(Request $request, CapaAction $capa)
    {
        $this->authorize('update', $capa);

        try {
            $this->capaService->submitForApproval($capa);
            return redirect()->route('capa.show', $capa)
                ->with('success', 'CAPA submitted for approval.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(Request $request, CapaAction $capa)
    {
        $this->authorize('approve', $capa);

        try {
            $this->capaService->approve($capa, $request->input('comments'));
            return redirect()->route('capa.show', $capa)
                ->with('success', 'CAPA approved.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, CapaAction $capa)
    {
        $this->authorize('approve', $capa);

        $request->validate(['reason' => 'required|string']);

        try {
            $this->capaService->reject($capa, $request->reason);
            return redirect()->route('capa.show', $capa)
                ->with('success', 'CAPA rejected.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function startImplementation(Request $request, CapaAction $capa)
    {
        $this->authorize('update', $capa);

        try {
            $this->capaService->startImplementation($capa);
            return redirect()->route('capa.show', $capa)
                ->with('success', 'Implementation started.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function submitForVerification(Request $request, CapaAction $capa)
    {
        $this->authorize('update', $capa);

        try {
            $this->capaService->submitForVerification($capa);
            return redirect()->route('capa.show', $capa)
                ->with('success', 'CAPA submitted for verification.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function verify(Request $request, CapaAction $capa)
    {
        $this->authorize('verify', $capa);

        $request->validate([
            'passed'   => 'required|boolean',
            'comments' => 'nullable|string',
        ]);

        try {
            $this->capaService->verify($capa, (bool) $request->passed, $request->comments);
            $msg = $request->passed ? 'CAPA verification passed.' : 'Verification failed — CAPA returned to in-progress.';
            return redirect()->route('capa.show', $capa)->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function close(Request $request, CapaAction $capa)
    {
        $this->authorize('update', $capa);

        try {
            $this->capaService->close($capa, $request->input('lessons_learned'));
            return redirect()->route('capa.show', $capa)
                ->with('success', 'CAPA closed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function storeUpdate(Request $request, CapaAction $capa)
    {
        $this->authorize('view', $capa);

        $request->validate([
            'update_description'  => 'required|string',
            'progress_percentage' => 'required|numeric|min:0|max:100',
        ]);

        try {
            $this->capaService->addUpdate(
                $capa,
                $request->update_description,
                (float) $request->progress_percentage
            );
            return redirect()->route('capa.show', $capa)
                ->with('success', 'Progress update added.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
