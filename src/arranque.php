<?php
declare(strict_types=1);

/* Carga la configuración y las clases. Lo usan public/index.php y tests/pruebas.php. */

/*
 * Respaldo por si la extensión mbstring está apagada (pasa en PHP para Windows: se activa
 * quitando el ; de «extension=mbstring» en php.ini). Con mbstring encendida no se usa nada de esto.
 */
if (!function_exists('mb_strtoupper')) {
    define('EDUNEX_MIN', ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ']);
    define('EDUNEX_MAY', ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ']);
    function mb_strtoupper(string $s): string { return str_replace(EDUNEX_MIN, EDUNEX_MAY, strtoupper($s)); }
    function mb_strtolower(string $s): string { return str_replace(EDUNEX_MAY, EDUNEX_MIN, strtolower($s)); }
    function mb_substr(string $s, int $i, ?int $n = null): string { preg_match_all('/./us', $s, $m); return implode('', array_slice($m[0], $i, $n)); }
    function mb_strlen(string $s): int { return (int) preg_match_all('/./us', $s); }
    function mb_check_encoding(string $s, string $e = 'UTF-8'): bool { return preg_match('//u', $s) === 1; }
    function mb_convert_encoding(string $s, string $a, string $de): string { return function_exists('iconv') ? (string) iconv($de, $a . '//TRANSLIT', $s) : $s; }
    function mb_strimwidth(string $s, int $i, int $n): string { return mb_substr($s, $i, $n); }
}
require __DIR__ . '/config.php';
require __DIR__ . '/Almacen.php';
require __DIR__ . '/Seguridad.php';
require __DIR__ . '/Auditoria.php';
require __DIR__ . '/Nube.php';
require __DIR__ . '/Nucleo.php';
require __DIR__ . '/Vistas.php';
require __DIR__ . '/Pruebas.php';

// En el servidor no se muestran errores técnicos al usuario (se guardan en el registro del servidor).
ini_set('display_errors', PHP_SAPI === 'cli' ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
