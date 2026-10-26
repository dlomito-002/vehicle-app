<?php

namespace App\Providers;

use App\Support\Audit;
use App\Support\Ecosystem;
use App\Support\ScreenHelp;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Audit trail (Administración › Auditoría): changes to the main records
        // and session start/end are recorded without touching each controller.
        foreach (array_keys(Audit::AUDITED_MODELS) as $model) {
            $model::created(fn (Model $m) => Audit::record('created', $m, Audit::snapshot($m)));
            $model::updated(function (Model $m) {
                $changes = Audit::changes($m);
                if ($changes['despues']) {
                    Audit::record('updated', $m, $changes);
                }
            });
            $model::deleted(fn (Model $m) => Audit::record('deleted', $m, Audit::snapshot($m)));
        }

        Event::listen(Login::class, fn (Login $e) => Audit::record('login', $e->user instanceof Model ? $e->user : null));
        Event::listen(Logout::class, fn (Logout $e) => Audit::record('logout', $e->user instanceof Model ? $e->user : null));

        // Ecosystem shell data for the authenticated layout.
        View::composer('layouts.app', function ($view) {
            $view->with([
                'ecoAttention' => auth()->check() ? Ecosystem::attention() : [],
                'ecoHelp' => ScreenHelp::for(Route::currentRouteName()),
            ]);
        });
    }
}
