import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Public Sans"', ...defaultTheme.fontFamily.sans],
                data: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Paleta corporativa Carrousel (misma línea visual que
                // FernandoZL/helpdesk-carrousel — ver docs/ESTANDAR_VISUAL_CARROUSEL.md
                // de ese repo). Los nombres brand.* se conservan para no romper
                // las vistas existentes; solo cambian los valores.
                brand: {
                    magenta: '#213a8f', // acción primaria — antes magenta, ahora azul corporativo
                    cyan: '#0d6efd',    // enlaces / foco / acentos secundarios
                    olive: '#067647',   // éxito / disponible / ok
                    amber: '#9a6700',   // advertencia / pendiente
                    orange: '#b42318',  // anomalía / error / peligro
                    violet: '#6b46c1',
                    pink: '#e62e7c',
                    yellow: '#f4c430',
                },
            },
        },
    },
    plugins: [],
};
