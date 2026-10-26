# Ecosistema Carrousel

Apps independientes (repositorio, base de datos y lógica propios) que se ven y se usan igual dentro del Portal.
Este archivo es **idéntico en todas las apps**: si se cambia aquí, se copia a las demás.

## Apps y versiones

| App | Repositorio | Versión de la app | Ecosistema |
|---|---|---|---|
| Helpdesk | FernandoZL/helpdesk-carrousel | `config/config.php` → `APP_VERSION` | 1.2.1 |
| Caja Chica | FernandoZL/caja-chica-carrousel | archivo `VERSION` | 1.2.1 |
| Rendimientos de Máquinas | FernandoZL/rendimientos-maquinas-carrousel | archivo `VERSION.txt` (primera línea) | 1.2.1 |
| Destrucción de Tickets | FernandoZL/tickets-destruccion-carrousel | archivo `VERSION` | 1.2.1 |
| Control de Vehículos | dlomito-002/vehicle-app | archivo `VERSION` | 1.2.1 |

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

### 1.2.1 · 2026-10-26
- Versión al pie del menú siempre visible: queda fija abajo aunque el menú sea largo. Con el menú en riel de íconos (colapsado o entre 901 y 1180 px) muestra solo la versión de la app; el texto completo aparece al pasar el cursor. Usa una consulta de contenedor sobre `.sidebar` (`container-type:inline-size`).
- Se agregan Destrucción de Tickets y Control de Vehículos a la tabla de apps.
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
