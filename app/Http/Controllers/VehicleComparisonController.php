<?php

namespace App\Http\Controllers;

use App\Models\VehicleReception;
use App\Support\VehicleComparisonBuilder;
use Illuminate\View\View;

class VehicleComparisonController extends Controller
{
    public function show(VehicleReception $reception, VehicleComparisonBuilder $builder): View
    {
        $this->authorize('view', $reception);

        $reception->load(['vehicle', 'creator', 'photos', 'documentation']);
        $delivery = $reception->delivery()->with(['creator', 'photos', 'documentation'])->first();

        abort_unless($delivery, 404, 'Este vehículo aún no ha sido devuelto, por lo que no hay una comparación disponible.');

        $comparison = $builder->build($reception, $delivery);

        return view('comparisons.show', compact('reception', 'delivery', 'comparison'));
    }
}
