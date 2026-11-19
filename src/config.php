<?php
declare(strict_types=1);

/*
 * Configuración. Lo que cambia entre la computadora de prueba y el servidor en la nube
 * se lee de variables de entorno (las pone Docker); si no existen, se usan estos valores.
 */

date_default_timezone_set('America/Mexico_City');

// Carpeta donde se guardan los datos. Está FUERA de public/ para que nadie la pueda
// descargar escribiendo su dirección en el navegador.
define('EDUNEX_DATOS', getenv('EDUNEX_DATOS') ?: dirname(__DIR__) . '/datos');

// Proxies de confianza (por ejemplo, Caddy dentro de Docker). Solo si la petición llega
// desde uno de estos, se cree el encabezado X-Forwarded-For para saber la IP real.
define('EDUNEX_PROXIES', array_filter(array_map('trim', explode(',', getenv('EDUNEX_PROXIES') ?: ''))));

// Minutos sin actividad antes de cerrar la sesión de Coordinación o caja.
define('EDUNEX_SESION_MIN', 20);

// Intentos fallidos permitidos antes de bloquear un rato (contra adivinar contraseñas o códigos).
define('EDUNEX_MAX_INTENTOS_LOGIN', 5);
define('EDUNEX_MAX_INTENTOS_CONSULTA', 10);
define('EDUNEX_BLOQUEO_MIN', 15);

/*
 * Usuarios de DEMOSTRACIÓN. Las contraseñas NO se guardan: se guarda su hash (password_hash,
 * algoritmo bcrypt). Al entrar se compara con password_verify. Así, aunque alguien robara
 * este archivo, no vería las contraseñas.
 *   coordinador / edunex2026     caja / caja2026
 * En el sistema real se entraría con la cuenta institucional del Tec (Microsoft 365).
 */
const EDUNEX_USUARIOS = [
    'coordinador' => ['rol' => 'coordinador', 'hash' => '$2y$10$a0UM/1Q554yq5.zGS98Xz.FRT5geh8WacNhaRi8eOECcMVKkOimnq'],
    'caja' => ['rol' => 'caja', 'hash' => '$2y$10$K.ee9hPYj.ZHVTOfZA1gPeWsrVWpD.W2QjHJf1KRUaiR8NIJYN.g.'],
];
