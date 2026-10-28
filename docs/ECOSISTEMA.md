# Ecosistema Carrousel

Apps independientes (repositorio, base de datos y lógica propios) que se ven y se usan igual dentro del Portal.
Este archivo es **idéntico en todas las apps**: si se cambia aquí, se copia a las demás.

## Apps y versiones

| App | Repositorio | Versión de la app | Ecosistema |
|---|---|---|---|
| Helpdesk | FernandoZL/helpdesk-carrousel | `config/config.php` → `APP_VERSION` | 1.3.0 |
| Caja Chica | FernandoZL/caja-chica-carrousel | archivo `VERSION` | 1.3.0 |
| Rendimientos de Máquinas | FernandoZL/rendimientos-maquinas-carrousel | archivo `VERSION.txt` (primera línea) | 1.3.0 |
| Destrucción de Tickets | FernandoZL/tickets-destruccion-carrousel | archivo `VERSION` | 1.3.0 |
| Control de Vehículos | dlomito-002/vehicle-app | archivo `VERSION` | 1.3.0 |
| Carrousel Campañas (Cumpleaños) | FernandoZL/carrousel-cumple | archivo `VERSION` | 1.3.0 |
| Compras | FernandoZL/compras-carrousel | archivo `VERSION` | 1.3.0 |
| Control de Documentos | FernandoZL/control-documentos-carrousel | archivo `VERSION` | 1.3.0 |
| Portal de Sistemas | FernandoZL/portal-carrousel | último commit | 1.3.0 |

Cada app muestra al pie del menú lateral su versión y la del ecosistema que tiene instalada
(por ejemplo, `Caja Chica v1.0.42 · Ecosistema v1.0.0`). Si dos apps muestran versiones de ecosistema distintas,
falta copiar los archivos compartidos a alguna.

## Archivos compartidos

- `public/assets/css/ecosystem.css`
- `public/assets/js/ecosystem.js`
- `docs/ECOSISTEMA.md` (este archivo)

En Rendimientos de Máquinas la carpeta pública es la raíz, así que viven en `assets/css/` y `assets/js/`;
sus ajustes propios del shell están en `assets/css/ecosystem-rend.css` y los datos del Portal y versiones en `includes/ecosystem.php`.

Reglas:

1. Se cargan **al final**, después de las hojas y scripts propios de cada app.
2. Solo presentación: no cambian rutas, formularios, permisos ni lógica de negocio.
3. Se editan en una app y se copian sin cambios a las demás.
4. Cada cambio sube `@version` en `ecosystem.css` y `ecosystem.js` y agrega una entrada abajo.
   - Parche (1.0.**x**): ajuste visual sin clases nuevas.
   - Menor (1.**x**.0): componente o clase nueva.
   - Mayor (**x**.0.0): cambia la estructura HTML que las apps deben usar.

## Agregar una app nueva

1. Copiar los tres archivos compartidos.
2. Usar el shell de Helpdesk: `.sidebar`, `.side-link` / `.side-icon` / `.side-label`, `.topbar`, botón ☰ con `data-sidebar-toggle`
   (colapsa a riel de íconos de 76 px, guarda `<app>:sidebar-collapsed` en localStorage) y botón `← Portal` en la misma pestaña.
3. Tablas con `.data-table-wrap > .data-table`; acciones de fila en la última columna (`.data-table-actions`).
4. Campana con la estructura de `ecosystem.css` §6.
5. Ayuda con la burbuja `button.help-fab` (§8) que abre el panel de la pantalla; el recorrido de inicio incluye los pasos comunes (menú ☰, campana, ← Portal y ?).
6. Acceso (login y código OTP) con la estructura de `ecosystem.css` §9 (`body.auth-body-v2`, `.auth-layout-v2`, `.auth-card-v2`…),
   botón de tema `data-eco-theme-toggle` y botón `← Portal`. Los textos comunes son los mismos en todas las apps.
7. Al pie del menú, `.sidebar-version` con la versión de la app y la leída de `@version` de `ecosystem.css`.
8. Sección **Administración** al final del menú lateral, visible solo para administradores (ver abajo).
9. Consola `MAIN.bat` en la raíz del repositorio (ver abajo).
10. Agregar la app a la tabla de arriba.

## Administración

Cada app tiene al final del menú lateral la sección **Administración**, solo para administradores, con encabezado
`Administración › <pantalla>`, tarjetas de resumen, filtros en tarjeta y tablas `.data-table`. Pantallas mínimas:

| Pantalla | Qué hace |
|---|---|
| Usuarios | Dar acceso, cambiar rol o estado y quitar acceso. Nadie puede quitarse su propio rol de administrador y siempre queda al menos uno. |
| Auditoría | Quién entró, quién cambió accesos y cuándo; filtros por texto, acción y fechas. |
| Correo y notificaciones (en Caja Chica, «Correos») | Estado del SMTP sin mostrar la contraseña y botón para enviar un correo de prueba. |

Cada app agrega las suyas (Helpdesk: Catálogos y Proveedores externos; Caja Chica: Asignaciones, Roles y permisos, Centros y áreas, Configuración;
Rendimientos: Diagnóstico). En Rendimientos, que no usa base de datos, los usuarios viven en `config/private/access_list.csv`
(columna `rol`: `admin` o `usuario`) y la auditoría en `storage/audit/`.

## Consola MAIN.bat

Misma estructura en todas las apps: encabezado con versión y rama, opciones numeradas (abrir la app, abrir el Portal,
estado Git, actualizar desde GitHub, validar, respaldar, validar SMTP, commit, push, abrir GitHub) y `[0] Salir`.
Los menús anteriores (`CAJA_CHICA_ADMIN.bat`, `RENDIMIENTOS_ADMIN.bat`) se conservan solo como acceso que abre `MAIN.bat`.

**PHP de las consolas y tareas programadas**: el mismo que usa Apache. En el servidor `C:\xampp\php` es PHP 8.0 y Apache usa
`C:\xampp\php82` (PHP 8.2, requerido por Cumpleaños y Control de Vehículos). Todo `.bat`, `.cmd` o `.ps1` busca primero
`C:\xampp\php82\php.exe`, luego `C:\xampp\php\php.exe` y al final `php` del PATH. En una PC sin `php82` usa el de XAMPP.

## Movimiento

Las animaciones son las mismas en todas las apps y viven en `ecosystem.css` §10 y `ecosystem.js` (`window.EcoUI`).
Ninguna app define sus propias ruedas de carga, avisos ni animaciones de entrada.

| Qué | Cómo se ve | Cómo se usa |
|---|---|---|
| Procesando | Fondo oscuro translúcido con fundido de .14 s; tarjeta con rueda de 42 px que gira cada .75 s, "Procesando información" y el mensaje. En el acceso, tarjeta compacta. Para procesos largos, barra de avance `.global-action-progress` y paso actual en `small`. | `EcoUI.busy('Guardando…')` / `EcoUI.idle()` (o el marcado `#global-action-status` con la clase `.show`) |
| Avisos | Arriba a la derecha (en celular, a lo ancho), franja izquierda de color por tipo, entran en .18 s y se cierran solos a los 3,6 s | `EcoUI.toast('Guardado', 'success')`; tipos `info`, `success`, `warning`, `danger` |
| Botón guardando | Deshabilitado, opacidad .65 y texto "Procesando…" | Formularios con `data-single-submit` (o lo hace la app al enviar) |
| Diálogos | Entran en .14 s desde 8 px más abajo | Clase `.eco-dialog` (o `.admin-record-dialog`) en el `<dialog>` |

Si el equipo pide reducir movimiento (`prefers-reduced-motion`), todo aparece sin animación.

## Acceso y código OTP

Misma pantalla (§9) y mismas reglas en todas las apps; solo cambian el nombre, la frase y las tres etiquetas de la izquierda.

- **Pantalla de correo**: "Acceso seguro" · "Ingresa a &lt;App&gt;" · "Te enviaremos un código temporal de seis dígitos a tu correo." ·
  campo "Correo electrónico" con ejemplo `nombre@carrousel.com.gt` · botón "Enviar código" · recuadro "¿Aún no tienes acceso? …" ·
  aviso "El código OTP es temporal. No lo compartas: el personal de Carrousel nunca te lo pedirá." · "← Portal" y botón de tema.
- **Pantalla de código**: "Verificación" · "Escribe tu código" · "Si &lt;correo&gt; tiene acceso, recibirás el código en unos segundos." ·
  campo "Código de 6 dígitos" (`.otp-input-v2`) · "Ingresar" · "Cambiar correo" y "Reenviar código".
- **Reglas**: código de 6 dígitos guardado con `password_hash`; vence en **10 minutos**; **5 intentos** por código (al quinto se invalida);
  máximo **5 códigos cada 15 minutos** por correo; pedir uno nuevo invalida el anterior; la respuesta es la misma exista o no el correo
  (no se revela quién tiene acceso); al entrar se regenera la sesión.
- **Mensajes**: "El código no es correcto." · "El código venció o ya se usó. Solicita uno nuevo." ·
  "Demasiados intentos con este código. Solicita uno nuevo." · "Pediste varios códigos seguidos. Espera unos minutos e inténtalo de nuevo."
- El correo del código usa la plantilla de abajo (etiqueta "Acceso").

## Correos

Todos los correos del sistema (código de acceso, avisos, alertas, prueba de correo) usan **la misma plantilla que Helpdesk**
(`app/Services/MailService.php` → `layout()`). Cada app la tiene en su propio código:

| App | Dónde |
|---|---|
| Helpdesk | `app/Services/MailService.php` → `layout()` |
| Caja Chica | `app/Services/MailService.php` → `layout()` (y `template()` para lotes y accesos) |
| Rendimientos | `includes/otp_mailer.php` → `authMailLayout()` |
| Cumpleaños | `app/Mailer.php` → `layout()` |
| Destrucción de Tickets | `inc/mail_layout.php` → `eco_mail_layout()` |
| Control de Vehículos | `resources/views/emails/layout.blade.php` |
| Compras | `mail_layout.php` → `eco_mail_layout()` |
| Control de Documentos | `includes/mail_layout.php` → `eco_mail_layout()` |

Estructura: fondo `#eef3f9`; tarjeta blanca de 620 px con borde `#d7deea` y radio 18 px; franja de 4 px con el degradado
`#2748a0 → #773db8 → #eb2f7d → #f6a800`; logo de 108 px a la izquierda y etiqueta en mayúsculas a la derecha; título de 24 px
`#14213d`; texto de 15 px `#475467`; botón opcional `#1677ff`; nota de pie "Mensaje automático de &lt;App&gt;." y, fuera de la
tarjeta, "Corporación Carrousel".

- **Logo**: `email-logo.png` (216 px, ~23 KB), incrustado como `cid:carrousel-logo`. No usar el logo de pantalla (~290 KB por correo).
- **Asunto**: `<Asunto> | <App>` (por ejemplo, `Código de acceso | Caja Chica Carrousel`).
- **Código de acceso**: etiqueta "Acceso", título "Tu código de acceso", texto "Usa este código temporal para ingresar. No necesitas contraseña.",
  caja "Tu código" y "Vence en N minutos. Si no solicitaste este acceso, puedes ignorar este mensaje."
- **Prueba de correo**: etiqueta "Prueba", título "El correo funciona".
- Excepción: los correos a **clientes** de Cumpleaños (cupón, campañas, cumpleaños) conservan su diseño festivo; solo usan el mismo logo liviano.

## Historial

### 1.3.0 · 2026-10-28
- Movimiento (§10 y sección Movimiento): "Procesando", avisos, botones guardando y diálogos con la misma animación en todas las apps; `window.EcoUI` con `busy()`, `idle()` y `toast()` (crea el marcado si falta) y cierre del "Procesando" al volver con "Atrás".
- Acceso y código OTP: textos, mensajes y reglas comunes (10 minutos, 5 intentos, 5 códigos cada 15 minutos).
- Se agregan Carrousel Campañas, Compras, Control de Documentos y el Portal a la tabla de apps; Compras y Control de Documentos usan la plantilla de correos.

### 1.2.1 · 2026-10-26
- Versión al pie del menú siempre visible: queda fija abajo aunque el menú sea largo. Con el menú en riel de íconos (colapsado o entre 901 y 1180 px) muestra solo la versión de la app; el texto completo aparece al pasar el cursor. Usa una consulta de contenedor sobre `.sidebar` (`container-type:inline-size`).
- Se agregan Destrucción de Tickets y Control de Vehículos a la tabla de apps.
- Consolas y tareas programadas usan el PHP de Apache (`php82` en el servidor) antes que `C:\xampp\php`.
- Correos: misma plantilla que Helpdesk en las seis apps, asunto `<Asunto> | <App>` y logo liviano de 23 KB (sección Correos).

### 1.2.0 · 2026-10-22
- Acceso: misma pantalla de login y código OTP en todas las apps (§9), autocontenida y con modo oscuro; botón de tema `data-eco-theme-toggle` en `ecosystem.js`.
- Textos comunes del acceso: "Acceso seguro", "Te enviaremos un código temporal de seis dígitos a tu correo.", "Enviar código", "Escribe tu código", "Ingresar" y el mismo aviso de seguridad.
- Rendimientos de Máquinas se integra al ecosistema: ← Portal, ayuda en la burbuja ?, usuario + Salir, versión al pie del menú y pasos comunes en su tutorial.

### 1.1.0 · 2026-10-22
- Ayuda: burbuja "?" flotante abajo a la derecha (`.help-fab`), igual en todas las apps; reemplaza el botón "Ayuda" de la barra superior de Caja Chica.
- Recorridos guiados de inicio con los pasos comunes del ecosistema: Menú, Notificaciones, Volver al Portal y Ayuda (mismos textos en todas las apps).

### 1.0.0 · 2026-10-22
- Tablas: encabezados 10 px, celdas 12 px; no se parten palabras, fechas, montos, etiquetas ni botones.
- Tablas anchas se desplazan dentro de su tarjeta; la columna de acciones queda fija a la derecha (`.is-overflowing`).
- Acciones de fila con un solo estilo: texto azul sin fondo, destructivas en rojo, en una sola línea.
- Menú "Más acciones" flotante sobre la pantalla para que la tabla no lo recorte.
- Barra superior en celular: título completo e íconos visibles.
- Campana de notificaciones: mismo botón, contador y panel (diseño de Helpdesk), claro y oscuro.
- Versión de la app y del ecosistema al pie del menú lateral.
