<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleReception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    /**
     * Availability calendar: for each vehicle, shows which days of the
     * selected month it was (or still is) checked out — computed from its
     * receptions and their closing deliveries, or through today when a
     * reception is still open. Lets staff verify whether a vehicle is
     * free for a given date before requesting it, instead of relying on
     * the paper bitácora or asking around.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', VehicleReception::class);

        $month = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : now()->startOfMonth();

        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $days = collect(range(0, $monthEnd->day - 1))
            ->map(fn ($offset) => $monthStart->copy()->addDays($offset));

        $vehicles = Vehicle::query()
            ->orderBy('make')
            ->with(['receptions' => function ($query) use ($monthStart, $monthEnd) {
                $query->with('delivery')
                    ->where('reception_date', '<=', $monthEnd)
                    ->where(function ($q) use ($monthStart) {
                        $q->whereDoesntHave('delivery')
                            ->orWhereHas('delivery', fn ($d) => $d->where('return_date', '>=', $monthStart));
                    });
            }])
            ->get();

        // Per-vehicle, per-day map of which reception (if any) has the
        // vehicle checked out that day, so the view can render a colored
        // cell and link straight to the relevant record.
        $occupancy = $vehicles->mapWithKeys(function (Vehicle $vehicle) use ($days) {
            $busyByDay = [];

            foreach ($vehicle->receptions as $reception) {
                $start = $reception->reception_date->copy()->startOfDay();
                $end = $reception->delivery
                    ? $reception->delivery->return_date->copy()->startOfDay()
                    : now()->startOfDay();

                foreach ($days as $day) {
                    if ($day->between($start, $end)) {
                        $busyByDay[$day->toDateString()] = $reception;
                    }
                }
            }

            return [$vehicle->id => $busyByDay];
        });

        return view('calendar.index', [
            'vehicles' => $vehicles,
            'days' => $days,
            'occupancy' => $occupancy,
            'month' => $monthStart,
            'prevMonth' => $monthStart->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $monthStart->copy()->addMonth()->format('Y-m'),
            'today' => now()->startOfDay(),
        ]);
    }
}
