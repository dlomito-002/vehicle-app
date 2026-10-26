<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDelivery;
use App\Models\VehicleMaintenanceCompletion;
use App\Models\VehicleReception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Audit trail for the ecosystem "Auditoría" screen. Best effort: a failure
 * to write the log is reported to the application log and never breaks the
 * operation being audited.
 */
class Audit
{
    /** Models whose create/update/delete is recorded automatically (see AppServiceProvider). */
    public const AUDITED_MODELS = [
        User::class => 'Usuario',
        Vehicle::class => 'Vehículo',
        VehicleReception::class => 'Recepción',
        VehicleDelivery::class => 'Devolución',
        VehicleMaintenanceCompletion::class => 'Mantenimiento',
    ];

    /** Attributes never copied into the audit details. */
    private const HIDDEN = ['password', 'remember_token', 'signature', 'signature_data', 'updated_at', 'created_at'];

    public static function record(string $action, ?Model $subject = null, array $details = [], ?string $subjectLabel = null, ?string $subjectId = null): void
    {
        try {
            $user = auth()->user();

            AuditLog::create([
                'user_id' => $user?->getKey(),
                'actor' => $user?->email ?? (app()->runningInConsole() ? 'sistema' : ''),
                'action' => $action,
                'subject_type' => $subjectLabel ?? ($subject ? (self::AUDITED_MODELS[$subject::class] ?? class_basename($subject)) : ''),
                'subject_id' => (string) ($subjectId ?? $subject?->getKey() ?? ''),
                'details' => $details ?: null,
                'ip' => app()->runningInConsole() ? '' : (string) request()->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar la auditoría.', ['action' => $action, 'error' => $e->getMessage()]);
        }
    }

    /** Old/new values of the attributes that changed, without sensitive fields. */
    public static function changes(Model $model): array
    {
        $new = collect($model->getChanges())->except(self::HIDDEN);

        return [
            'antes' => $new->keys()->mapWithKeys(fn ($key) => [$key => $model->getOriginal($key)])->map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v)->all(),
            'despues' => $new->all(),
        ];
    }

    public static function snapshot(Model $model): array
    {
        return collect($model->attributesToArray())->except(self::HIDDEN)->all();
    }

    public static function label(string $action): string
    {
        return [
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'login_code_requested' => 'Código solicitado',
            'login_code_failed' => 'Código incorrecto',
            'created' => 'Creado',
            'updated' => 'Actualizado',
            'deleted' => 'Eliminado',
            'mail_test' => 'Prueba de correo',
        ][$action] ?? ucfirst(str_replace('_', ' ', $action));
    }
}
