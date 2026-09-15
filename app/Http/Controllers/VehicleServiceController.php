<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleServiceRequest;
use App\Models\Vehicle;
use App\Models\VehicleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleServiceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', VehicleService::class);

        $services = VehicleService::query()
            ->with('vehicle')
            ->latest('service_date')
            ->get()
            ->map(function (VehicleService $service) {
                $service->setAttribute('alert_status', $service->alertStatus());

                return $service;
            });

        // Most urgent first: overdue, then due soon, then everything else.
        $order = ['overdue' => 0, 'due_soon' => 1, 'ok' => 2];
        $services = $services->sortBy(fn (VehicleService $s) => $order[$s->alert_status])->values();

        $alerts = $services->whereIn('alert_status', ['overdue', 'due_soon']);

        return view('services.index', compact('services', 'alerts'));
    }

    public function create(): View
    {
        $this->authorize('create', VehicleService::class);

        $vehicles = Vehicle::orderBy('make')->get();

        return view('services.create', compact('vehicles'));
    }

    public function store(StoreVehicleServiceRequest $request): RedirectResponse
    {
        $this->authorize('create', VehicleService::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        VehicleService::create($data);

        return redirect()->route('services.index')->with('status', 'Servicio registrado.');
    }

    public function edit(VehicleService $service): View
    {
        $this->authorize('update', $service);

        $vehicles = Vehicle::orderBy('make')->get();

        return view('services.edit', compact('service', 'vehicles'));
    }

    public function update(StoreVehicleServiceRequest $request, VehicleService $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $service->update($request->validated());

        return redirect()->route('services.index')->with('status', 'Servicio actualizado.');
    }

    public function destroy(VehicleService $service): RedirectResponse
    {
        $this->authorize('delete', $service);

        $service->delete();

        return redirect()->route('services.index')->with('status', 'Servicio eliminado.');
    }
}
