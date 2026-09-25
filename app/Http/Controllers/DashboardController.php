<?php

namespace App\Http\Controllers;

use App\Enums\ReceptionStatus;
use App\Models\VehicleReception;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $query = VehicleReception::query()->with(['vehicle', 'creator']);

        $openReceptions = (clone $query)
            ->where('status', ReceptionStatus::Open)
            ->latest('reception_date')
            ->limit(10)
            ->get();

        $recentlyClosed = (clone $query)
            ->where('status', ReceptionStatus::Closed)
            ->with('delivery')
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact('openReceptions', 'recentlyClosed'));
    }
}
