# Control de Vehiculos Carrousel

Aplicacion web para administrar la recepcion, uso y devolucion de vehiculos. Permite registrar el estado del vehiculo antes y despues de un viaje, adjuntar evidencias y generar un reporte comparativo en PDF.

El proyecto esta construido con Laravel 12.69.x, PHP 8.2+, Blade, Tailwind CSS, Vite y Dompdf.

## Funcionalidades

- Inicio de sesion y cierre de sesion.
- Panel principal y calendario de disponibilidad.
- Administracion del catalogo de vehiculos.
- Registro de recepciones de vehiculos con:
	- Datos del viaje, conductor, fecha, hora y kilometraje.
	- Nivel y tipo de combustible.
	- Estado general del vehiculo.
	- Checklist de equipamiento.
	- Checklist de componentes y condiciones.
	- Documentacion valida.
	- Fotografias por posicion y fotografias de anomalias.
- Registro de devoluciones asociado a una recepcion abierta.
- Comparacion entre la recepcion y la devolucion.
- Descarga del comparativo completo en formato PDF.
- Control de acceso por roles y propiedad de los registros.

## Roles y permisos

| Rol | Permisos |
| --- | --- |
| Administrador | Gestionar vehiculos, consultar todos los registros y utilizar todos los flujos. |
| Agente | Registrar recepciones y devoluciones, consultar sus propios registros y usar el calendario. |

Un agente no puede administrar el catalogo de vehiculos ni consultar las recepciones o devoluciones creadas por otro agente.

## Requisitos

- PHP 8.2 o superior.
- Composer 2.
- Node.js y npm.
- SQLite (configuracion predeterminada) o MySQL/MariaDB.
- Extensiones PHP habituales de Laravel, incluyendo `pdo`, `mbstring`, `openssl`, `fileinfo` y `tokenizer`.

## Instalacion local

Desde la carpeta raiz del proyecto:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
```

Configura la conexion en `.env`. Para usar SQLite, deja estos valores:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/ruta/absoluta/al/proyecto/database/database.sqlite
```

Tambien puede usarse una ruta relativa compatible con el entorno local:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Para MySQL o MariaDB, configura por ejemplo:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vehicle_app
DB_USERNAME=root
DB_PASSWORD=
```

Ejecuta las migraciones y los datos de ejemplo:

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
```

`storage:link` es necesario para que las fotografias almacenadas en el disco publico puedan visualizarse desde la aplicacion.

## Ejecucion en desarrollo

Para iniciar el servidor de Laravel y Vite por separado:

```bash
php artisan serve
npm run dev
```

La aplicacion estara disponible normalmente en `http://localhost:8000`.

Tambien existe un comando compuesto que inicia el servidor web, el listener de colas, los logs y Vite:

```bash
composer run dev
```

Si se utiliza XAMPP, el proyecto puede ubicarse dentro de `htdocs`, pero se recomienda ejecutar `php artisan serve` para el desarrollo local y verificar que la version de PHP usada por CLI sea la misma que requiere el proyecto.

## Usuarios de prueba

El login **no usa contrasena**: se accede con correo + un codigo de un solo uso enviado por email (ver `App\Http\Controllers\Auth\LoginController`). La columna `password` de la tabla `users` existe solo porque es NOT NULL en el esquema; el seeder le pone un valor aleatorio inutilizable, no es una credencial real.

El seeder crea los siguientes usuarios (cualquiera puede iniciar sesion con su correo, recibiendo el codigo por email; en local, si `MAIL_MAILER=log`, el codigo queda escrito en `storage/logs/laravel.log`):

| Rol | Nombre | Correo |
| --- | --- | --- |
| Administrador | Admin User | `admin@example.com` |
| Agente | Agent User | `agent@example.com` |
| Agente | Pierre | `pierre.mazariegos@carrousel.com.gt` |
| Agente | Luis | `luis@carrousel.com.gt` |
| Agente | Diego Velasquez | `diego2402alejandrov@gmail.com` |
| Agente | Rocio | `rocio@carrousel.com.gt` |
| Administrador | Ad | `ad@gmail.com` |

Estos usuarios son exclusivamente para desarrollo. Deben eliminarse antes de desplegar la aplicacion en un entorno real.

## Flujo de uso

1. Iniciar sesion con un usuario autorizado.
2. Un administrador registra los vehiculos desde **Vehiculos**.
3. El agente crea una recepcion y completa la inspeccion, documentacion, checklist y fotografias.
4. Cuando el vehiculo regresa, el usuario selecciona el vehiculo y la recepcion abierta que desea cerrar.
5. Se registra la devolucion con la inspeccion final y sus evidencias.
6. El sistema cierra la recepcion de forma atomica y muestra el comparativo.
7. Desde el comparativo se puede descargar el reporte PDF.

Una recepcion cerrada no puede cerrarse nuevamente. La comparacion tampoco esta disponible hasta que exista una devolucion asociada.

## Rutas principales

| Ruta | Descripcion |
| --- | --- |
| `/login` | Inicio de sesion. |
| `/` | Panel principal. |
| `/calendar` | Calendario de disponibilidad. |
| `/vehicles` | Catalogo de vehiculos, solo administrador. |
| `/receptions` | Listado y registro de recepciones. |
| `/deliveries` | Listado y registro de devoluciones. |
| `/comparisons/{reception}` | Comparativo entre recepcion y devolucion. |
| `/comparisons/{reception}/pdf` | Descarga del comparativo en PDF. |

## Comandos utiles

```bash
# Ejecutar todas las pruebas
php artisan test

# Ejecutar una suite concreta
php artisan test --testsuite=Feature

# Formatear el codigo PHP
vendor/bin/pint

# Limpiar caches de configuracion, rutas y vistas
php artisan optimize:clear

# Ver las rutas registradas
php artisan route:list

# Crear datos de prueba de nuevo (elimina y recrea las tablas)
php artisan migrate:fresh --seed
```

## Pruebas

Las pruebas de `tests/Feature` cubren autenticacion y autorizacion, calendario, recepciones, devoluciones y reportes comparativos. Las pruebas usan `RefreshDatabase`, por lo que no deben ejecutarse contra una base de datos con informacion que se quiera conservar.

La configuracion de PHPUnit usa colas y sesiones en memoria para el entorno de pruebas. Para ejecutar una prueba puntual:

```bash
php artisan test tests/Feature/VehicleReceptionTest.php
```

## Estructura del proyecto

```text
app/
	Enums/                 Enumeraciones del dominio.
	Http/Controllers/      Controladores web y autorizacion de formularios.
	Http/Requests/         Validacion de entradas.
	Models/                Modelos Eloquent y relaciones.
	Policies/              Reglas de acceso por rol y propietario.
	Support/               Servicios auxiliares, incluido el comparativo PDF.
database/
	factories/             Factories para pruebas y seeders.
	migrations/            Esquema de usuarios, vehiculos e inspecciones.
	seeders/               Datos iniciales de desarrollo.
resources/views/         Vistas Blade de la aplicacion.
resources/css/           Estilos Tailwind CSS.
resources/js/            Entrada JavaScript de Vite.
routes/web.php           Rutas web autenticadas y publicas.
tests/                   Pruebas unitarias y funcionales.
```

## Almacenamiento de fotografias

Las fotografias se guardan en el disco `public`, dentro de `storage/app/public`, y se registran en la base de datos junto con su posicion, nombre original, tipo MIME y tamano. El enlace simbolico creado por `php artisan storage:link` expone estos archivos bajo `/storage`.

El PDF utiliza las imagenes almacenadas como datos embebidos para que el reporte pueda generarse sin depender de URLs externas.

## Variables de entorno importantes

Las variables principales se encuentran en `.env.example`:

- `APP_URL`: URL base de la aplicacion.
- `APP_KEY`: clave de cifrado generada con `php artisan key:generate`.
- `DB_*`: conexion a SQLite o MySQL/MariaDB.
- `FILESYSTEM_DISK`: disco de archivos; por defecto `local` y las evidencias usan el disco `public`.
- `SESSION_DRIVER`: por defecto `database`.
- `QUEUE_CONNECTION`: por defecto `database`.
- `MAIL_MAILER`: por defecto `log`, por lo que no se envia correo real en desarrollo.

## Produccion

Antes de desplegar:

1. Configura `APP_ENV=production`, `APP_DEBUG=false` y una `APP_URL` real.
2. Usa una base de datos dedicada y ejecuta `php artisan migrate --force`.
3. No ejecutes `db:seed` con las credenciales de ejemplo.
4. Configura un disco de archivos apropiado y ejecuta `php artisan storage:link` si corresponde.
5. Ejecuta `npm run build` y sirve la carpeta `public` desde el servidor web.
6. Configura un worker para las colas si el entorno las utiliza.
7. Restringe los permisos de `storage` y `bootstrap/cache` al usuario del servidor.

## Licencia

El proyecto declara la licencia MIT en `composer.json`.
