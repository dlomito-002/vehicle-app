<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Vehicle::class);

        $vehicles = Vehicle::query()
            ->withCount(['receptions', 'deliveries'])
            ->orderBy('make')
            ->paginate(20);

        return view('vehicles.index', compact('vehicles'));
    }

    public function create(): View
    {
        $this->authorize('create', Vehicle::class);

        return view('vehicles.create');
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $this->authorize('create', Vehicle::class);

        Vehicle::create($request->validated());

        return redirect()->route('vehicles.index')->with('status', 'Vehículo agregado.');
    }

    public function edit(Vehicle $vehicle): View
    {
        $this->authorize('update', $vehicle);

        return view('vehicles.edit', compact('vehicle'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);

        $vehicle->update($request->validated());

        return redirect()->route('vehicles.index')->with('status', 'Vehículo actualizado.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);

        $vehicle->delete();

        return redirect()->route('vehicles.index')->with('status', 'Vehículo eliminado.');
    }
}
