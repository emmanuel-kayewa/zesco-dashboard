<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    /**
     * Update a single user preference.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'key'   => ['required', 'string', 'in:chart_palette'],
            'value' => ['required', 'string', 'max:50'],
        ]);

        $request->user()->setPreference($validated['key'], $validated['value']);

        return response()->noContent();
    }
}
