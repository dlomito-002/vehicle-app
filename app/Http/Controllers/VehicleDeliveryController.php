<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Enums\ReceptionStatus;
use App\Http\Controllers\Concerns\HandlesVehicleFormUploads;
use App\Http\Requests\StoreVehicleDeliveryRequest;
use App\Models\Vehicle;
use App\Models\VehicleDelivery;
use App\Models\VehicleReception;
use App\Models\VehicleMaintenanceSchedule;
use App\Support\VehicleMovementNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
    public function selectReception(Vehicle $vehicle): View|RedirectResponse
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

    /**
     * Interstitial step shown before the delivery form: a damage report
     * summarizing everything flagged as an issue at reception, so staff can
     * review it before closing out the checkout.
     */
    public function damageReport(VehicleReception $reception): View
    {
        $this->authorize('create', VehicleDelivery::class);
        $this->authorize('view', $reception);

        if ($reception->status !== ReceptionStatus::Open) {
            abort(409, 'Esta recepción ya fue cerrada por una devolución.');
        }

        $reception->load(['vehicle', 'creator', 'photos', 'equipmentChecks', 'conditionItems']);

        return view('deliveries.damage-report', compact('reception'));
    }

    public function damageReportPdf(VehicleReception $reception): Response
    {
        $this->authorize('create', VehicleDelivery::class);
        $this->authorize('view', $reception);

        $reception->load(['vehicle', 'creator', 'photos', 'equipmentChecks', 'conditionItems']);

        $pdf = Pdf::loadView('deliveries.damage-report-pdf', compact('reception'))
            ->setPaper('letter', 'portrait');

        $filename = 'informe-danos-'.str($reception->vehicle->displayName())->slug().'-'.$reception->id.'.pdf';

        return $pdf->download($filename);
    }

    /** Step 3: delivery form scoped to the specific reception chosen in step 2. */
    public function create(VehicleReception $reception): View
    {
        $this->authorize('create', VehicleDelivery::class);
        $this->authorize('view', $reception);

        if ($reception->status !== ReceptionStatus::Open) {
            abort(409, 'Esta recepción ya fue cerrada por una devolución.');
        }

        $reception->load('vehicle');

        return view('deliveries.create', compact('reception'));
    }

    public function store(StoreVehicleDeliveryRequest $request, VehicleReception $reception): RedirectResponse
    {
        $this->authorize('create', VehicleDelivery::class);
        $this->authorize('view', $reception);

        if ($reception->status !== ReceptionStatus::Open) {
            throw ValidationException::withMessages([
                'reception' => 'Esta recepción ya fue cerrada por otra devolución.',
            ]);
        }

        $data = $request->validated();

        $delivery = DB::transaction(function () use ($request, $data, $reception) {
            // Claim the reception with a conditional UPDATE so two concurrent
            // requests can't both pass the Open check above; only one wins.
            $claimed = VehicleReception::whereKey($reception->id)
                ->where('status', ReceptionStatus::Open)
                ->update(['status' => ReceptionStatus::Closed]);

            if (! $claimed) {
                throw ValidationException::withMessages([
                    'reception' => 'Esta recepción ya fue cerrada por otra devolución.',
                ]);
            }

            $delivery = VehicleDelivery::create([
                'vehicle_reception_id' => $reception->id,
                'vehicle_id' => $reception->vehicle_id,
                'created_by' => $request->user()->id,
                'returned_by_name' => $data['returned_by_name'],
                'keys_received_by_name' => $data['keys_received_by_name'],
                'location' => $data['location'],
                'return_date' => $data['return_date'],
                'return_time' => $data['return_time'],
                'final_mileage' => $data['final_mileage'],
                'fuel_level' => $data['fuel_level'],
                'fuel_type' => $data['fuel_type'],
                'washed' => $data['washed'],
                'general_condition' => $data['general_condition'],
                'windows_mirrors_lights' => $data['windows_mirrors_lights'],
                'tires_condition' => $data['tires_condition'],
                'dashboard_indicators' => $data['dashboard_indicators'],
                'has_anomaly' => $data['has_anomaly'],
                'anomaly_description' => $data['anomaly_description'] ?? null,
            ]);

            $this->storeDocumentation($delivery, $data['documentation'], DocumentType::forDelivery());
            $this->storeEquipmentChecks($delivery, $data['equipment_checks'], $request);
            $this->storeConditionItems($delivery, $data['condition_items'], $request);
            $this->storePhotos($delivery, $request);
            $this->storeSignature($delivery, $request);

            // The reception was closed at the top of this transaction, so the
            // pair can never end up inconsistent.
            return $delivery;
        });

        VehicleMovementNotifier::notify($delivery);
        VehicleMaintenanceSchedule::checkAllFor($delivery->vehicle);

        return redirect()
            ->route('comparisons.show', $reception)
            ->with('status', 'Devolución registrada.');
    }

    public function show(VehicleDelivery $delivery): View
    {
        $this->authorize('view', $delivery);

        $delivery->load(['vehicle', 'reception', 'creator', 'photos', 'documentation', 'equipmentChecks', 'conditionItems']);

        return view('deliveries.show', compact('delivery'));
    }
}
