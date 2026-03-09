<?php

namespace App\Http\Controllers;

use App\Http\Requests\PpRiskRequest;
use App\Models\AuditLog;
use App\Models\Directorate;
use App\Models\PpRisk;
use App\Models\PpProject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PpRiskController extends Controller
{
    private function enforcePpAccess(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }
        if ($user->isAdmin()) {
            return;
        }
        $pp = Directorate::where('code', 'PP')->firstOrFail();
        if (!$user->isDirectorateHead() || (int) $user->directorate_id !== (int) $pp->id) {
            abort(403, 'You do not have permission to manage PP risks.');
        }
    }

    public function index(Request $request)
    {
        $this->enforcePpAccess($request);

        $risks = PpRisk::with(['project', 'enteredBy'])
            ->orderByDesc('severity')
            ->paginate(30)
            ->withQueryString();

        $projects = PpProject::orderBy('project_code')->get(['id', 'project_code', 'project_name']);

        return Inertia::render('Pp/Index', [
            'activeTab'  => 'risks',
            'risks'      => $risks,
            'ppProjects' => $projects,
        ]);
    }

    public function store(PpRiskRequest $request)
    {
        $this->enforcePpAccess($request);

        $data = $request->validated();
        $data['severity'] = $data['likelihood'] * $data['impact'];
        $data['entered_by'] = auth()->id();

        $risk = PpRisk::create($data);

        AuditLog::log('create', 'PP risk created', $risk);

        return redirect()->back()->with('success', 'Risk created successfully.');
    }

    public function update(PpRiskRequest $request, PpRisk $ppRisk)
    {
        $this->enforcePpAccess($request);

        $old = $ppRisk->toArray();
        $data = $request->validated();
        $data['severity'] = $data['likelihood'] * $data['impact'];
        $ppRisk->update($data);

        AuditLog::log('update', 'PP risk updated', $ppRisk, $old, $ppRisk->fresh()->toArray());

        return redirect()->back()->with('success', 'Risk updated successfully.');
    }

    public function destroy(Request $request, PpRisk $ppRisk)
    {
        $this->enforcePpAccess($request);

        AuditLog::log('delete', 'PP risk deleted', $ppRisk);
        $ppRisk->delete();

        return redirect()->back()->with('success', 'Risk deleted successfully.');
    }
}
