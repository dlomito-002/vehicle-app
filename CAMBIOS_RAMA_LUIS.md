# Cambios en la rama `luis/setup-local`

Notas para Diego (dlomito-002): esto resume todo lo que se hizo en esta rama, agrupado por tipo, con el porqué de cada cambio.

## 🔴 IMPORTANTE PARA DIEGO — revisa esto antes de correr `php artisan test`

Encontré y arreglé un bug real de compatibilidad entre tu entorno (Ubuntu) y el mío (Windows) que **te habría roto la suite de tests por completo** si tu `.env` sigue con `DB_CONNECTION=sqlite` (el valor por defecto de `.env.example`):

- `phpunit.xml` forzaba `DB_DATABASE=vehiculos_carrousel_test` asumiendo MySQL. En una máquina con `DB_CONNECTION=sqlite`, SQLite exige una ruta *absoluta* — con ese valor tal cual, fallan 58/60 tests con `Database file at path [vehiculos_carrousel_test] does not exist`. Lo comprobé forzando `DB_CONNECTION=sqlite` localmente y reproduje el error exacto.
- **Fix**: `phpunit.xml` ahora fuerza `DB_CONNECTION=sqlite` + `DB_DATABASE=:memory:` para los tests, sin importar qué motor tengas configurado en tu `.env` real (mysql, sqlite, lo que sea). Los tests corren siempre aislados en SQLite en memoria — más rápido (2.3s vs 6-12s en MySQL) y ya no depende de que exista una base `..._test` en ningún motor.
- Verifiqué que las 60 pruebas pasan igual en SQLite en memoria y en MySQL desde cero (`migrate:fresh` limpio), así que las migraciones actuales son compatibles con ambos motores — el único punto de fricción real era este de `phpunit.xml`, ya resuelto.
- Si tu `.env` local ya apunta a MySQL/MariaDB para desarrollo, no cambia nada para ti; si sigues en SQLite (el default del proyecto), esto es lo que te habría bloqueado al hacer pull.

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

## ✅ Logo en correos — resuelto

Los 3 correos (`emails/login-code.blade.php`, `help-request.blade.php`, `maintenance-alert.blade.php`) ya usan `{{ $message->embed(public_path('images/logo.png')) }}` en vez de `asset('images/logo.png')`. El logo ahora viaja incrustado en el correo (CID de MIME) y se ve igual en local, Ubuntu o cualquier bandeja de entrada, sin depender de `APP_URL`. Suite completa verificada de nuevo tras el cambio: 60/60 tests pasan.

## ✅ REDISEÑO VISUAL — completo (2026-09-22)

**Problema reportado por Luis:** "el diseño no se parece en nada" a `helpdesk-carrousel` (confirmado explícitamente contra `https://github.com/FernandoZL/helpdesk-carrousel`, que es el mismo repo clonado en `C:\xampp\htdocs\HelpdeskCarrousel`).

**Diagnóstico (confirmado):** el login ya coincidía con HelpdeskCarrousel; el resto de vistas tenía los *colores* remapeados sobre marcado Tailwind genérico (`bg-white`, `border-slate-*`) sin usar los componentes reales del sistema de HelpdeskCarrousel (`.card`/`.card-header`/`.card-body`, `.page-heading`, `.data-table`, `.badge`, `.alert`, `.stat`). Se confirmó leyendo `HelpdeskCarrousel/public/assets/css/app.css` línea por línea. Además, los formularios largos (recepción/entrega) usaban una paleta "arcoíris" propia del scaffold viejo (`brand-cyan`/`brand-olive`/`brand-orange`/`brand-magenta`/`brand-amber`) que no existe en el diseño real de Carrousel.

**Hecho:**
- `resources/css/app.css`: componentes reales de HelpdeskCarrousel portados — `.page-heading`, `.card-header`/`.card-body`, `.grid-2/3/4`, `.stat`, `.badge` + variantes, `.alert-success/.alert-danger`, `.empty-state`, `.data-table-shell`/`.data-table` (colapsa a tarjetas en móvil), `.btn-sm`, `.list-row`, `.form-label`/`.field-error`/`.field-help`, `.step-indicator` (wizard 1/2), `.choice-chip` (radios tipo combustible/condición/sí-no, con resaltado en vivo vía `:has()`, sin depender de Alpine para el estilo), `.field-item`/`.item-row` (checklists de equipo/condición), `.upload-tile`, `.kv-list` (listas clave-valor en vistas de detalle), `.photo-grid`.
- `<x-status-badge>` ahora emite `.badge badge-*` — se propaga a las 9+ vistas que lo usan.
- Reconstruidas TODAS las vistas de las 3 áreas que Luis confirmó (dashboard, recepciones/entregas, vehículos/usuarios/mantenimiento), incluyendo formularios largos y vistas de detalle: `dashboard/index`, `receptions/index`, `receptions/create`, `receptions/show`, `deliveries/index`, `deliveries/select-vehicle`, `deliveries/select-reception`, `deliveries/damage-report`, `deliveries/create`, `deliveries/show`, `vehicles/index`, `vehicles/create`, `users/index`, `users/create`, `users/edit`, `maintenance-schedules/index`, alertas de `layouts/app.blade.php`.
- Los 9 componentes de formulario compartidos retemados sin tocar su lógica Alpine.js: `fuel-level-selector`, `fuel-type-selector`, `documentation-checklist`, `equipment-checklist`, `condition-field`, `condition-checklist`, `anomaly-field`, `photo-uploader`, `signature-pad`.

**Verificado:**
- `artisan view:cache` compila sin errores en todas las vistas.
- Suite completa (ahora en SQLite en memoria, ver sección de arriba): 60/60, incluyendo tests reales que hacen GET con `assertOk()` sobre `receptions.show`, `deliveries.select-reception` y `deliveries.create` con datos reales — o sea que esas plantillas ya se probaron renderizando de verdad, no solo por inspección.
- Smoke test manual autenticado (login OTP real vía `tinker` + Apache/XAMPP) sobre las 11 pantallas principales: todas 200, sin excepciones PHP en el HTML devuelto.
- `npm run build` limpio.

## ✅ REDISEÑO VISUAL — ronda 2: auditoría de botones + checklists compactos (2026-09-22, misma tarde)

Luis pidió revisar **todos** los botones de la app y quitar redundancias, y después que los checklists largos (equipo 26 ítems / condición 12 ítems) se sintieran más profesionales.

- **`auth/login`, `auth/verify-code`, `help/create`, `calendar/index`, `comparisons/show`**: eran las últimas 5 vistas con el estilo viejo (`bg-brand-*`, alertas sin `.alert`, botones sin `.btn`). Ya quedaron en el mismo sistema. `comparisons/show` en particular se reconstruyó completo (ya no usa `.responsive-table`).
- **Auditoría con grep sobre toda `resources/views`**: cero clases `bg-brand-*`/`text-brand-*`/`border-brand-*` restantes, cero `<button>` sin `.btn`.
- **Redundancias eliminadas de `app.css`**: el bloque `.responsive-table` (ya no lo usa nadie), el parche completo de modo oscuro para clases Tailwind sueltas (`bg-white`, `border-slate-*`, `bg-brand-*/10` — cero usos verificados), y `.field-item-row/-label/-choices` (huérfanas tras el punto siguiente). CSS final bajó de 35.97kB a 28.90kB solo en esta limpieza.
- **Bug real encontrado**: `.grid-2/3/4` no tenían reglas responsive — se habrían quedado fijas en varias columnas en celular. Agregado.
- **Bug real encontrado (el "botón pegado a la línea" que reportó Luis)**: los `<fieldset>` usados para agrupar/deshabilitar campos por paso (vía Alpine `:disabled`) nunca tuvieron reset de su borde/relleno nativo del navegador (`border: 2px groove` por defecto) — se veía como una línea gris pegada al botón "Siguiente". Reseteado globalmente (`fieldset{margin:0;padding:0;border:0;min-width:0}`).
- **Input de archivo nativo reemplazado en toda la app**: el texto nativo "No se ha seleccionado ningún archivo" no se puede repintar con CSS y se veía fuera de lugar. Ahora hay un `.file-btn` propio (input real oculto con `.sr-only`, sigue siendo 100% funcional — confirmado con los tests que suben archivos de verdad) con estado visual "adjuntado" (✓ + verde) en equipo/condición, y lo mismo en `photo-uploader`/`anomaly-field`.
- **Checklists de equipo y condición: de tabla de una columna a grilla compacta** (`.checklist-grid`, `repeat(auto-fill,minmax(250px,1fr))`): la tabla de 26+12 filas de una sola columna obligaba a un scroll larguísimo para algo que es, en esencia, marcar Sí/No. Ahora son tarjetas compactas (2-4 columnas según el ancho) con el nombre arriba y Sí/No + botón de foto en la misma fila, usando `.choice-chip` (mismo componente que combustible/condición) en vez de radios nativos sueltos — corrige también el desalineado de altura entre el radio y el botón de foto que Luis señaló en la captura. `documentation-checklist` (solo 2-3 ítems) se actualizó al mismo estilo de chip para consistencia, sin convertirla a grilla (no la necesita).
- Verificado: `artisan view:cache` limpio, `npm run build` limpio, 60/60 tests (incluye los que suben archivos reales a los campos que ahora están ocultos tras `.file-btn`).

## ✅ REDISEÑO VISUAL — ronda 3: "Fleet Desk", ancho de pantalla, bug de modo oscuro (2026-09-22, noche)

- **"Fleet Desk" en el remitente del correo**: no era el asunto ni el cuerpo (ya arreglados en la ronda 1), sino `APP_NAME` en `.env` — Laravel lo usa como `MAIL_FROM_NAME`. Cambiado a `"Control de Vehiculos"` en `.env` (máquina de Luis) y en `.env.example` (para que el setup de Diego arranque bien desde cero). `php artisan config:clear` corrido después.
- **Bug real de modo oscuro encontrado y corregido**: la regla base de `input`/`select`/`textarea` tenía `background-color: rgb(255 255 255)` fijo (no `var(--card)`) y sin `color` explícito — en modo oscuro, **todos** los campos de texto del formulario quedaban blancos con texto potencialmente ilegible. Corregido a `var(--card)`/`var(--ink)`.
- **"Aprovechar la pantalla"**: `receptions/create` y `deliveries/create` tenían el `<form>` limitado a `max-width:56rem` (896px) mientras el resto de la app usa hasta 1400px (`.content`). Se quitó el límite y se reestructuró el paso 1 en pares de tarjetas lado a lado (`.grid.grid-2`: Vehículo/Persona + Detalles, luego Combustible + Documentación) en vez de apiladas, dejando el checklist de equipo a ancho completo.
- **Pendiente sin resolver**: Luis reporta que el modo oscuro "sigue sin ser en toda la app" después del fix de inputs. Hice una auditoría completa por grep (cero clases Tailwind `bg/text/border-slate/gray/zinc/*`, cero hex fijos fuera de logo/firma/emails/PDF que deben quedar blancos a propósito, `:root`/`[data-theme="dark"]` sin duplicados) y no encontré más candidatos por código estático. **Se le pidió una captura de pantalla con el modo oscuro activado para localizar el elemento exacto que sigue claro — todavía no la mandó.** Este es el hilo suelto más importante para retomar.
- Verificado: `npm run build` limpio, 60/60 tests, smoke test real de `receptions/create` (200, sin excepciones, estructura de 2 columnas presente en el HTML).

## Configuración local (no está en git, cada quien la suya)

Por si es útil de referencia y no como algo que deba replicarse igual: en esta máquina Windows, Apache (XAMPP) sirve esta carpeta con PHP 8.4 vía un handler específico en `httpd-xampp.conf` (el resto de apps del XAMPP se quedaron en PHP 8.2, sin tocar), porque `composer.lock` ya exigía `>=8.4.1`. La base de datos local es MySQL (`vehiculos_carrousel`), y el `.env` local tiene SMTP real configurado para que el login mande el código de verdad. Nada de esto viaja por git — cada quien configura su propio entorno.

## REDISENO VISUAL - ronda 4A: shell + dark refinement alineados al Helpdesk (2026-09-22)

Se comparo directamente contra el estandar vigente de `FernandoZL/helpdesk-carrousel`, incluyendo `shell-v2.css`, `visual-system.css`, `dark-refinement.css` y `data-tables.css`.

- Modo oscuro: tokens y superficies finales alineados al Helpdesk.
- Shell oscuro: sidebar, topbar, navegacion activa, botones outline y CTA principal.
- Ancho util: `.content` deja de limitarse a 1400px.
- Topbar: jerarquia seccion/pagina, acceso al Portal y comportamiento responsive.
- Sidebar: iconografia comun mediante `.side-icon` y `.side-label`.
- Botones/titulos: densidad y escala alineadas al sistema vigente.
- Alcance: solo presentacion/layout. No se modifican rutas, permisos, controladores, modelos, migraciones, datos ni flujos.

No se incorporan busqueda, notificaciones ni modulos propios del Helpdesk.

## FORM UX — ronda 4B: formularios empresariales (2026-09-22)

Se refinó el formulario de recepción tomando como referencia patrones de formularios empresariales de ServiceNow Horizon, Atlassian Design System y GOV.UK/Baymard, sin cambiar la lógica de negocio.

- Resumen de errores visible al inicio cuando Laravel devuelve validaciones.
- Barra de acciones consistente al cerrar cada paso.
- Campos requeridos identificados visualmente; adjuntos marcados como opcionales.
- Feedback visual sutil en cada elemento del checklist al responder Sí/No.
- Acciones adaptadas a móvil en una sola columna.
- Se conserva el flujo de 2 pasos y la grilla compacta para el checklist operativo.

## ✅ Estado compartido para continuar con Claudio — 2026-09-22

### Rama `luis/setup-local`

Últimos hitos:

- `1200703` — Completar modo oscuro del acceso.
- `0445121` — Agregar selects buscables globales.
- `6524b17` — Limpiar archivos auxiliares y retirar credenciales del repositorio.
- `707bdab` — Mejorar UX empresarial del formulario de recepción.
- `2e9c27a` — Mejorar cierre del formulario y asignar admin a Luis.
- `98335b3` — Alinear interfaz con Helpdesk Carrousel.
- `10c67ad` — Actualizar Laravel 12 y compatibilidad PHP 8.2.

Estado validado de esta rama:

- Laravel 12.69.x.
- PHP 8.2 / Composer platform 8.2.12.
- `npm run build`: OK.
- `php artisan test`: 60 tests / 194 assertions.
- Smart Select global integrado.
- Modo oscuro completo en app y login/OTP.
- Flujo de trabajo acordado: consola + Git; no usar Codex para continuar este proyecto.

### Relación con la rama `Claudio`

Las ramas están divergidas: ambas tienen trabajo propio. Mientras cada desarrollador trabaje en su rama no se interfieren; el rietgo está en la futura integración.

No hacer merge automático todavía.

Cambios propios de `Claudio` que deben conservarse/revisarse:

- `HandlesVehicleFormUploads` y manejo de almacenamiento;
- configuración `filesystems` / Cloudinary;
- cierre concurrente seguro de recepciones al registrar devolución;
- `FullFlowTest`;
- ampliaciones de `VehicleDeliveryTest`;
- `VEHICLE_PHOTOS_DISK=public` en pruebas;
- cambios intencionales en seeder.

Cambios propios de `luis/setup-local` que deben conservarse/revisarse:

- Laravel 12 / PHP 8.2;
- SQLite `:memory:` para tests y `APP_URL=http://localhost`;
- compatibilidad de migraciones con MySQL/MariaDB;
- autorización de recepciones/devoluciones;
- rediseño Helpdesk;
- dark mode;
- UX de formularios;
- Smart Select;
- correos/OTP;
- documentación Windows/Ubuntu.

Archivos con conflicto probable:

- `phpunit.xml`
- `resources/js/app.js`
- `resources/views/receptions/create.blade.php`
- `resources/views/deliveries/create.blade.php`
- `app/Http/Controllers/VehicleDeliveryController.php`

Resultado deseado de `phpunit.xml`: conservar **ambas** configuraciones `APP_URL=http://localhost` y `VEHICLE_PHOTOS_DISK=public`.

### Compatibilidad Windows / Ubuntu

- Luis: Windows + XAMPP, ruta local `C:\xampp\htdocs\ControlVehiculosCarrousel`.
- Claudio: Ubuntu.
- No introducir rutas absolutas de ningún SO en código compartido.
- `MAIN.bat` es ayuda opcional de Windows, no requisito del proyecto.
- Comandos compartidos: Composer, Artisan y npm.

### Seguridad pendiente

La llave privada SSH `2402` fue eliminada de la rama actual, pero permanece en el historial previo y estuvo en `main`. Debe rotarse/revocarse. Una futura limpieza del historial requiere coordinación porque reescribe commits.

### Próximos pasos

1. Limpiar `MAIN.bat` y eliminar el script temporal `tools/APLICAR_RONDA_4A_HELPDESK.ps1`.
2. Actualizar `index.html`.
3. Implementar buscador global de topbar + `Ctrl+K` + `/buscar?q=`.
4. Agregar búsqueda server-side `?q=` a Recepciones, Devoluciones, Vehículos, Usuarios y Mantenimiento.
5. Integrar `Claudio` ↔ `luis/setup-local` de forma controlada.
6. Ejecutar build/tests completos.
7. Solo después evaluar PR hacia `main`.
