<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Scenario;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingScenarioController extends Controller
{
    public function briefing(Request $request, Scenario $scenario): View
    {
        abort_if($scenario->isArchived(), 404);

        $version = $scenario->currentVersion;

        $availablePersonas = collect();
        if ($version) {
            $assigned = $version->assignedPersonas()
                ->where('is_enabled', true)
                ->whereHas('persona', fn ($q) => $q->where('status', Persona::STATUS_ACTIVE))
                ->with('persona.currentVersion')
                ->get();

            $availablePersonas = $assigned->pluck('persona')->filter();
        }

        return view('training.briefing', compact('scenario', 'version', 'availablePersonas'));
    }
}
