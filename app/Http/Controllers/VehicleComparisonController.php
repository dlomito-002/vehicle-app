<?php

namespace App\Http\Controllers;

use App\Models\VehicleReception;
use App\Support\VehicleComparisonBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class VehicleComparisonController extends Controller
{
    public function show(VehicleReception $reception, VehicleComparisonBuilder $builder): View
    {
        $this->authorize('view', $reception);

        [$reception, $delivery, $comparison] = $this->loadComparison($reception, $builder);

        return view('comparisons.show', compact('reception', 'delivery', 'comparison'));
    }

    /**
     * Render the full comparison (all inspection questions, both checklists,
     * and every paired photo) as a downloadable PDF.
     */
    public function pdf(VehicleReception $reception, VehicleComparisonBuilder $builder): Response
    {
        $this->authorize('view', $reception);

        [$reception, $delivery, $comparison] = $this->loadComparison($reception, $builder);

        $pdf = Pdf::loadView('comparisons.pdf', compact('reception', 'delivery', 'comparison'))
            ->setPaper('letter', 'portrait');

        $filename = 'comparacion-'.str($reception->vehicle->displayName())->slug().'-'.$reception->id.'.pdf';

        return $pdf->download($filename);
    }

    private function loadComparison(VehicleReception $reception, VehicleComparisonBuilder $builder): array
    {
        $reception->load(['vehicle', 'creator', 'photos', 'documentation', 'equipmentChecks', 'conditionItems']);
        $delivery = $reception->delivery()->with(['creator', 'photos', 'documentation', 'equipmentChecks', 'conditionItems'])->first();

        abort_unless($delivery, 404, 'Este vehículo aún no ha sido devuelto, por lo que no hay una comparación disponible.');

        $comparison = $builder->build($reception, $delivery);

        return [$reception, $delivery, $comparison];
    }
}
