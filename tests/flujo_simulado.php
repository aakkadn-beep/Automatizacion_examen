<?php
/*
 * Simulador de los flujos de Power Automate, SOLO para probar la conexión sin internet.
 * Se corre con:  php -S 127.0.0.1:8090 tests/flujo_simulado.php
 * Guarda las "filas del Excel" en /tmp/excel_simulado.json.
 *   POST /registro  {control, nombre, ...}  → agrega la fila (como «Agregar una fila a una tabla»)
 *   POST /consulta  {control, codigo}       → {"value":[fila]} (como «Enumerar filas» filtrando por control)
 *   POST /consulta  {control: ""}           → la tabla completa
 *   POST /cruce                             → flujo C: marca «pagado» según /tmp/pagos_simulados.json
 *   GET  /pagar?control=...                 → marca «pagado» a mano
 *   GET  /borrar?control=...                → borra la fila (como borrarla en el Excel)
 */
$archivo = '/tmp/excel_simulado.json';
$filas = is_file($archivo) ? json_decode(file_get_contents($archivo), true) : [];
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$entrada = json_decode((string) file_get_contents('php://input'), true) ?: [];
header('Content-Type: application/json');
if ($ruta === '/registro' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $filas[] = $entrada;      // el flujo A real pone cada dato en su columna del Excel
    file_put_contents($archivo, json_encode($filas, JSON_UNESCAPED_UNICODE));
    echo json_encode(['ok' => true]);
} elseif ($ruta === '/consulta' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Como el flujo B recomendado: «Enumerar filas» SIN filtro y regresa toda la tabla;
    // PHP busca la fila del número de control. Encabezados como en el Excel de la autora.
    // ?modo=todo (por defecto): regresa la tabla completa · ?modo=filtra: solo la fila del control pedido
    // ?modo=error: responde 400, como cuando la consulta de filtro usa una columna que no existe
    $modo = $_GET['modo'] ?? 'todo';
    if ($modo === 'error') {
        http_response_code(400);
        echo json_encode(['error' => ['code' => 'InvalidFilterClause', 'message' => "Could not find a property named 'control'"]]);
        exit;
    }
    if ($modo === 'filtra') {
        $filas = array_values(array_filter($filas, fn ($f) => ($f['control'] ?? '') === ($entrada['control'] ?? '')));
    }
    $hall = array_map(fn ($f) => ['Número de control' => $f['control'], 'Nombre' => $f['nombre'], 'Carrera' => $f['carrera'],
        'Grupo' => $f['grupo'], 'Estatus' => $f['estatus'], 'Referencia' => $f['referencia'] ?? '', 'Codigo' => $f['codigo'] ?? '',
        'Nivel' => $f['nivel'] ?? ''], $filas);
    echo json_encode(['value' => $hall], JSON_UNESCAPED_UNICODE);
} elseif ($ruta === '/cruce' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Flujo C: marca «pagado» a quien tenga su referencia en la tabla de pagos simulada
    $pagos = is_file('/tmp/pagos_simulados.json') ? json_decode(file_get_contents('/tmp/pagos_simulados.json'), true) : [];
    $n = 0;
    foreach ($filas as &$f) { if (in_array($f['referencia'] ?? '', $pagos, true) && ($f['estatus'] ?? '') !== 'pagado') { $f['estatus'] = 'pagado'; $n++; } }
    unset($f);
    file_put_contents($archivo, json_encode($filas, JSON_UNESCAPED_UNICODE));
    echo json_encode(['ok' => true, 'marcados' => $n]);
} elseif ($ruta === '/borrar') {
    $filas = array_values(array_filter($filas, fn ($f) => $f['control'] !== ($_GET['control'] ?? '')));
    file_put_contents($archivo, json_encode($filas, JSON_UNESCAPED_UNICODE));
    echo json_encode(['ok' => true]);
} elseif ($ruta === '/pagar') {
    foreach ($filas as &$f) { if ($f['control'] === ($_GET['control'] ?? '')) { $f['estatus'] = 'pagado'; } }
    file_put_contents($archivo, json_encode($filas, JSON_UNESCAPED_UNICODE));
    echo json_encode(['ok' => true]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'ruta']);
}
