# Manual técnico · Control de Vehículos Carrousel

Corporación Carrousel · Uso interno · 31 de octubre de 2026


| Control | Valor |
| --- | --- |
| Documento | Manual técnico empresarial |
| Carpeta | ControlVehiculosCarrousel |
| Código base | edb618f94dfa0434b30295e41125bfae3714f396 |
| Rama | main |
| Versión declarada | 1.1.1 |
| Revisión | Estática de código y documentación; sin ejecución de flujos de negocio |
| Aprobación operativa | Pendiente de validación por el responsable de la aplicación |


## 1. Propósito y alcance

Controlar la recepción y devolución de flota, inspecciones, fotografías, firmas, comparación de daños y mantenimiento.

Este manual está dirigido a TI, soporte y responsables operativos. Describe la copia local revisada y permite localizar las reglas y componentes que deben conservarse al instalar, actualizar o recuperar el servicio. La aprobación del proceso de negocio y del entorno productivo corresponde a sus responsables.

La revisión se realizó sobre archivos de aplicación, rutas, esquemas declarados y documentación existente. No se accedió a datos reales, no se enviaron correos y no se ejecutaron migraciones, instaladores ni operaciones de negocio. Los nombres de archivos privados se documentan para su administración, sin revelar valores.


## 2. Perfiles y responsabilidades


| Perfil | Responsabilidad y alcance |
| --- | --- |
| Admin | Vehículos, usuarios, mantenimiento, auditoría y correo, además de la operación autorizada. |
| Agent | Recepción/devolución y consulta permitidas por políticas; sin administración de usuarios. |

TI mantiene código, entorno, respaldos y configuración privada. El responsable operativo valida catálogos, asignaciones y criterios de aceptación. Antes de habilitar un usuario se debe comprobar su alcance efectivo en el servidor; ocultar un botón no prueba que una operación esté autorizada.


## 3. Arquitectura y tecnologías

Laravel 12.69, PHP 8.2, Eloquent, Blade, Tailwind, Alpine y Vite. SQLite de desarrollo, con configuración de conexión por entorno; archivos locales o Cloudinary según disco.


```text
Navegador · interfaz y formularios
  ↓
Servidor Apache / PHP · validación y reglas
  ↓
Persistencia · base de datos y archivos de la aplicación
```


| Dependencia | Restricción | Interpretación |
| --- | --- | --- |
| php | ^8.2 | Restricción declarada en composer.json; no certifica la versión desplegada |
| barryvdh/laravel-dompdf | ^3.1 | Restricción declarada en composer.json; no certifica la versión desplegada |
| cloudinary-labs/cloudinary-laravel | ^3.0 | Restricción declarada en composer.json; no certifica la versión desplegada |
| laravel/framework | ^12.69.0 | Restricción declarada en composer.json; no certifica la versión desplegada |
| laravel/tinker | ^2.9 | Restricción declarada en composer.json; no certifica la versión desplegada |


## 4. Flujo operativo y reglas


```text
Ingresar con código por correo
  ↓
Seleccionar vehículo y registrar recepción
  ↓
Completar fotos, documentación, checklist y firma
  ↓
Seleccionar recepción abierta y registrar devolución
  ↓
Comparar daños, generar PDF y atender mantenimiento
```

1. Ingresar con código por correo. Confirmar el resultado en la pantalla o consulta correspondiente antes de continuar; ante un error, conservar el identificador de operación y evitar repetir una acción sensible sin comprobar su estado.

2. Seleccionar vehículo y registrar recepción. Confirmar el resultado en la pantalla o consulta correspondiente antes de continuar; ante un error, conservar el identificador de operación y evitar repetir una acción sensible sin comprobar su estado.

3. Completar fotos, documentación, checklist y firma. Confirmar el resultado en la pantalla o consulta correspondiente antes de continuar; ante un error, conservar el identificador de operación y evitar repetir una acción sensible sin comprobar su estado.

4. Seleccionar recepción abierta y registrar devolución. Confirmar el resultado en la pantalla o consulta correspondiente antes de continuar; ante un error, conservar el identificador de operación y evitar repetir una acción sensible sin comprobar su estado.

5. Comparar daños, generar PDF y atender mantenimiento. Confirmar el resultado en la pantalla o consulta correspondiente antes de continuar; ante un error, conservar el identificador de operación y evitar repetir una acción sensible sin comprobar su estado.


### Reglas de negocio que deben conservarse

- Los roles son Admin y Agent; autorización en Policies y validación en Form Requests.
- Fotos y firmas pasan por HandlesVehicleFormUploads; el disco se determina por config/vehicle.php.
- Mantenimiento fijo: básico 1,000 km y mayor 4,000 km por defecto; se calcula desde el último servicio de su categoría.
- La alerta de mantenimiento se evalúa al visitar maintenance-schedules; no asumir scheduler ni worker activo por la sola configuración de cola.
- Cambiar a Cloudinary afecta nuevas subidas: las fotos locales existentes no se migran solas.
- Las notificaciones de movimiento son best effort: una falla de correo se registra sin invalidar la recepción/devolución.

## 5. Estructura y puntos de mantenimiento


| Ruta relativa | Función |
| --- | --- |
| routes/web.php | Rutas y middleware de autenticación/administración. |
| app/Http/Controllers/VehicleReceptionController.php | Recepción. |
| app/Http/Controllers/VehicleDeliveryController.php | Devolución. |
| app/Http/Controllers/Concerns/HandlesVehicleFormUploads.php | Persistencia de evidencias. |
| app/Support/VehicleComparisonBuilder.php | Comparación de inspecciones. |
| app/Enums/MaintenanceCategory.php | Categorías e intervalos predeterminados. |
| config/vehicle.php | Disco y destinatario de respaldo. |
| database/migrations/ | Evolución del esquema. |


## 6. Datos y relaciones

Eloquent relaciona vehículos, recepciones, devoluciones, fotos, documentación, checklist, servicios, calendarios y auditoría. La base concreta se selecciona en .env; no copiar la base local como si fuera un esquema vacío.

El siguiente diccionario resume nombres y tipos declarados en las fuentes indicadas. No procede de una consulta a la base activa y no certifica que todas las migraciones estén aplicadas. En Laravel incluye declaraciones históricas: una columna puede haberse retirado en migraciones posteriores; comprobar el esquema efectivo antes de intervenir.


| Entidad declarada | Campos y tipos identificados | Fuente |
| --- | --- | --- |
| users | name [string]; email [string]; email_verified_at [timestamp]; password [string]; token [string]; created_at [timestamp]; id [string]; user_id [foreignId]; ip_address [string]; user_agent [text]; payload [longText]; last_activity [integer]; role [string]; receives_notification_emails [boolean] | database/migrations/0001_01_01_000000_create_users_table.php:14 |
| password_reset_tokens | name [string]; email [string]; email_verified_at [timestamp]; password [string]; token [string]; created_at [timestamp]; id [string]; user_id [foreignId]; ip_address [string]; user_agent [text]; payload [longText]; last_activity [integer] | database/migrations/0001_01_01_000000_create_users_table.php:24 |
| sessions | name [string]; email [string]; email_verified_at [timestamp]; password [string]; token [string]; created_at [timestamp]; id [string]; user_id [foreignId]; ip_address [string]; user_agent [text]; payload [longText]; last_activity [integer] | database/migrations/0001_01_01_000000_create_users_table.php:30 |
| cache | key [string]; value [mediumText]; expiration [integer]; owner [string] | database/migrations/0001_01_01_000001_create_cache_table.php:14 |
| cache_locks | key [string]; value [mediumText]; expiration [integer]; owner [string] | database/migrations/0001_01_01_000001_create_cache_table.php:20 |
| jobs | queue [string]; payload [longText]; attempts [unsignedTinyInteger]; reserved_at [unsignedInteger]; available_at [unsignedInteger]; created_at [unsignedInteger]; id [string]; name [string]; total_jobs [integer]; pending_jobs [integer]; failed_jobs [integer]; failed_job_ids [longText]; options [mediumText]; cancelled_at [integer]; created_at [integer]; finished_at [integer]; uuid [string]; connection [text]; queue [text]; exception [longText]; failed_at [timestamp] | database/migrations/0001_01_01_000002_create_jobs_table.php:14 |
| job_batches | queue [string]; payload [longText]; attempts [unsignedTinyInteger]; reserved_at [unsignedInteger]; available_at [unsignedInteger]; created_at [unsignedInteger]; id [string]; name [string]; total_jobs [integer]; pending_jobs [integer]; failed_jobs [integer]; failed_job_ids [longText]; options [mediumText]; cancelled_at [integer]; created_at [integer]; finished_at [integer]; uuid [string]; connection [text]; queue [text]; exception [longText]; failed_at [timestamp] | database/migrations/0001_01_01_000002_create_jobs_table.php:24 |
| failed_jobs | queue [string]; payload [longText]; attempts [unsignedTinyInteger]; reserved_at [unsignedInteger]; available_at [unsignedInteger]; created_at [unsignedInteger]; id [string]; name [string]; total_jobs [integer]; pending_jobs [integer]; failed_jobs [integer]; failed_job_ids [longText]; options [mediumText]; cancelled_at [integer]; created_at [integer]; finished_at [integer]; uuid [string]; connection [text]; queue [text]; exception [longText]; failed_at [timestamp] | database/migrations/0001_01_01_000002_create_jobs_table.php:37 |
| vehicles | make [string]; model [string]; license_plate [string] | database/migrations/2025_01_01_000002_create_vehicles_table.php:11 |
| vehicle_receptions | vehicle_id [foreignId]; created_by [foreignId]; received_by_name [string]; trip_reason [string]; reception_date [date]; reception_time [string]; initial_mileage [unsignedInteger]; fuel_level [string]; general_condition [string]; windows_mirrors_lights [string]; tires_condition [string]; dashboard_indicators [string]; cleanliness [string]; has_anomaly [boolean]; anomaly_description [text]; status [string]; fuel_type [string]; location [string]; washed [boolean] | database/migrations/2025_01_01_000003_create_vehicle_receptions_table.php:11 |
| vehicle_deliveries | vehicle_reception_id [foreignId]; vehicle_id [foreignId]; created_by [foreignId]; returned_by_name [string]; keys_received_by_name [string]; return_date [date]; return_time [string]; final_mileage [unsignedInteger]; fuel_level [string]; washed [boolean]; general_condition [string]; windows_mirrors_lights [string]; tires_condition [string]; dashboard_indicators [string]; cleanliness [string]; has_anomaly [boolean]; anomaly_description [text]; fuel_type [string]; location [string] | database/migrations/2025_01_01_000004_create_vehicle_deliveries_table.php:11 |
| vehicle_photos | photographable [morphs]; position [string]; disk [string]; path [string]; original_filename [string]; size [unsignedBigInteger]; mime_type [string] | database/migrations/2025_01_01_000005_create_vehicle_photos_table.php:11 |
| vehicle_documentations | documentable [morphs]; document_type [string]; is_valid [boolean] | database/migrations/2025_01_01_000006_create_vehicle_documentations_table.php:11 |
| vehicle_equipment_checks | checkable [morphs]; item [string]; is_present [boolean]; photo_disk [string]; photo_path [string]; photo_original_filename [string]; photo_size [unsignedBigInteger]; photo_mime_type [string] | database/migrations/2026_09_03_000002_create_vehicle_equipment_checks_table.php:11 |
| vehicle_condition_items | conditionable [morphs]; item [string]; status [string]; photo_disk [string]; photo_path [string]; photo_original_filename [string]; photo_size [unsignedBigInteger]; photo_mime_type [string] | database/migrations/2026_09_03_000003_create_vehicle_condition_items_table.php:11 |
| vehicle_services | vehicle_id [foreignId]; created_by [foreignId]; service_type [string]; other_description [string]; service_date [date]; mileage_at_service [unsignedInteger]; next_service_date [date]; next_service_mileage [unsignedInteger]; notes [text] | database/migrations/2026_09_14_000002_create_vehicle_services_table.php:11 |
| vehicle_maintenance_schedules | vehicle_id [foreignId]; category [string]; interval_km [unsignedInteger]; alert_sent_at [timestamp]; alert_mileage [unsignedInteger]; alert_status [string] | database/migrations/2026_09_16_000002_create_vehicle_maintenance_schedules_table.php:11 |
| vehicle_maintenance_completions | vehicle_maintenance_schedule_id [foreignId]; mileage [unsignedInteger]; service_date [date]; created_by [foreignId]; notes [text] | database/migrations/2026_09_16_000003_create_vehicle_maintenance_completions_table.php:11 |
| login_verification_codes | email [string]; code_hash [string]; expires_at [timestamp]; attempts [unsignedTinyInteger]; consumed_at [timestamp] | database/migrations/2026_09_16_000004_create_login_verification_codes_table.php:11 |
| audit_logs | user_id [foreignId]; actor [string]; action [string]; subject_type [string]; subject_id [string]; details [json]; ip [string]; created_at [timestamp] | database/migrations/2026_10_26_000001_create_audit_logs_table.php:12 |

No se presenta un diagrama físico certificado: no se verificaron llaves foráneas en la instancia activa. Las asociaciones de negocio se describen en el flujo; para un ERD físico final, exportar únicamente el esquema autorizado del entorno y contrastarlo con las migraciones.


### Archivos y configuración que requieren conservación


| Recurso | Tratamiento |
| --- | --- |
| .env | Configuración privada: transferir por canal administrativo; excluir de Git y del manual. |
| storage/app/ | Recurso operativo: clasificar contenido, permisos y política de respaldo antes de actualizar. |
| storage/logs/ | Recurso operativo: clasificar contenido, permisos y política de respaldo antes de actualizar. |
| database/database.sqlite | Recurso operativo: clasificar contenido, permisos y política de respaldo antes de actualizar. |


## 7. Acceso e interfaces

Las rutas se expresan relativas al punto de entrada de la aplicación. No constituyen una API pública contratada ni autorizan automatizaciones sobre datos de empresa. Para cada escritura deben revisarse método, validación, permiso, alcance y protección contra solicitudes no autorizadas en el archivo señalado.


| Método / despacho | Ruta / acción | Manejador o verificación | Fuente |
| --- | --- | --- | --- |
| GET | /login | Revisar controlador y middleware en fuente | routes/web.php:18 |
| POST | /login | Revisar controlador y middleware en fuente | routes/web.php:19 |
| GET | /login/verify | Revisar controlador y middleware en fuente | routes/web.php:20 |
| POST | /login/verify | Revisar controlador y middleware en fuente | routes/web.php:21 |
| POST | /logout | Revisar controlador y middleware en fuente | routes/web.php:25 |
| GET | / | Revisar controlador y middleware en fuente | routes/web.php:27 |
| GET | /calendar | Revisar controlador y middleware en fuente | routes/web.php:30 |
| GET | /receptions | Revisar controlador y middleware en fuente | routes/web.php:33 |
| GET | /receptions/create | Revisar controlador y middleware en fuente | routes/web.php:34 |
| POST | /receptions | Revisar controlador y middleware en fuente | routes/web.php:35 |
| GET | /receptions/{reception} | Revisar controlador y middleware en fuente | routes/web.php:36 |
| GET | /deliveries | Revisar controlador y middleware en fuente | routes/web.php:39 |
| GET | /deliveries/select-vehicle | Revisar controlador y middleware en fuente | routes/web.php:40 |
| GET | /deliveries/select-reception/{vehicle} | Revisar controlador y middleware en fuente | routes/web.php:41 |
| GET | /deliveries/damage-report/{reception} | Revisar controlador y middleware en fuente | routes/web.php:42 |
| GET | /deliveries/damage-report/{reception}/pdf | Revisar controlador y middleware en fuente | routes/web.php:43 |
| GET | /deliveries/create/{reception} | Revisar controlador y middleware en fuente | routes/web.php:44 |
| POST | /deliveries/{reception} | Revisar controlador y middleware en fuente | routes/web.php:45 |
| GET | /deliveries/{delivery} | Revisar controlador y middleware en fuente | routes/web.php:46 |
| GET | /comparisons/{reception} | Revisar controlador y middleware en fuente | routes/web.php:49 |
| GET | /comparisons/{reception}/pdf | Revisar controlador y middleware en fuente | routes/web.php:50 |
| GET | /maintenance-schedules | Revisar controlador y middleware en fuente | routes/web.php:53 |
| GET | /help | Revisar controlador y middleware en fuente | routes/web.php:56 |
| POST | /help | Revisar controlador y middleware en fuente | routes/web.php:57 |
| GET | /vehicles | Revisar controlador y middleware en fuente | routes/web.php:61 |
| GET | /vehicles/create | Revisar controlador y middleware en fuente | routes/web.php:62 |
| POST | /vehicles | Revisar controlador y middleware en fuente | routes/web.php:63 |
| GET | /vehicles/{vehicle}/edit | Revisar controlador y middleware en fuente | routes/web.php:64 |
| PUT | /vehicles/{vehicle} | Revisar controlador y middleware en fuente | routes/web.php:65 |
| DELETE | /vehicles/{vehicle} | Revisar controlador y middleware en fuente | routes/web.php:66 |
| POST | /vehicles/{vehicle}/maintenance-schedules/{category}/complete | Revisar controlador y middleware en fuente | routes/web.php:68 |
| RESOURCE | users | Revisar controlador y middleware en fuente | routes/web.php:70 |
| GET | /admin/auditoria | Revisar controlador y middleware en fuente | routes/web.php:73 |
| GET | /admin/correo | Revisar controlador y middleware en fuente | routes/web.php:74 |
| POST | /admin/correo/prueba | Revisar controlador y middleware en fuente | routes/web.php:75 |


## 8. Instalación y actualización


### Instalación nueva o preparación de entorno

1. Ejecutar composer install y npm ci; compilar recursos con npm run build.
1. Crear .env desde .env.example y configurar URL, base, correo y disco de fotos.
1. Solo en instalación nueva, generar APP_KEY con php artisan key:generate; conservar la clave existente al actualizar.
1. Respaldar y revisar php artisan migrate --force para producción; en desarrollo usar php artisan migrate.
1. Para disco public, revisar php artisan storage:link y permisos de storage y bootstrap/cache.
1. Servir exclusivamente public/ y verificar OTP, fotos y reporte PDF; ejecutar optimize:clear después de cambios de configuración.

### Actualización de una instancia existente

1. Identificar servidor, carpeta, rama y commit actuales. Ejecutar git status --short y git log -1; registrar cambios locales antes de proceder.
1. Crear un respaldo verificable de la persistencia y archivos operativos descritos en la sección 9. Revisar el cambio de esquema antes de aplicar código.
1. Preparar el código en PC TEST. Si se actualiza desde Git y la rama está limpia y reconciliada, usar git pull --ff-only; una divergencia requiere revisión, no un descarte automático.
1. Instalar dependencias desde los archivos lock si cambiaron. Conservar configuración privada, datos, evidencias y sesiones de acuerdo con la política de la aplicación.
1. Aplicar únicamente migraciones incrementales revisadas. No ejecutar scripts de reinicio, semillas o instaladores limpios como actualización rutinaria.
1. Validar acceso, permisos y el flujo afectado con la matriz de la sección 11. Registrar el commit desplegado y el resultado antes de devolver el servicio.

## 9. Respaldo y recuperación

Respaldar conjuntamente la base configurada, evidencias/adjuntos y configuración privada. Guardar copias fuera de htdocs y fuera del repositorio, con acceso restringido. Para MariaDB/MySQL, usar mysqldump con un archivo privado de opciones para las credenciales; para SQLite, realizar copia consistente mediante el mecanismo de respaldo de SQLite o detener escrituras de forma controlada. No copiar una base abierta sin garantizar consistencia.

1. Registrar fecha, entorno, commit, versión del motor, ubicación privada de respaldo y cantidad de archivos; verificar integridad y que el respaldo pueda abrirse.
1. Restaurar primero en una instancia aislada, sin tareas de correo ni integraciones activas. Recuperar base y archivos del mismo punto de corte, junto con la configuración correcta del entorno.
1. Comprobar ingreso, lectura de expedientes/evidencias y reportes; después habilitar tareas y correo de manera controlada. Conservar evidencia del ensayo.
1. Definir con la empresa la frecuencia, retención, objetivo de pérdida máxima de datos (RPO) y tiempo objetivo de recuperación (RTO). Este documento no inventa valores aprobados.

### Reversa de una actualización

Identificar el último commit estable y comprobar compatibilidad de esquema antes de volver al código anterior. Una reversa de código no deshace una migración ni recupera adjuntos. Si la estructura o datos cambiaron de forma incompatible, restaurar el conjunto coordinado de base y archivos con la ventana operativa acordada. Evitar reset --hard o limpieza de carpetas como mecanismo de recuperación.


## 10. Diagnóstico y continuidad


| Síntoma | Diagnóstico inicial |
| --- | --- |
| No abre la aplicación | Comprobar Apache, URL, carpeta pública y reglas .htaccess; revisar log PHP sin exponer detalles al usuario. |
| No conecta a persistencia | Comprobar servicio y ruta de base o archivos, identidad de Apache y configuración privada; no recrear la base para probar. |
| Ingreso rechazado | Confirmar usuario activo, alcance y mecanismo de acceso de la sección 2; revisar reloj y registros antes de insistir. |
| OTP o correo no llega | Si la app utiliza correo, comprobar configuración y conectividad SMTP, spam y cola; un archivo de log no demuestra entrega. En Portal no aplica. |
| No carga evidencia / exportación | Comprobar escritura en rutas runtime, límite de carga PHP, espacio y extensiones; conservar archivos históricos. |
| Reporte sin datos o desactualizado | Verificar filtro, alcance, fecha de corte y fuente; distinguir cero real de dato ausente. |
| Falla después de actualizar | Comparar commit, dependencias y migraciones aplicadas; revisar compatibilidad antes de ejecutar la reversa. |


### Limitaciones y asuntos por validar

- La copia revisada está en main; las notas históricas mencionan ramas divergentes, lo cual no prueba una reconciliación actual.
- No se comprobó configuración real de Cloudinary ni existencia de alertas fuera de visitas a pantalla.

## 11. Validación y aceptación

Durante esta entrega se validó la documentación, la existencia de fuentes citadas y la sintaxis de los PHP principales indicados a continuación. No se ejecutó la batería de negocio ni se certificó producción. Las pruebas funcionales propuestas requieren PC TEST y datos aislados; su estado es pendiente, no aprobado.


| Comprobación documental | Resultado |
| --- | --- |
| Archivos principales citados | Existencia verificada en la copia local |
| Sintaxis PHP de fuentes seleccionadas | 30 archivos; todos con salida 0 de php -l |
| Contenido DOCX y Markdown | Validación estructural automatizada antes del commit |
| Flujos de negocio, SMTP y restauración | No ejecutados; pendientes de aceptación operativa |


| Caso propuesto | Criterio de aceptación | Estado |
| --- | --- | --- |
| Recepción abierta | Evitar devolver una recepción cerrada o ajena según políticas. | Pendiente en PC TEST |
| Comparación | PDF y pantalla muestran las mismas diferencias de kilometraje, combustible y evidencias. | Pendiente en PC TEST |
| Intervalos | Completar básico reinicia su categoría sin alterar el mantenimiento mayor. | Pendiente en PC TEST |


### Pruebas existentes localizadas

Se localizaron 17 archivos de prueba rastreados. La lista siguiente permite ubicar pruebas relacionadas; su presencia no demuestra ejecución ni aprobación y algunas modifican datos de prueba.


| Archivo de prueba (selección) |
| --- |
| tests/Feature/AuthorizationTest.php |
| tests/Feature/ComparisonReportTest.php |
| tests/Feature/EcosystemShellTest.php |
| tests/Feature/FullFlowTest.php |
| tests/Feature/HelpRequestTest.php |
| tests/Feature/InstallCommandTest.php |
| tests/Feature/LoginCodeRateLimitTest.php |
| tests/Feature/MaintenanceScheduleTest.php |
| tests/Feature/ReceptionSearchTest.php |
| tests/Feature/UserManagementTest.php |
| tests/Feature/VehicleCalendarTest.php |
| tests/Feature/VehicleDeliveryTest.php |
| tests/Feature/VehicleMovementMailTest.php |
| tests/Feature/VehicleReceptionTest.php |
| tests/Feature/VehicleServiceTest.php |
| tests/TestCase.php |
| tests/Unit/ExampleTest.php |


## 12. Fuentes y control de cambios

Las fuentes son rutas relativas al repositorio de esta aplicación. El hash del commit identifica el código confirmado; el manifiesto documenta además hashes de las fuentes revisadas en el árbol de trabajo, que puede contener cambios locales anteriores. La revisión no replica configuración privada ni datos de producción.


| Fuente | Referencia |
| --- | --- |
| AGENTS.md | SHA-256 en evidencia_revision.json |
| README.md | SHA-256 en evidencia_revision.json |
| app/Enums/MaintenanceCategory.php | SHA-256 en evidencia_revision.json |
| app/Http/Controllers/Concerns/HandlesVehicleFormUploads.php | SHA-256 en evidencia_revision.json |
| app/Http/Controllers/VehicleDeliveryController.php | SHA-256 en evidencia_revision.json |
| app/Http/Controllers/VehicleReceptionController.php | SHA-256 en evidencia_revision.json |
| app/Support/VehicleComparisonBuilder.php | SHA-256 en evidencia_revision.json |
| composer.json | SHA-256 en evidencia_revision.json |
| composer.lock | SHA-256 en evidencia_revision.json |
| config/vehicle.php | SHA-256 en evidencia_revision.json |
| database/migrations/0001_01_01_000000_create_users_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/0001_01_01_000001_create_cache_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/0001_01_01_000002_create_jobs_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2025_01_01_000001_add_role_to_users_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2025_01_01_000002_create_vehicles_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2025_01_01_000003_create_vehicle_receptions_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2025_01_01_000004_create_vehicle_deliveries_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2025_01_01_000005_create_vehicle_photos_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2025_01_01_000006_create_vehicle_documentations_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_09_03_000001_add_fuel_type_to_vehicle_receptions_and_deliveries_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_09_03_000002_create_vehicle_equipment_checks_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_09_03_000003_create_vehicle_condition_items_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_09_14_000001_add_location_and_washed_to_vehicle_receptions_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_09_14_000002_create_vehicle_services_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_09_16_000001_add_location_to_vehicle_deliveries_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_09_16_000002_create_vehicle_maintenance_schedules_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_09_16_000003_create_vehicle_maintenance_completions_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_09_16_000004_create_login_verification_codes_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_09_19_000001_remove_transmission_service_insurance_policy_and_legacy_fuel_type.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_09_25_000001_add_receives_notification_emails_to_users_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_09_25_000002_drop_cleanliness_from_receptions_and_deliveries.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_09_25_000003_add_alert_progress_to_vehicle_maintenance_schedules_table.php | SHA-256 en evidencia_revision.json |
| database/migrations/2026_10_26_000001_create_audit_logs_table.php | SHA-256 en evidencia_revision.json |
| routes/web.php | SHA-256 en evidencia_revision.json |


| Edición | Fecha | Cambio |
| --- | --- | --- |
| 1.0 | 31 de octubre de 2026 | Manual técnico empresarial inicial basado en revisión local de fuentes. |
