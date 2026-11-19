<?php
declare(strict_types=1);

/*
 * Corre los casos CP-01 a CP-29 desde la terminal:
 *     php tests/pruebas.php            (muestra la tabla)
 *     php tests/pruebas.php --csv      (además guarda tests/resultados_pruebas.csv)
 * Devuelve código de salida 1 si algún caso falla (útil para revisar antes de cada entrega).
 */
require dirname(__DIR__) . '/src/arranque.php';

[$res, $ms] = Pruebas::correr();
$cuenta = ['Pasa' => 0, 'Falla' => 0, 'Pendiente' => 0];
foreach ($res as $x) {
    $cuenta[$x['estado']] = ($cuenta[$x['estado']] ?? 0) + 1;
    printf("%-6s %-10s %-26s %s\n", $x['cp'], $x['estado'], mb_strimwidth($x['req'], 0, 26), $x['obtenido']);
}
printf("\n%d casos: %d pasan, %d fallan, %d pendientes. Tiempo: %.0f ms. PHP %s. Fecha: %s\n",
    count($res), $cuenta['Pasa'], $cuenta['Falla'], $cuenta['Pendiente'], $ms, PHP_VERSION, date('Y-m-d H:i'));

if (in_array('--csv', $argv, true)) {
    $csv = Nucleo::aCSV([['cp', 'CP'], ['req', 'Requisito'], ['que', 'Qué se prueba'], ['esp', 'Resultado esperado'], ['obtenido', 'Resultado obtenido'], ['estado', 'Estado']], $res);
    file_put_contents(__DIR__ . '/resultados_pruebas.csv', "\xEF\xBB\xBF" . $csv);
    echo "Guardado en tests/resultados_pruebas.csv\n";
}
exit($cuenta['Falla'] > 0 ? 1 : 0);
