<?php

namespace App\Support;

use App\Enums\ReceptionStatus;
use App\Models\VehicleMaintenanceSchedule;
use App\Models\VehicleReception;
use Illuminate\Support\Facades\Cache;

/**
 * Ecosistema Carrousel: shared shell data (versions, Portal link and the
 * notification bell). Presentation only — see docs/ECOSISTEMA.md.
 */
class Ecosystem
{
    public static function appVersion(): string
    {
        static $version = null;

        return $version ??= trim((string) @file_get_contents(base_path('VERSION'))) ?: '0.0.0';
    }

    /** Version of the shared visual layer, read from the @version header of ecosystem.css. */
    public static function ecosystemVersion(): string
    {
        static $version = null;

        if ($version === null) {
            $head = (string) @file_get_contents(public_path('assets/css/ecosystem.css'), false, null, 0, 400);
            $version = preg_match('/@version\s+([\w.-]+)/', $head, $m) ? $m[1] : '';
        }

        return $version;
    }

    public static function portalUrl(): string
    {
        return (string) config('vehicle.portal_url');
    }

    /** Cache-busted URL of a file under public/. */
    public static function asset(string $path): string
    {
        return asset($path).'?v='.(int) @filemtime(public_path($path));
    }

    /**
     * Items for the bell: vehicles still out (open receptions) and
     * maintenance due soon or overdue. Cached briefly so every page view
     * doesn't recompute the fleet's maintenance state.
     *
     * @return list<array{label: string, detail: string, count: int, href: string}>
     */
    public static function attention(): array
    {
        return Cache::remember('eco-attention', 60, function () {
            $items = [];

            $open = VehicleReception::where('status', ReceptionStatus::Open)->count();
            if ($open > 0) {
                $items[] = [
                    'label' => 'Vehículos sin devolver',
                    'detail' => 'Recepciones abiertas pendientes de devolución.',
                    'count' => $open,
                    'href' => route('deliveries.select-vehicle'),
                ];
            }

            $due = VehicleMaintenanceSchedule::with('vehicle')->get()
                ->filter(fn (VehicleMaintenanceSchedule $s) => in_array($s->alertStatus(), ['overdue', 'due_soon'], true))
                ->count();
            if ($due > 0) {
                $items[] = [
                    'label' => 'Mantenimiento por atender',
                    'detail' => 'Servicios vencidos o a 200 km o menos.',
                    'count' => $due,
                    'href' => route('maintenance-schedules.index'),
                ];
            }

            return $items;
        });
    }
}
