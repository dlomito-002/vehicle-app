<?php

namespace Tests\Feature;

use App\Models\VehicleService;
use Tests\TestCase;

/**
 * The standalone Services UI/routes were removed, but the VehicleService
 * model and its historical data are kept for the maintenance system's sake
 * (see AGENTS.md) — this guards the one bit of business logic still worth
 * regression-testing now that no route exercises it.
 */
class VehicleServiceTest extends TestCase
{
    public function test_service_is_overdue_once_current_mileage_passes_the_threshold(): void
    {
        $service = new VehicleService([
            'service_type' => 'oil_change',
            'service_date' => now()->subMonths(2),
            'mileage_at_service' => 10000,
            'next_service_mileage' => 15000,
        ]);

        $this->assertSame('overdue', $service->alertStatus(15200));
        $this->assertSame('due_soon', $service->alertStatus(14700));
        $this->assertSame('ok', $service->alertStatus(12000));
    }
}
