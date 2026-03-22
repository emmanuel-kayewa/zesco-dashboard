<?php

namespace App\Http\Controllers;

use App\Http\Requests\PpWorkstreamRequest;
use App\Models\AuditLog;
use App\Models\Directorate;
use App\Models\PpProject;
use App\Models\PpWorkstream;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PpWorkstreamController extends Controller
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
            abort(403, 'You do not have permission to manage PP workstreams.');
        }
    }

    public function index(Request $request)
    {
        $this->enforcePpAccess($request);

        $workstreams = PpWorkstream::with(['project:id,project_code,project_name', 'enteredBy:id,name'])
            ->orderBy('pp_project_id')
            ->orderBy('workstream')
            ->paginate(50)
            ->withQueryString();

        $projects = PpProject::orderBy('project_code')->get(['id', 'project_code', 'project_name']);

        return Inertia::render('Pp/Index', [
            'activeTab'   => 'workstreams',
            'workstreams' => $workstreams,
            'ppProjects'  => $projects,
        ]);
    }

    public function store(PpWorkstreamRequest $request)
    {
        $this->enforcePpAccess($request);

        $workstream = PpWorkstream::create([
            ...$request->validated(),
            'entered_by' => auth()->id(),
        ]);

        AuditLog::log('create', 'PP workstream created', $workstream);

        return redirect()->back()->with('success', 'Workstream created successfully.');
    }

    public function update(PpWorkstreamRequest $request, PpWorkstream $workstream)
    {
        $this->enforcePpAccess($request);

        $old = $workstream->toArray();
        $workstream->update($request->validated());

        AuditLog::log('update', 'PP workstream updated', $workstream, $old, $workstream->fresh()->toArray());

        return redirect()->back()->with('success', 'Workstream updated successfully.');
    }

    public function destroy(Request $request, PpWorkstream $workstream)
    {
        $this->enforcePpAccess($request);

        AuditLog::log('delete', 'PP workstream deleted', $workstream);
        $workstream->delete();

        return redirect()->back()->with('success', 'Workstream deleted successfully.');
    }
}
