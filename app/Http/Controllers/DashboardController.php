<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $scenarios = Scenario::with('currentVersion')
            ->where('status', Scenario::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        return view('training.dashboard', compact('scenarios'));
    }
}
