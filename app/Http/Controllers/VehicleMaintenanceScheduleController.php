<?php

namespace App\Http\Controllers;

use App\Enums\MaintenanceCategory;
use App\Http\Requests\StoreVehicleMaintenanceCompletionRequest;
use App\Models\Vehicle;
use App\Models\VehicleMaintenanceSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleMaintenanceScheduleController extends Controller
{
    /**
     * Overview across every vehicle x category (Basic/Major).
     * Visible to everyone, same as the free-text service registry — only
     * recording a completed service is admin-only. Each view also acts as
     * the trigger point for maintenance alert emails: any schedule that has
     * just entered its warning window and hasn't been notified yet gets
     * emailed here (see VehicleMaintenanceSchedule::checkAndNotify()).
     */
    public function index(): View
    {
        $this->authorize('viewAny', VehicleMaintenanceSchedule::class);

        $vehicles = Vehicle::orderBy('make')->get();

        $schedules = $vehicles
            ->crossJoin(MaintenanceCategory::cases())
            ->map(function (array $pair) {
                [$vehicle, $category] = $pair;

                $schedule = VehicleMaintenanceSchedule::firstOrCreateFor($vehicle, $category);
                $schedule->setRelation('vehicle', $vehicle);
                $schedule->checkAndNotify();

                return $schedule;
            });

        $order = ['overdue' => 0, 'due_soon' => 1, 'unknown' => 2, 'unconfigured' => 3, 'ok' => 4];
        $schedules = $schedules->sortBy(fn (VehicleMaintenanceSchedule $s) => $order[$s->alertStatus()] ?? 5)->values();

        $alerts = $schedules->filter(fn (VehicleMaintenanceSchedule $s) => in_array($s->alertStatus(), ['overdue', 'due_soon'], true));

        return view('maintenance-schedules.index', compact('schedules', 'alerts'));
    }

    /** Record a completed service for one vehicle + category — resets that category's alert state. */
    public function complete(
        StoreVehicleMaintenanceCompletionRequest $request,
        Vehicle $vehicle,
        MaintenanceCategory $category
    ): RedirectResponse {
        $schedule = VehicleMaintenanceSchedule::firstOrCreateFor($vehicle, $category);

        $this->authorize('update', $schedule);

        $data = $request->validated();

        $schedule->recordCompletion(
            mileage: $data['mileage'],
            serviceDate: $data['service_date'],
            createdBy: $request->user()->id,
            notes: $data['notes'] ?? null,
        );

        return redirect()->route('maintenance-schedules.index')->with('status', 'Servicio de mantenimiento registrado.');
    }
}
