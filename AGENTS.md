# AGENTS.md

Contexto para agentes de IA (Claude Code u otros) que trabajen en este repositorio. Léelo antes de tocar código.

## Qué es esto

**Fleet Desk** — app Laravel 11 en español para control de flota vehicular: recepción y devolución de vehículos con inspecciones fotográficas, firma, reporte de comparación (recepción vs. entrega) en PDF, mantenimiento y gestión de usuarios/roles.

- PHP 8.2, Laravel 11.56
- Base de datos: SQLite (desarrollo)
- Frontend: Blade + Tailwind + Alpine.js (sin framework JS pesado, sin build de SPA)
- Sin cola/scheduler configurado — no asumas que existe un worker corriendo

## Cómo correr el proyecto

```bash
composer install
npm install && npm run build   # o npm run dev en desarrollo
php artisan migrate
php artisan test
```

Revisa **`LEEME.txt`** en la raíz — tiene los pasos manuales pendientes específicos de este proyecto (paquetes por instalar, variables de `.env` por llenar). Si existe, está más actualizado que este archivo respecto a pasos de instalación puntuales.

## Convenciones del proyecto (sigue estas, no las de Laravel "por defecto" si difieren)

- **Idioma**: todo el texto de cara al usuario (vistas, mensajes de validación, nombres de rutas con nombre descriptivo) está en español. El código (nombres de variables, clases, comentarios) está en inglés. Mantén esa mezcla.
- **Form Requests**: toda validación de formularios vive en `app/Http/Requests/`, nunca inline en el controlador.
- **Enums respaldados por string** (`App\Enums\*`) para todo lo que sea un conjunto fijo de opciones (posición de foto, tipo de documento, categoría de mantenimiento, rol, etc.), con un método `label()` que da el texto en español. Cuando necesites agregar una opción a un catálogo existente, agrégala al enum — los formularios, reglas de validación y reportes casi siempre iteran sobre el enum dinámicamente (`DocumentType::forReception()`, `PhotoPosition::standardPositions()`, etc.), así que un solo cambio ahí se propaga solo. Verifícalo greppeando el enum antes de tocar vistas/controladores a mano.
- **Políticas** (`app/Policies/`) para toda autorización — nunca metas checks de rol (`if ($user->role === ...)`) directo en un controlador o vista; usa `$this->authorize(...)` o `@can`. Los roles son solo `Admin` y `Agent` (`App\Enums\UserRole`) — no inventes roles nuevos sin que el dueño del proyecto lo pida explícitamente.
- **Fotos/firmas**: pasan todas por el trait `app/Http/Controllers/Concerns/HandlesVehicleFormUploads.php`. No dupliques lógica de subida de archivos en un controlador — reutiliza `persistPhoto()`/`storeChecklistPhoto()`/`storeSignature()`. El disco de fotos es configurable (`config('vehicle.photos_disk')`, ver más abajo) — nunca hardcodees `'public'` o `'cloudinary'` directamente en código nuevo.
- **Carpetas/nombres de archivo**: las fotos se guardan bajo `flota/{placa-slug}/{recepcion|entrega}-{id}/{posicion}.{ext}` (ver el trait) para que sean legibles a simple vista en el media library de Cloudinary, no hashes aleatorios. Respeta ese patrón si agregas un nuevo tipo de subida.
- **Migraciones para SQLite**: este proyecto corre sobre SQLite. Si agregas una columna `NOT NULL` a una tabla que ya puede tener filas, SQLite falla (`Cannot add a NOT NULL column with default value NULL`) a menos que declares `->nullable()` o le des un `->default(...)`. Ya nos pasó una vez en este proyecto — revisa `database/migrations/2026_09_16_000001_add_location_to_vehicle_deliveries_table.php` como referencia de la corrección.
- **Tests**: `tests/Feature/`, usan `RefreshDatabase` + Laravel's `actingAs()`. Antes de asumir que una funcionalidad no existe, revisa si hay un test que ya la cubre.

## Áreas del dominio

| Área | Modelos principales | Notas |
|---|---|---|
| Recepción de vehículo | `VehicleReception`, `VehiclePhoto`, `VehicleDocumentation`, `VehicleEquipmentCheck`, `VehicleConditionItem` | Checklist de 26 ítems de equipo + 12 de estado general, cada uno con foto opcional |
| Devolución de vehículo | `VehicleDelivery` | Flujo explícito: seleccionar vehículo → seleccionar recepción abierta → comparación de daños → formulario de entrega |
| Comparación / reporte PDF | `App\Support\VehicleComparisonBuilder`, `App\Support\PdfImageEncoder` | Diffea recepción vs. entrega (kilometraje, combustible, documentación, checklist, fotos por posición). El PDF usa `barryvdh/laravel-dompdf` |
| Mantenimiento (registro manual) | `VehicleService`, `App\Enums\ServiceType` | Bitácora libre de servicios (cambio de aceite, frenos, etc.) con `next_service_mileage`/`next_service_date` capturados a mano |
| Mantenimiento (intervalos fijos) | `VehicleMaintenanceSchedule`, `VehicleMaintenanceCompletion`, `App\Enums\MaintenanceCategory` | Sistema separado y en paralelo al anterior: Básico (1,000 km) y Mayor (4,000 km). Calcula desde el último servicio completado de esa categoría, no desde el kilometraje actual. Alerta a 200 km o menos, una sola vez por ventana, se resetea al completar el servicio |
| Autenticación | `LoginController`, `LoginVerificationCode` | Login por código de un solo uso enviado por correo (NO es OAuth/"Sign in with Google" — el código lo genera la app, el correo solo es el transporte). Expira, un solo uso, con rate limiting y límite de intentos fallidos |
| Ayuda/soporte | `HelpController` | Formulario simple → correo a `VEHICLE_MANAGER_EMAIL`. No persiste en base de datos |
| Usuarios/roles | `User`, `App\Enums\UserRole` | Solo `Admin`/`Agent`. Gestión de usuarios es admin-only |

## Configuración específica de este proyecto

- `config/vehicle.php` — `manager_email` (destinatario de Ayuda y alertas de mantenimiento) y `photos_disk` (disco de Storage para fotos/firmas, default `'public'`, cámbialo a `'cloudinary'` solo cuando el paquete `cloudinary-labs/cloudinary-laravel` esté instalado y las credenciales configuradas).
- `config/cloudinary.php` — credenciales de Cloudinary armadas a partir de tres variables separadas en `.env` (`CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET`) en vez del formato combinado `CLOUDINARY_URL` que usa el paquete por defecto.
- Variables de `.env` propias del proyecto (no son de Laravel por defecto): `VEHICLE_MANAGER_EMAIL`, `VEHICLE_PHOTOS_DISK`, `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET`.

## Cosas que NO hay que asumir

- No hay cola (`QUEUE_CONNECTION=database` está configurado pero no hay jobs que la usen) ni scheduler activo. Las alertas de mantenimiento se evalúan y envían de forma síncrona cada vez que alguien visita `/maintenance-schedules` — si agregas algo que "debería correr en background", probablemente necesites decirlo explícitamente en vez de asumir que ya hay infraestructura para eso.
- Las fotos existentes en el disco local **no se migran automáticamente** a Cloudinary al cambiar `VEHICLE_PHOTOS_DISK`. Solo las subidas nuevas van al nuevo disco.

## Antes de proponer un cambio

1. Busca si ya existe un patrón similar en el código (un enum, un trait, una policy) antes de crear uno nuevo — este proyecto reutiliza agresivamente.
2. Si el cambio toca una migración, revisa si la tabla ya puede tener filas (ver nota de SQLite arriba).
3. Si el cambio implica una regla de negocio no explícita en el código o en este archivo (un intervalo, un rol, un monto, una condición), pregunta en vez de asumir — así se ha trabajado en este proyecto hasta ahora.