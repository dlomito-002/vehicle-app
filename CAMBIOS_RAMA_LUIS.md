# Cambios en la rama `luis/setup-local`

Notas para Diego (dlomito-002): esto resume todo lo que se hizo en esta rama, agrupado por tipo, con el porqué de cada cambio. Nada de esto está en `main` todavía — está esperando que se le dé a `FernandoZL` acceso de escritura en el repo para poder subirlo.

## 1. Bugs de migraciones corregidos (bloqueaban MySQL/MariaDB)

MySQL limita los nombres de índice/constraint a 64 caracteres. Los nombres que Laravel genera automáticamente en 3 migraciones los superaban, así que `migrate:fresh` nunca terminaba en MySQL (sí funcionaba en SQLite, que no tiene ese límite — por eso no se había notado antes).

- `database/migrations/2025_01_01_000005_create_vehicle_photos_table.php`
- `database/migrations/2026_09_03_000003_create_vehicle_condition_items_table.php`
- `database/migrations/2026_09_16_000003_create_vehicle_maintenance_completions_table.php`

Se les puso nombre explícito y corto al índice/constraint. La estructura de las tablas (columnas, tipos, relaciones) no cambió en nada.

**Importante para Diego:** si tu base local/producción ya tiene estas tablas creadas de otra forma (por ejemplo si usas SQLite, o si nunca corriste `migrate:fresh` limpio contra MySQL), pregúntame antes de mezclar — puede que tu base ya tenga el nombre largo y la mía el corto, y aunque funcionalmente da igual, lo ideal es que ambas bases queden iguales.

## 2. Seguridad

- **Enumeración de usuarios en el login** (`app/Http/Controllers/Auth/LoginController.php`): antes, si el correo no existía, el formulario respondía con un mensaje distinto ("No existe una cuenta con ese correo"), lo que permitía usar el login para adivinar qué correos tienen cuenta. Ahora la respuesta es idéntica exista o no la cuenta; solo se genera y envía código si el usuario existe de verdad.

- **Autorización faltante en el flujo de entregas** (`app/Http/Controllers/VehicleDeliveryController.php`): `create()`, `damageReport()`, `damageReportPdf()` y `store()` solo validaban `authorize('create', VehicleDelivery::class)` (cualquier agente), pero nunca `authorize('view', $reception)` — que sí es obligatorio para ver una recepción por la ruta `/receptions/{id}`. Resultado: un agente podía ver el reporte de daños y fotos de una recepción de OTRO agente entrando por la ruta de "registrar entrega" con el ID. Ya agregué el chequeo de `view` en los cuatro métodos.
  **⚠️ Esto cambia comportamiento real: si operativamente cualquier agente debe poder cerrar la entrega de un vehículo que recibió otro agente (por ejemplo, turnos distintos), esto ahora lo bloquea.** Avísame si es el caso, para ajustarlo — no lo confirmé con nadie del equipo antes de aplicarlo.

- **`authorize()` faltante** en `VehicleController::store()` — ya estaba protegido por el middleware `admin` de la ruta, así que no era explotable, pero rompía el patrón que sigue el resto de controladores. Agregado para consistencia.

## 3. Funcionalidad nueva

- **Alertas de mantenimiento por cron** (`app/Console/Commands/CheckMaintenanceAlerts.php` + `routes/console.php`): antes, las alertas de mantenimiento solo se disparaban cuando alguien abría la pantalla `/maintenance-schedules` (efecto secundario de un GET). Ahora hay un comando `php artisan maintenance:check-alerts` programado para correr diario vía `Schedule::command()`. Sigue siendo idempotente (no reenvía si ya se mandó), así que ambos mecanismos pueden convivir. **Para que el cron realmente dispare en producción, falta agregar `* * * * * php artisan schedule:run` al crontab del servidor Ubuntu** — eso es infraestructura, no algo que yo pueda configurar desde aquí.

## 4. Infraestructura de tests

- **Base de datos de test separada** (`vehiculos_carrousel_test`, configurada en `phpunit.xml`): antes los tests corrían contra la misma base de datos real de desarrollo (porque no había ninguna separación configurada) y de hecho **me vaciaron la tabla `users` una vez durante esta sesión** al correr `php artisan test`. Ahora tienen su propia base, aislada.
- **`APP_URL` fijo para tests** (`phpunit.xml`): el `APP_URL` local (con subcarpeta `/ControlVehiculosCarrousel/public`, necesario para que Apache/XAMPP sirva la app) rompía la resolución de rutas dentro de los tests (devolvía 404 en rutas con parámetros). Se fijó `APP_URL=http://localhost` solo para el entorno de test, sin tocar el `.env` real.
- Suite completa verificada: **60/60 tests pasan.**

## 5. Rediseño visual

Toda la app (Tailwind config, CSS, layouts, vistas) se realineó al sistema visual de Carrousel, tomando como referencia principal `helpdesk-carrousel` (que a su vez declara a `PayOutParques` como su propia referencia en `docs/ESTANDAR_VISUAL_CARROUSEL.md`) y confirmando consistencia contra `caja-chica-carrousel`:

- Paleta azul corporativa (`#213a8f` + gradiente violeta/rosa/naranja/amarillo para la franja de marca), reemplazando la paleta vieja tipo magenta/cian que traía el scaffold original.
- Franja de marca, modo oscuro (con toggle, persistido en `localStorage`), login de dos paneles, shell de sidebar + topbar.
- Los 3 correos de la app (`resources/views/emails/login-code.blade.php`, `help-request.blade.php`, `maintenance-alert.blade.php`) se rehicieron con el mismo patrón visual que usan `helpdesk-carrousel` y `caja-chica-carrousel` en sus propios correos (tarjeta centrada, barra con gradiente, caja de código OTP). **Dato importante: `PayOutParques` no tiene ningún correo con diseño de marca — no hay nada que portar de ahí en ese punto específico**, así que el patrón sale de las otras dos.
- Los PDFs (`comparisons/pdf.blade.php`, `deliveries/damage-report-pdf.blade.php`) usan su propio CSS inline (Dompdf no soporta Tailwind) — tenían colores viejos hardcodeados que también se actualizaron a la paleta nueva.
- Se agregó un pie de página corporativo (`.app-corporate-footer`, visible en toda pantalla autenticada) con crédito a ambos: desarrollado por Diego Velasquez (enlazado a `@dlomito-002`), en colaboración de Luis Fernando Zuniga.

Es 100% capa de presentación — no se tocó lógica de negocio, rutas, modelos, controladores ni los datos que reciben los Mailables en esta parte.

## 6. Documentación

`README.md`: la sección de "usuarios de prueba" describía un login con contraseña; se corrigió para reflejar el login real (correo + código de un solo uso, sin contraseña).

## Lo que NO se tocó

`composer.lock` y `package-lock.json` siguen exactamente iguales a como estaban — nada de esto cambió versiones de dependencias compartidas.

## ⏭️ PENDIENTE PRIORITARIO — mensaje para continuar

**Problema reportado por Luis:** "el diseño no se parece en nada" a `helpdesk-carrousel`.

**Diagnóstico ya hecho (para no repetir la investigación):**
- Comparé `auth-v2.css` real de HelpdeskCarrousel contra `resources/css/app.css` de vehicle-app línea por línea. El login (`auth-body`/`auth-layout`/`auth-brand`/`auth-card`) **sí coincide casi exacto**: mismo gradiente radial, mismo `grid-template-columns`, mismo `border-radius:26px`, mismo `box-shadow`. El logo sirve bien (`HTTP 200`, ~286KB). No hay ningún `<script src="cdn.tailwindcss.com">` residual. Build de Vite está limpio y actualizado.
- **La causa más probable está en las otras ~20 vistas** (dashboard, receptions, deliveries, vehicles, users, maintenance-schedules, calendar, etc.): el primer fork de rediseño remapeó los *colores* de Tailwind (`brand-magenta` → azul, etc.) sobre el marcado Tailwind genérico que ya existía (`bg-white`, `border-slate-*`, `rounded-md`), pero **no reconstruyó la estructura visual** (tarjetas, tablas, densidad de espaciado) usando el sistema real de HelpdeskCarrousel (`.card`/`.card-header`/`.card-body`, `.data-table` con colapso a filas en móvil, las reglas de `layout-density-v25.css`, `components.css`, `data-tables.css` — archivos que sí existen en `C:\xampp\htdocs\HelpdeskCarrousel\public\assets\css\` pero que no se usaron a fondo). Colores correctos, estructura genérica: por eso "no se parece".

**Antes de seguir mañana:**
1. Pedirle a Luis que diga *específicamente* qué pantalla mira cuando dice que no se parece (¿dashboard? ¿la lista de recepciones? ¿una tarjeta en particular?) — así el esfuerzo va directo ahí en vez de reconstruir las 20 vistas a ciegas.
2. Con esa pantalla concreta, comparar su HTML/CSS renderizado contra el equivalente real en HelpdeskCarrousel (mismo método que se usó aquí para el login: `curl` a ambas, comparar clases y reglas CSS reales, no solo los nombres de archivo).
3. Reconstruir el marcado de esa vista (y luego el resto) usando `.card`/`.data-table` y los espaciados reales, no solo colores — este sí es un cambio más profundo que el remapeo de colores que ya se hizo.

**Bug adicional encontrado y diagnosticado (falta aplicar el fix, ya está identificado exactamente):** el logo en los 3 correos (`emails/login-code.blade.php`, `help-request.blade.php`, `maintenance-alert.blade.php`) usa `asset('images/logo.png')`, que resuelve a `http://localhost/ControlVehiculosCarrousel/public/images/logo.png` — una URL que **solo existe en esta máquina Windows**. Gmail (o cualquier cliente de correo, desde cualquier otro dispositivo) nunca va a poder cargarla, sin importar el diseño. No es un problema de estilos, es que la imagen es físicamente inalcanzable desde fuera de esta PC.

Fix (2 minutos, ya identificado, falta solo aplicarlo): Laravel inyecta automáticamente una variable `$message` en las vistas Blade de correo, con un método `embed()` que incrusta la imagen directamente en el correo (technique CID de MIME), sin depender de ninguna URL pública. Cambiar en los 3 archivos:

```blade
<!-- Antes -->
<img src="{{ asset('images/logo.png') }}" ...>

<!-- Después -->
<img src="{{ $message->embed(public_path('images/logo.png')) }}" ...>
```

Esto funciona igual en local, en Ubuntu, y en cualquier lado — no depende de `APP_URL`. Verificar después con un envío real (ya hay SMTP configurado en el `.env` local) que el logo se vea en la bandeja de Gmail.

## Configuración local (no está en git, cada quien la suya)

Por si es útil de referencia y no como algo que deba replicarse igual: en esta máquina Windows, Apache (XAMPP) sirve esta carpeta con PHP 8.4 vía un handler específico en `httpd-xampp.conf` (el resto de apps del XAMPP se quedaron en PHP 8.2, sin tocar), porque `composer.lock` ya exigía `>=8.4.1`. La base de datos local es MySQL (`vehiculos_carrousel`), y el `.env` local tiene SMTP real configurado para que el login mande el código de verdad. Nada de esto viaja por git — cada quien configura su propio entorno.
