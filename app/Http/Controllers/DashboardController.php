<?php

namespace App\Http\Controllers;

use App\Enums\ReceptionStatus;
use App\Models\VehicleReception;
use App\Models\VehicleService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $query = VehicleReception::query()->with(['vehicle', 'creator']);

        if (! $request->user()->isAdmin()) {
            $query->where('created_by', $request->user()->id);
        }

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

        $serviceAlerts = VehicleService::query()
            ->with('vehicle')
            ->get()
            ->map(function (VehicleService $service) {
                $service->setAttribute('alert_status', $service->alertStatus());

                return $service;
            })
            ->whereIn('alert_status', ['overdue', 'due_soon'])
            ->sortBy(fn (VehicleService $s) => $s->alert_status === 'overdue' ? 0 : 1)
            ->take(5)
            ->values();

        return view('dashboard.index', compact('openReceptions', 'recentlyClosed', 'serviceAlerts'));
    }
}
