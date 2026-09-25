# AGENTS.md

Contexto para agentes de IA (Claude Code u otros) que trabajen en este repositorio. Léelo antes de tocar código.

## Qué es esto

**Control de Vehiculos Carrousel** — app Laravel 12.69.x en español para control de flota vehicular: recepción y devolución de vehículos con inspecciones fotográficas, firma, reporte de comparación (recepción vs. entrega) en PDF, mantenimiento y gestión de usuarios/roles.

- PHP 8.2, Laravel 12.69.x
- Base de datos: SQLite (desarrollo)
- Frontend: Blade + Tailwind + Alpine.js (sin framework JS pesado, sin build de SPA)
- Scheduler: revisar el estado compartido al final; no asumir worker de colas.

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
| Ayuda/soporte | `HelpController` | Formulario simple → correo a los destinatarios de `App\Support\NotificationRecipients`. No persiste en base de datos |
| Usuarios/roles | `User`, `App\Enums\UserRole` | Solo `Admin`/`Agent`. Gestión de usuarios es admin-only. `receives_notification_emails` ("Recibir correos de Fleet Desk") define quién recibe Ayuda, alertas de mantenimiento y avisos de recepción/devolución de vehículos (`VehicleMovementNotifier`, best effort: si el correo falla se registra en log y la operación no falla) |

## Configuración específica de este proyecto

- `config/vehicle.php` — `manager_email` (respaldo opcional: solo se usa para Ayuda y alertas de mantenimiento cuando ningún usuario tiene activado "Recibir correos de Fleet Desk"; ver `App\Support\NotificationRecipients`) y `photos_disk` (disco de Storage para fotos/firmas, default `'public'`, cámbialo a `'cloudinary'` solo cuando el paquete `cloudinary-labs/cloudinary-laravel` esté instalado y las credenciales configuradas).
- `config/cloudinary.php` — credenciales de Cloudinary armadas a partir de tres variables separadas en `.env` (`CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET`) en vez del formato combinado `CLOUDINARY_URL` que usa el paquete por defecto.
- Variables de `.env` propias del proyecto (no son de Laravel por defecto): `VEHICLE_MANAGER_EMAIL`, `VEHICLE_PHOTOS_DISK`, `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET`.

## Cosas que NO hay que asumir

- No hay cola (`QUEUE_CONNECTION=database` está configurado pero no hay jobs que la usen) ni scheduler activo. Las alertas de mantenimiento se evalúan y envían de forma síncrona cada vez que alguien visita `/maintenance-schedules` — si agregas algo que "debería correr en background", probablemente necesites decirlo explícitamente en vez de asumir que ya hay infraestructura para eso.
- Las fotos existentes en el disco local **no se migran automáticamente** a Cloudinary al cambiar `VEHICLE_PHOTOS_DISK`. Solo las subidas nuevas van al nuevo disco.

## Antes de proponer un cambio

1. Busca si ya existe un patrón similar en el código (un enum, un trait, una policy) antes de crear uno nuevo — este proyecto reutiliza agresivamente.
2. Si el cambio toca una migración, revisa si la tabla ya puede tener filas (ver nota de SQLite arriba).
3. Si el cambio implica una regla de negocio no explícita en el código o en este archivo (un intervalo, un rol, un monto, una condición), pregunta en vez de asumir — así se ha trabajado en este proyecto hasta ahora.
## Estado compartido de colaboración — 2026-09-22

Nombre actual del sistema: **Control de Vehículos Carrousel**.

Estado técnico de `luis/setup-local`:

- Laravel `^12.69.0` (actualmente 12.69.x).
- PHP objetivo `8.2` con plataforma Composer `8.2.12`.
- Blade + Tailwind + Alpine + Vite.
- Login por OTP enviado por correo; no hay login por contraseña.
- Rediseño visual alineado con `FernandoZL/helpdesk-carrousel`.
- Modo oscuro completo, incluido acceso/login.
- Smart Select global propio en `resources/js/app.js` + `resources/css/app.css`.
- Base de referencia de calidad de esta rama: 60 tests / 194 assertions.

### Compatibilidad Windows / Ubuntu

El código de aplicación debe ser multiplataforma.

- Luis: Windows + XAMPP.
- Claudio: Ubuntu.
- No hardcodear rutas `C:\...` ni rutas Linux dentro de controladores, modelos, vistas, configuración compartida o tests.
- `MAIN.bat` es solo una ayuda local para Windows y no debe ser una dependencia funcional.
- Usar APIs de Laravel (`storage_path`, `public_path`, `base_path`) y variables de entorno.

Comandos comunes:

```bash
composer install
npm ci
npm run build
php artisan optimize:clear
php artisan test
composer validate --strict
```

### Ramas y reconciliación

`luis/setup-local` y `Claudio` están divergidas. No hacer merge a ciegas.

Cambios de Claudio que deben revisarse/conservarse al integrar:

- mejoras en `HandlesVehicleFormUploads`;
- configuración Cloudinary/filesystems;
- cierre concurrente seguro de devoluciones;
- `FullFlowTest` y ampliaciones de `VehicleDeliveryTest`;
- `VEHICLE_PHOTOS_DISK=public` en PHPUnit;
- cambios de seeder que sean intencionales.

Cambios de `luis/setup-local` que deben revisarse/conservarse:

- Laravel 12 y lock compatible con PHP 8.2;
- `APP_URL=http://localhost` y SQLite `:memory:` en tests;
- correcciones de migraciones MySQL/MariaDB;
- seguridad/autorización del flujo de entregas;
- sistema visual Helpdesk, dark mode, formularios y Smart Select;
- OTP/correos y documentación de compatibilidad.

Archivos con mayor probabilidad de conflicto:

- `phpunit.xml`
- `resources/js/app.js`
- `resources/views/receptions/create.blade.php`
- `resources/views/deliveries/create.blade.php`
- `app/Http/Controllers/VehicleDeliveryController.php`

### Seguridad

La llave SSH privada `2402` fue retirada de `luis/setup-local`, pero existió en el historial y también en `main`. Debe rotarse/revocarse donde haya sido autorizada. Cualquier limpieza del historial con `git filter-repo` debe coordinarse con todos los colaboradores porque reescribe SHAs.

### Próximos pasos de `luis/setup-local`

1. Limpiar `MAIN.bat` y retirar `tools/APLICAR_RONDA_4A_HELPDESK.ps1`.
2. Actualizar `index.html`.
3. Implementar búsqueda global visible + `Ctrl+K` + `/buscar?q=`.
4. Agregar búsquedas server-side `?q=` a listados paginados.
5. Reconciliar cambios con `Claudio`.
6. Ejecutar build/tests completos antes de cualquier PR hacia `main`.
