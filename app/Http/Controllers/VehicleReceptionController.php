<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Enums\ReceptionStatus;
use App\Http\Controllers\Concerns\HandlesVehicleFormUploads;
use App\Http\Requests\StoreVehicleReceptionRequest;
use App\Models\Vehicle;
use App\Models\VehicleReception;
use App\Support\VehicleMovementNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VehicleReceptionController extends Controller
{
    use HandlesVehicleFormUploads;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', VehicleReception::class);

        $query = VehicleReception::query()->with(['vehicle', 'creator', 'delivery']);

        if (! $request->user()->isAdmin()) {
            $query->where('created_by', $request->user()->id);
        }

        $receptions = $query->latest('reception_date')->paginate(20);

        return view('receptions.index', compact('receptions'));
    }

    public function create(): View
    {
        $this->authorize('create', VehicleReception::class);

        // A vehicle already checked out (open reception) can't be requested
        // again until it's returned.
        $vehicles = Vehicle::available()->orderBy('make')->get();

        // Suggested initial mileage per vehicle (same source as service alerts).
        $suggestedMileage = $vehicles->mapWithKeys(fn (Vehicle $v) => [$v->id => $v->currentMileage()])->all();

        return view('receptions.create', compact('vehicles', 'suggestedMileage'));
    }

    public function store(StoreVehicleReceptionRequest $request): RedirectResponse
    {
        $this->authorize('create', VehicleReception::class);

        $data = $request->validated();

        $reception = DB::transaction(function () use ($request, $data) {
            $reception = VehicleReception::create([
                'vehicle_id' => $data['vehicle_id'],
                'created_by' => $request->user()->id,
                'received_by_name' => $data['received_by_name'],
                'trip_reason' => $data['trip_reason'],
                'location' => $data['location'],
                'reception_date' => $data['reception_date'],
                'reception_time' => $data['reception_time'],
                'initial_mileage' => $data['initial_mileage'],
                'washed' => $data['washed'],
                'fuel_level' => $data['fuel_level'],
                'fuel_type' => $data['fuel_type'],
                'general_condition' => $data['general_condition'],
                'windows_mirrors_lights' => $data['windows_mirrors_lights'],
                'tires_condition' => $data['tires_condition'],
                'dashboard_indicators' => $data['dashboard_indicators'],
                'has_anomaly' => $data['has_anomaly'],
                'anomaly_description' => $data['anomaly_description'] ?? null,
                'status' => ReceptionStatus::Open,
            ]);

            $this->storeDocumentation($reception, $data['documentation'], DocumentType::forReception());
            $this->storeEquipmentChecks($reception, $data['equipment_checks'], $request);
            $this->storeConditionItems($reception, $data['condition_items'], $request);
            $this->storePhotos($reception, $request);
            $this->storeSignature($reception, $request);

            return $reception;
        });

        VehicleMovementNotifier::notify($reception);

        return redirect()->route('receptions.show', $reception)->with('status', 'Recepción registrada.');
    }

    public function show(VehicleReception $reception): View
    {
        $this->authorize('view', $reception);

        $reception->load(['vehicle', 'creator', 'photos', 'documentation', 'delivery', 'equipmentChecks', 'conditionItems']);

        return view('receptions.show', compact('reception'));
    }
}
