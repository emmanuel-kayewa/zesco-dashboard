<?php

namespace App\Http\Controllers;

use App\Http\Requests\WayleaveEntryRequest;
use App\Models\AuditLog;
use App\Models\Directorate;
use App\Models\WayleaveEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WayleaveEntryController extends Controller
{
    private function ppDirectorate(): Directorate
    {
        return Directorate::where('code', 'PP')->firstOrFail();
    }

    private function enforcePpAccess(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return;
        }

        $pp = $this->ppDirectorate();
        if (!$user->isDirectorateHead() || (int) $user->directorate_id !== (int) $pp->id) {
            abort(403, 'You do not have permission to edit PP wayleave figures.');
        }
    }

    public function index(Request $request)
    {
        $this->enforcePpAccess($request);

        $pp = $this->ppDirectorate();

        $entries = WayleaveEntry::with(['enteredBy'])
            ->where('directorate_id', $pp->id)
            ->where('source', 'manual')
            ->orderByDesc('report_date')
            ->orderBy('category')
            ->orderBy('aspect')
            ->paginate(25)
            ->withQueryString();

        $directorates = Directorate::active()->ordered()->get();

        return Inertia::render('DataEntry/WayleaveEntry', [
            'entries' => $entries,
            'directorates' => $directorates,
            'pp_directorate' => [
                'id' => $pp->id,
                'name' => $pp->name,
                'code' => $pp->code,
            ],
        ]);
    }

    public function store(WayleaveEntryRequest $request)
    {
        $this->enforcePpAccess($request);

        $pp = $this->ppDirectorate();

        $entry = WayleaveEntry::create([
            ...$request->validated(),
            'directorate_id' => $pp->id,
            'source' => 'manual',
            'entered_by' => auth()->id(),
        ]);

        AuditLog::log('create', 'Wayleave entry created', $entry);

        return redirect()->back()->with('success', 'Wayleave entry saved successfully.');
    }

    public function update(WayleaveEntryRequest $request, WayleaveEntry $wayleaveEntry)
    {
        $this->enforcePpAccess($request);

        $pp = $this->ppDirectorate();
        if ((int) $wayleaveEntry->directorate_id !== (int) $pp->id) {
            abort(403, 'You can only edit entries for PP.');
        }

        $old = $wayleaveEntry->toArray();
        $wayleaveEntry->update($request->validated());

        AuditLog::log('update', 'Wayleave entry updated', $wayleaveEntry, $old, $wayleaveEntry->fresh()->toArray());

        return redirect()->back()->with('success', 'Wayleave entry updated.');
    }

    public function destroy(Request $request, WayleaveEntry $wayleaveEntry)
    {
        $this->enforcePpAccess($request);

        $pp = $this->ppDirectorate();
        if ((int) $wayleaveEntry->directorate_id !== (int) $pp->id) {
            abort(403, 'You can only delete entries for PP.');
        }

        AuditLog::log('delete', 'Wayleave entry deleted', $wayleaveEntry);
        $wayleaveEntry->delete();

        return redirect()->back()->with('success', 'Wayleave entry deleted.');
    }
}
