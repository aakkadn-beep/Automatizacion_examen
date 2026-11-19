<?php
declare(strict_types=1);

/* Carga la configuración y las clases. Lo usan public/index.php y tests/pruebas.php. */
require __DIR__ . '/config.php';
require __DIR__ . '/Almacen.php';
require __DIR__ . '/Seguridad.php';
require __DIR__ . '/Auditoria.php';
require __DIR__ . '/Nucleo.php';
require __DIR__ . '/Vistas.php';
require __DIR__ . '/Pruebas.php';

// En el servidor no se muestran errores técnicos al usuario (se guardan en el registro del servidor).
ini_set('display_errors', PHP_SAPI === 'cli' ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
