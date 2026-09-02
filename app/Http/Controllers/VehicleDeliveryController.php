<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Enums\ReceptionStatus;
use App\Http\Controllers\Concerns\HandlesVehicleFormUploads;
use App\Http\Requests\StoreVehicleDeliveryRequest;
use App\Models\Vehicle;
use App\Models\VehicleDelivery;
use App\Models\VehicleReception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VehicleDeliveryController extends Controller
{
    use HandlesVehicleFormUploads;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', VehicleDelivery::class);

        $query = VehicleDelivery::query()->with(['vehicle', 'reception', 'creator']);

        if (! $request->user()->isAdmin()) {
            $query->where('created_by', $request->user()->id);
        }

        $deliveries = $query->latest('return_date')->paginate(20);

        return view('deliveries.index', compact('deliveries'));
    }

    /** Step 1: pick which vehicle is being returned. */
    public function selectVehicle(): View
    {
        $this->authorize('create', VehicleDelivery::class);

        $vehicles = Vehicle::query()
            ->whereHas('receptions', fn ($q) => $q->where('status', ReceptionStatus::Open))
            ->orderBy('make')
            ->get();

        return view('deliveries.select-vehicle', compact('vehicles'));
    }

    /**
     * Step 2: since a vehicle may have several open receptions at once, the
     * user must explicitly pick which specific one this delivery closes.
     * We never auto-select — each option is identified by reception date,
     * time, and the person who received the vehicle.
     */
    public function selectReception(Vehicle $vehicle): View
    {
        $this->authorize('create', VehicleDelivery::class);

        $openReceptions = $vehicle->openReceptions()
            ->with('creator')
            ->orderBy('reception_date')
            ->orderBy('reception_time')
            ->get();

        if ($openReceptions->isEmpty()) {
            return redirect()
                ->route('deliveries.select-vehicle')
                ->withErrors(['vehicle' => 'Este vehículo no tiene recepciones abiertas para cerrar.']);
        }

        return view('deliveries.select-reception', compact('vehicle', 'openReceptions'));
    }

    /** Step 3: delivery form scoped to the specific reception chosen in step 2. */
    public function create(VehicleReception $reception): View
    {
        $this->authorize('create', VehicleDelivery::class);

        if ($reception->status !== ReceptionStatus::Open) {
            abort(409, 'Esta recepción ya fue cerrada por una devolución.');
        }

        $reception->load('vehicle');

        return view('deliveries.create', compact('reception'));
    }

    public function store(StoreVehicleDeliveryRequest $request, VehicleReception $reception): RedirectResponse
    {
        $this->authorize('create', VehicleDelivery::class);

        if ($reception->status !== ReceptionStatus::Open) {
            throw ValidationException::withMessages([
                'reception' => 'Esta recepción ya fue cerrada por otra devolución.',
            ]);
        }

        $data = $request->validated();

        $delivery = DB::transaction(function () use ($request, $data, $reception) {
            $delivery = VehicleDelivery::create([
                'vehicle_reception_id' => $reception->id,
                'vehicle_id' => $reception->vehicle_id,
                'created_by' => $request->user()->id,
                'returned_by_name' => $data['returned_by_name'],
                'keys_received_by_name' => $data['keys_received_by_name'],
                'return_date' => $data['return_date'],
                'return_time' => $data['return_time'],
                'final_mileage' => $data['final_mileage'],
                'fuel_level' => $data['fuel_level'],
                'washed' => $data['washed'],
                'general_condition' => $data['general_condition'],
                'windows_mirrors_lights' => $data['windows_mirrors_lights'],
                'tires_condition' => $data['tires_condition'],
                'dashboard_indicators' => $data['dashboard_indicators'],
                'cleanliness' => $data['cleanliness'],
                'has_anomaly' => $data['has_anomaly'],
                'anomaly_description' => $data['anomaly_description'] ?? null,
            ]);

            $this->storeDocumentation($delivery, $data['documentation'], DocumentType::forDelivery());
            $this->storePhotos($delivery, $request);

            // Closing the reception happens atomically with delivery creation
            // so the pair can never end up inconsistent.
            $reception->update(['status' => ReceptionStatus::Closed]);

            return $delivery;
        });

        return redirect()
            ->route('comparisons.show', $reception)
            ->with('status', 'Devolución registrada.');
    }

    public function show(VehicleDelivery $delivery): View
    {
        $this->authorize('view', $delivery);

        $delivery->load(['vehicle', 'reception', 'creator', 'photos', 'documentation']);

        return view('deliveries.show', compact('delivery'));
    }
}
