<?php
declare(strict_types=1);

/*
 * Punto de entrada único (front controller). Toda petición llega aquí:
 *   ?r=registro, ?r=panel, ...  → muestran una pantalla (método GET)
 *   ?r=registrar, ?r=pagos, ... → hacen una acción (método POST) y redirigen
 *
 * Patrón POST → redirección → GET: después de guardar se redirige a una pantalla,
 * así recargar la página no vuelve a enviar el formulario ni duplica registros.
 */

require dirname(__DIR__) . '/src/arranque.php';

Seguridad::cabeceras();
Seguridad::iniciarSesion();

$r = preg_replace('/[^a-z]/', '', (string) ($_GET['r'] ?? 'inicio')) ?: 'inicio';
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rol = Seguridad::rol();

/** Estado de la base simulada (se crea con 60 aspirantes ficticios la primera vez). */
$porDefecto = static fn () => Nucleo::nuevoConDatos(60);

function ir(string $destino): never
{
    header('Location: ?r=' . $destino, true, 303);
    exit;
}

function flash(string $clave, string $tipo, string $texto): void
{
    $_SESSION['flash'][$clave] = [$tipo, $texto];
}

function tomarFlash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function exigirRol(?string $rol, string $vista): void
{
    if (!Nucleo::puedeVer($rol, $vista)) {
        Auditoria::registrar('acceso_denegado', 'bloqueado', 'pantalla ' . $vista);
        $_SESSION['siguiente'] = $vista;
        $_SESSION['motivo'] = 'Esta parte es solo para ' . ($vista === 'caja' ? 'la Coordinación y caja' : 'la Coordinación') . '. Entra con tu usuario y contraseña.';
        ir('acceso');
    }
}

/** Lee un archivo CSV subido. Límite de 1 MB y debe ser texto UTF-8. */
function archivoSubido(): ?string
{
    $f = $_FILES['archivo'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        return null;
    }
    if ($f['size'] > 1024 * 1024) {
        return null;
    }
    $t = (string) file_get_contents($f['tmp_name']);
    if (!mb_check_encoding($t, 'UTF-8')) {
        $t = mb_convert_encoding($t, 'UTF-8', 'Windows-1252');   // Excel a veces guarda en ANSI
    }
    return $t;
}

function descargar(string $nombre, string $csv): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    echo "\xEF\xBB\xBF" . $csv;                  // BOM para que Excel respete los acentos
    exit;
}

/* ============================================================ acciones (POST) */

if ($metodo === 'POST') {
    // Protección CSRF: el formulario debe traer el "boleto" de esta sesión.
    if (!Seguridad::csrfValido($_POST['csrf'] ?? null)) {
        Auditoria::registrar('csrf_invalido', 'bloqueado', 'acción ' . $r);
        http_response_code(400);
        echo Vistas::pagina('Formulario vencido', 'inicio', '<div class="angosto"><h1>El formulario venció</h1><p>Por seguridad, vuelve a abrir la página e inténtalo otra vez.</p><a class="btn" href="?r=inicio">Ir al inicio</a></div>', $rol);
        exit;
    }

    switch ($r) {
        case 'registrar':
            $d = ['control' => trim((string) ($_POST['control'] ?? '')), 'nombre' => mb_substr(trim((string) ($_POST['nombre'] ?? '')), 0, 80),
                'carrera' => (string) ($_POST['carrera'] ?? ''), 'grupo' => (string) ($_POST['grupo'] ?? '')];
            $asistido = $rol === 'coordinador' && !empty($_POST['asistido']);
            $res = Almacen::transaccion('estado', $porDefecto, static fn (array &$st) => Nucleo::registrar($st, $d, $asistido));
            if (!$res['ok']) {
                Auditoria::registrar('registro', 'rechazado', 'control ' . $d['control'] . ': ' . implode(' | ', array_keys($res['errores'])));
                $_SESSION['errores'] = $res['errores'];
                $_SESSION['viejo'] = $d;
                ir('registro');
            }
            Auditoria::registrar('registro', 'ok', 'control ' . $res['rec']['c'] . ($asistido ? ' (asistido)' : ''));
            $_SESSION['hecho'] = $res['rec'];
            ir('registro');

        case 'consultar':
            $clave = 'consulta:' . Seguridad::ip();
            if (Seguridad::bloqueado($clave, EDUNEX_MAX_INTENTOS_CONSULTA)) {
                Auditoria::registrar('consulta', 'bloqueado', 'demasiados intentos');
                $_SESSION['qerror'] = 'Hubo demasiados intentos fallidos desde este equipo. Espera ' . EDUNEX_BLOQUEO_MIN . ' minutos e inténtalo de nuevo.';
                ir('consulta');
            }
            $c = trim((string) ($_POST['control'] ?? ''));
            $st = Almacen::leer('estado', $porDefecto);
            $v = Nucleo::consultar($st, $c, (string) ($_POST['codigo'] ?? ''));
            if ($v === null) {
                Seguridad::fallo($clave);
                Auditoria::registrar('consulta', 'no_coincide', 'control ' . mb_substr($c, 0, 8));
                $_SESSION['qerror'] = 'No encontramos ese número de control con ese código. Revisa los dos datos e inténtalo de nuevo.';
                $_SESSION['qcontrol'] = $c;
                ir('consulta');
            }
            Seguridad::limpiarIntentos($clave);
            Auditoria::registrar('consulta', 'ok', 'control ' . $c);
            $_SESSION['qres'] = $v;
            $_SESSION['qcontrol'] = $c;
            ir('consulta');

        case 'entrar':
            $u = strtolower(trim((string) ($_POST['usuario'] ?? '')));
            $clave = 'login:' . $u;
            if (Seguridad::bloqueado($clave, EDUNEX_MAX_INTENTOS_LOGIN)) {
                Auditoria::registrar('inicio_sesion', 'bloqueado', 'usuario ' . mb_substr($u, 0, 30));
                $_SESSION['lerror'] = 'Demasiados intentos fallidos. Espera ' . EDUNEX_BLOQUEO_MIN . ' minutos.';
                ir('acceso');
            }
            $nuevo = Seguridad::entrar($u, (string) ($_POST['clave'] ?? ''));
            if ($nuevo === null) {
                Seguridad::fallo($clave);
                Auditoria::registrar('inicio_sesion', 'fallido', 'usuario ' . mb_substr($u, 0, 30));
                $_SESSION['lerror'] = 'Usuario o contraseña incorrectos. Revisa los datos de prueba que aparecen abajo.';
                ir('acceso');
            }
            Seguridad::limpiarIntentos($clave);
            Auditoria::registrar('inicio_sesion', 'ok');
            $sig = $_SESSION['siguiente'] ?? '';
            unset($_SESSION['siguiente'], $_SESSION['motivo']);
            ir(Nucleo::puedeVer($nuevo, $sig) && $sig !== '' ? $sig : ($nuevo === 'caja' ? 'caja' : 'panel'));

        case 'salir':
            Auditoria::registrar('cierre_sesion', 'ok');
            Seguridad::salir();
            ir('inicio');

        case 'fechas':
            exigirRol($rol, 'panel');
            $v = ['cierre' => (string) ($_POST['cierre'] ?? ''), 'payFrom' => (string) ($_POST['payFrom'] ?? ''),
                'payTo' => (string) ($_POST['payTo'] ?? ''), 'exam' => (string) ($_POST['exam'] ?? '')];
            $hoy = (string) ($_POST['hoy'] ?? '');
            $error = Nucleo::validarFechas($v);
            if ($error === null && $hoy !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hoy)) {
                $error = 'La fecha simulada no es válida.';
            }
            if ($error) {
                flash('fechas', 'mal', $error);
                ir('panel');
            }
            Almacen::transaccion('estado', $porDefecto, static function (array &$st) use ($v, $hoy) {
                $st['conv'] = $v;
                $st['hoy'] = $hoy;
            });
            Auditoria::registrar('fechas', 'ok', implode(' ', $v) . ($hoy ? ' hoy=' . $hoy : ''));
            flash('fechas', 'ok', 'Fechas guardadas. Se usan en validaciones, avisos y listas.');
            ir('panel');

        case 'pagos':
            exigirRol($rol, 'panel');
            $ejemplo = ($_POST['modo'] ?? '') === 'ejemplo';
            $texto = $ejemplo ? null : archivoSubido();
            if (!$ejemplo && $texto === null) {
                flash('pagos', 'mal', 'Elige primero un archivo CSV (máximo 1 MB), o usa el reporte de ejemplo.');
                ir('panel');
            }
            $res = Almacen::transaccion('estado', $porDefecto, static function (array &$st) use ($texto) {
                $t = $texto ?? Nucleo::pagosEjemploCSV($st, 60, true);
                return Nucleo::importarPagos($st, $t);
            });
            if (!$res['ok']) {
                flash('pagos', 'mal', $res['error']);
            } elseif ($res['total'] === 0) {
                flash('pagos', 'aviso', 'No hay pagos que cruzar (¿ya están todos pagados? Reinicia los datos de prueba).');
            } else {
                flash('pagos', 'ok', "Reporte de {$res['total']} pagos: {$res['identificados']} quedaron «pagado» sin intervención y {$res['excepciones']} pasaron a excepciones.");
            }
            Auditoria::registrar('cruce_pagos', $res['ok'] ? 'ok' : 'rechazado', ($ejemplo ? 'ejemplo' : 'archivo') . ($res['ok'] ? ": {$res['total']} pagos, {$res['identificados']} identificados, {$res['excepciones']} excepciones" : ''));
            ir('panel');

        case 'asociar':
            exigirRol($rol, 'panel');
            $id = (int) ($_POST['id'] ?? 0);
            $c = (string) ($_POST['control'] ?? '');
            $res = Almacen::transaccion('estado', $porDefecto, static fn (array &$st) => Nucleo::asociarExcepcion($st, $id, $c));
            flash('excepciones', $res['ok'] ? 'ok' : 'mal', $res['ok'] ? 'Pago asociado. El aspirante ahora aparece como pagado.' : $res['error']);
            Auditoria::registrar('asociar_excepcion', $res['ok'] ? 'ok' : 'rechazado', 'excepción ' . $id . ' → control ' . mb_substr($c, 0, 8));
            ir('panel');

        case 'resultados':
            exigirRol($rol, 'panel');
            $st = Almacen::leer('estado', $porDefecto);
            $ejemplo = ($_POST['modo'] ?? '') === 'ejemplo';
            $texto = $ejemplo ? Nucleo::resultadosEjemploCSV($st, 7) : archivoSubido();
            if ($texto === null) {
                flash('resultados', 'mal', 'Elige primero un archivo CSV (máximo 1 MB), o usa el resultado de ejemplo.');
                ir('panel');
            }
            $p = Nucleo::previsualizarResultados($st, $texto);
            if (!$p['ok']) {
                unset($_SESSION['prev']);
                flash('resultados', 'mal', $p['error']);
            } else {
                $_SESSION['prev'] = $p;      // se guarda en la sesión hasta que se confirme
                flash('resultados', count($p['exc']) ? 'aviso' : 'ok', "Archivo de {$p['total']} resultados: " . count($p['asociados']) . ' se asociarán a su aspirante y ' . count($p['exc']) . ' irán a excepciones. Si es correcto, confirma.');
            }
            Auditoria::registrar('revisar_resultados', $p['ok'] ? 'ok' : 'rechazado', $ejemplo ? 'ejemplo' : 'archivo');
            ir('panel');

        case 'publicar':
            exigirRol($rol, 'panel');
            $p = $_SESSION['prev'] ?? null;
            unset($_SESSION['prev']);
            if (!$p) {
                flash('resultados', 'mal', 'Primero revisa un archivo de resultados.');
                ir('panel');
            }
            $res = Almacen::transaccion('estado', $porDefecto, static fn (array &$st) => Nucleo::publicarResultados($st, $p));
            Auditoria::registrar('publicar_resultados', 'ok', "{$res['publicados']} niveles, {$res['excepciones']} excepciones");
            flash('resultados', 'ok', "Niveles publicados: {$res['publicados']}. Excepciones: {$res['excepciones']}. Cada aspirante ya ve su nivel al consultar su estatus.");
            ir('panel');

        case 'recordatorio':
            exigirRol($rol, 'panel');
            $res = Almacen::transaccion('estado', $porDefecto, static fn (array &$st) => Nucleo::enviarRecordatorio($st));
            Auditoria::registrar('recordatorio', 'ok', count($res['pendientes']) . ' pendientes');
            flash('recordatorio', 'ok', 'Lista de ' . count($res['pendientes']) . ' aspirantes sin pago enviada a la Coordinación (simulado).' . ($res['vencido'] ? '' : ' Aún falta para la fecha del recordatorio; se envió de todos modos para la prueba.'));
            ir('panel');

        case 'datos':
            exigirRol($rol, 'panel');
            $n = (string) ($_POST['n'] ?? '');
            if ($n === 'medir') {
                $st = Almacen::leer('estado', $porDefecto);
                if (count($st['regs']) < 600) {
                    Nucleo::sembrar($st, 600 - count($st['regs']), 99);
                }
                $t = static function (callable $f): float {
                    $a = hrtime(true);
                    $f();
                    return (hrtime(true) - $a) / 1e6;
                };
                $reg = $t(static function () use (&$st) {
                    Nucleo::registrar($st, ['control' => '29990001', 'nombre' => 'Prueba Tiempo Medido', 'carrera' => Nucleo::CARRERAS[0], 'grupo' => 'A']);
                });
                $bus = $t(static fn () => Nucleo::filtrar($st, ['q' => '29990001']));
                $fil = $t(static fn () => Nucleo::filtrar($st, ['q' => '2624', 'carrera' => Nucleo::CARRERAS[0], 'grupo' => 'A', 'estatus' => 'Registrado']));
                flash('datos', 'ok', sprintf('Con %d registros (copia, no cambia tus datos): registrar %.2f ms · buscar %.2f ms · filtrar %.2f ms. Meta: menos de 3000 ms.', count($st['regs']), $reg, $bus, $fil));
                ir('panel');
            }
            $num = in_array($n, ['0', '60', '600'], true) ? (int) $n : 60;
            Almacen::transaccion('estado', $porDefecto, static function (array &$st) use ($num) {
                $st = Nucleo::nuevoConDatos($num);
            });
            unset($_SESSION['prev']);
            Auditoria::registrar('reiniciar_datos', 'ok', $num . ' aspirantes');
            flash('datos', 'ok', 'Datos de prueba reiniciados: ' . $num . ' aspirantes.');
            ir('panel');
    }
    http_response_code(404);
    echo Vistas::pagina('No encontrado', 'inicio', Vistas::noEncontrado(), $rol);
    exit;
}

/* ============================================================ pantallas (GET) */

switch ($r) {
    case 'estilos':
        header('Content-Type: text/css; charset=utf-8');
        header('Cache-Control: max-age=3600');
        echo Vistas::css();
        exit;

    case 'inicio':
        echo Vistas::pagina('Inicio', 'inicio', Vistas::inicio($rol), $rol);
        exit;

    case 'ayuda':
        echo Vistas::pagina('Ayuda', 'ayuda', Vistas::ayuda(), $rol);
        exit;

    case 'registro':
        $st = Almacen::leer('estado', $porDefecto);
        $err = $_SESSION['errores'] ?? [];
        $viejo = $_SESSION['viejo'] ?? [];
        $hecho = $_SESSION['hecho'] ?? null;
        unset($_SESSION['errores'], $_SESSION['viejo'], $_SESSION['hecho']);
        echo Vistas::pagina('Registro', 'registro', Vistas::registro($st, $rol, $err, $viejo, $hecho), $rol);
        exit;

    case 'consulta':
        $st = Almacen::leer('estado', $porDefecto);
        $res = $_SESSION['qres'] ?? null;
        $error = $_SESSION['qerror'] ?? null;
        $c = $_SESSION['qcontrol'] ?? preg_replace('/\D/', '', (string) ($_GET['c'] ?? ''));
        unset($_SESSION['qres'], $_SESSION['qerror'], $_SESSION['qcontrol']);
        echo Vistas::pagina('Consulta de estatus', 'consulta', Vistas::consulta($st, $res, $error, (string) $c), $rol);
        exit;

    case 'acceso':
        $err = $_SESSION['lerror'] ?? null;
        unset($_SESSION['lerror']);
        echo Vistas::pagina('Acceso', 'acceso', Vistas::acceso($err, $_SESSION['motivo'] ?? 'Esta parte requiere usuario y contraseña.'), $rol);
        exit;

    case 'panel':
        exigirRol($rol, 'panel');
        $st = Almacen::leer('estado', $porDefecto);
        $f = ['q' => mb_substr((string) ($_GET['q'] ?? ''), 0, 60), 'estatus' => (string) ($_GET['estatus'] ?? ''),
            'carrera' => (string) ($_GET['carrera'] ?? ''), 'grupo' => (string) ($_GET['grupo'] ?? '')];
        $esquema = Seguridad::esHttps() ? 'https' : 'http';
        $url = $esquema . '://' . preg_replace('/[^A-Za-z0-9.:\-\[\]]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost') . '/?r=registro';
        echo Vistas::pagina('Panel', 'panel', Vistas::panel($st, $f, tomarFlash(), $_SESSION['prev'] ?? null, $url), $rol);
        exit;

    case 'caja':
        exigirRol($rol, 'caja');
        echo Vistas::pagina('Reporte para caja', 'caja', Vistas::caja(Almacen::leer('estado', $porDefecto)), $rol);
        exit;

    case 'auditoria':
        exigirRol($rol, 'auditoria');
        echo Vistas::pagina('Bitácora', 'auditoria', Vistas::auditoria(Auditoria::ultimos(200)), $rol);
        exit;

    case 'pruebas':
        [$res, $ms] = Pruebas::correr();
        $_SESSION['ultimas_pruebas'] = $res;
        echo Vistas::pagina('Pruebas', 'pruebas', Vistas::pruebas($res, $ms), $rol);
        exit;

    case 'descargar':
        $que = (string) ($_GET['que'] ?? '');
        $st = Almacen::leer('estado', $porDefecto);
        switch ($que) {
            case 'pagados':
                exigirRol($rol, 'panel');
                Auditoria::registrar('descarga', 'ok', 'lista del examen');
                descargar('lista_examen_pagados.csv', Nucleo::aCSV(Nucleo::COLS['pagados'], Nucleo::listaPagados($st)));
            case 'nivel':
                exigirRol($rol, 'panel');
                Auditoria::registrar('descarga', 'ok', 'lista por nivel');
                descargar('lista_por_nivel.csv', Nucleo::aCSV(Nucleo::COLS['nivel'], Nucleo::listaNivel($st)));
            case 'pendientes':
                exigirRol($rol, 'panel');
                Auditoria::registrar('descarga', 'ok', 'pendientes de pago');
                descargar('pendientes_de_pago.csv', Nucleo::aCSV(Nucleo::COLS['pendientes'], Nucleo::recordatorios($st)['pendientes']));
            case 'caja':
                exigirRol($rol, 'caja');
                Auditoria::registrar('descarga', 'ok', 'reporte para caja');
                descargar('reporte_para_caja.csv', Nucleo::aCSV(Nucleo::COLS['caja'], Nucleo::reporteCaja($st)));
            case 'auditoria':
                exigirRol($rol, 'auditoria');
                descargar('bitacora_auditoria.csv', Nucleo::aCSV(Auditoria::COLS, Auditoria::ultimos(100000)));
            case 'pruebas':
                $res = $_SESSION['ultimas_pruebas'] ?? Pruebas::correr()[0];
                descargar('resultados_pruebas.csv', Nucleo::aCSV([['cp', 'CP'], ['req', 'Requisito'], ['que', 'Qué se prueba'], ['esp', 'Resultado esperado'], ['obtenido', 'Resultado obtenido'], ['estado', 'Estado']], $res));
        }
}

http_response_code(404);
echo Vistas::pagina('No encontrado', 'inicio', Vistas::noEncontrado(), $rol);
