<?php

namespace App\Support;

/**
 * Content of the ecosystem help bubble ("?") per screen: a short guide and
 * the guided tour steps ([selector, title, text, tip]). The common shell
 * steps (menu, bell, Portal, help) are appended by vehiculos-ecosistema.js.
 */
class ScreenHelp
{
    public static function for(?string $routeName): array
    {
        $screens = [
            'dashboard' => [
                'title' => 'Panel',
                'steps' => [
                    'Revisa primero los vehículos que siguen fuera (recepciones abiertas).',
                    'Usa “Nueva recepción” cuando un vehículo sale y “Devoluciones” cuando regresa.',
                    'La campana avisa de vehículos sin devolver y mantenimientos por atender.',
                ],
                'tour' => [
                    ['.page-heading', 'Panel', 'Resumen del movimiento de la flota.', ''],
                    ['.topbar-primary-action', 'Nueva recepción', 'Registra la salida de un vehículo con fotos, checklist y firma.', ''],
                ],
            ],
            'calendar' => [
                'title' => 'Calendario',
                'steps' => [
                    'Consulta qué vehículos están libres o en uso en cada fecha.',
                    'Muévete entre meses con los botones de la parte superior.',
                ],
                'tour' => [['.page-heading', 'Calendario', 'Disponibilidad de la flota por fecha.', '']],
            ],
            'receptions' => [
                'title' => 'Recepciones',
                'steps' => [
                    'Una recepción registra el estado del vehículo cuando sale: kilometraje, combustible, checklist, fotos y firma.',
                    'Completa los pasos en orden; las fotos se comprimen antes de subir.',
                    'La recepción queda abierta hasta registrar su devolución.',
                ],
                'tour' => [
                    ['.page-heading', 'Recepciones', 'Salidas de vehículos registradas.', ''],
                    ['.step-indicator', 'Pasos', 'El formulario avanza por pasos; revisa cada uno antes de guardar.', ''],
                    ['.data-table-shell', 'Listado', 'Abre una recepción para ver su detalle o su comparación.', ''],
                ],
            ],
            'deliveries' => [
                'title' => 'Devoluciones',
                'steps' => [
                    'Elige el vehículo y luego la recepción abierta que se está cerrando.',
                    'Revisa el reporte de daños antes de registrar la devolución.',
                    'Al guardar, la recepción se cierra y se puede descargar la comparación en PDF.',
                ],
                'tour' => [
                    ['.page-heading', 'Devoluciones', 'Regresos de vehículos y su comparación con la salida.', ''],
                    ['.data-table-shell', 'Listado', 'Devoluciones registradas.', ''],
                ],
            ],
            'comparisons' => [
                'title' => 'Comparación',
                'steps' => ['Compara la salida y el regreso: kilometraje, combustible, documentos, checklist y fotos.', 'Descarga el PDF para compartirlo.'],
                'tour' => [['.page-heading', 'Comparación', 'Diferencias entre recepción y devolución.', '']],
            ],
            'maintenance-schedules' => [
                'title' => 'Mantenimiento',
                'steps' => [
                    'Cada vehículo tiene servicio Básico (1,000 km) y Mayor (4,000 km).',
                    'En rojo los vencidos y en amarillo los que están a 200 km o menos.',
                    'Al completar un servicio se reinicia el conteo.',
                ],
                'tour' => [['.data-table-shell', 'Servicios', 'Estado de mantenimiento de cada vehículo.', '']],
            ],
            'vehicles' => [
                'title' => 'Vehículos',
                'steps' => ['Administra el catálogo de la flota.', 'Un vehículo con movimientos no se elimina: se conserva su historial.'],
                'tour' => [['.data-table-shell', 'Catálogo', 'Vehículos registrados.', '']],
            ],
            'users' => [
                'title' => 'Usuarios',
                'steps' => [
                    'Da acceso con nombre, correo y rol (Administrador o Agente).',
                    'Marca “Recibir notificaciones por correo” para quien deba recibir avisos.',
                    'El acceso es con un código temporal que llega al correo; no se usan contraseñas.',
                ],
                'tour' => [['.data-table-shell', 'Usuarios', 'Accesos, roles y avisos por correo.', '']],
            ],
            'admin.audit' => [
                'title' => 'Auditoría y trazabilidad',
                'steps' => ['Filtra por persona, acción o fecha.', 'Abre Ver detalle para comparar valores antes y después.', 'Aquí quedan ingresos, recepciones, devoluciones, mantenimientos y cambios de usuarios y vehículos.'],
                'tour' => [
                    ['.audit-stats', 'Resumen', 'Actividad de hoy, de los últimos 7 días, ingresos e intentos fallidos.', ''],
                    ['.audit-filter-card', 'Filtros', 'Acota por persona, acción o fecha.', ''],
                    ['.data-table-shell', 'Registro de actividad', 'Cuándo, qué acción, sobre qué registro y quién.', ''],
                ],
            ],
            'admin.mail' => [
                'title' => 'Correo y notificaciones',
                'steps' => ['Comprueba que el canal sea SMTP: sin correo nadie recibe su código de acceso.', 'Envía un correo de prueba antes de depender del correo en operación.', 'Los avisos van a los usuarios marcados para recibir notificaciones.'],
                'tour' => [
                    ['.eco-mail-status', 'Estado del canal', 'Servidor, puerto y remitente. La contraseña nunca se muestra.', ''],
                    ['.eco-mail-test', 'Prueba', 'Envía un correo de prueba.', ''],
                ],
            ],
            'help' => [
                'title' => 'Reportar un problema',
                'steps' => ['Describe qué pasó y en qué pantalla.', 'El mensaje llega por correo a los responsables de la flota.'],
                'tour' => [['.page-heading', 'Soporte', 'Envía tu reporte a los responsables.', '']],
            ],
        ];

        $routeName ??= '';
        foreach ($screens as $prefix => $help) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix.'.')) {
                return $help;
            }
        }

        return [
            'title' => 'Ayuda',
            'steps' => ['Usa el menú de la izquierda para moverte entre pantallas.', 'Si algo no funciona, usa “Reportar un problema”.'],
            'tour' => [],
        ];
    }
}
