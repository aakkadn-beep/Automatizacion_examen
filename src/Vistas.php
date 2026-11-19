<?php
declare(strict_types=1);

/*
 * Vistas: arman la página que se envía al navegador.
 *
 * El navegador solo entiende HTML y CSS para dibujar una página; eso no cambia con ningún
 * lenguaje. Lo que cambia es QUIÉN lo escribe: aquí lo genera PHP en el servidor. No hay
 * archivos .html ni JavaScript: todos los botones son formularios que regresan al servidor.
 */
final class Vistas
{
    /* Colores de la identidad EduNex (manual de identidad, sección 2). La prueba CP-24
       calcula el contraste de cada par texto/fondo con estos mismos valores. */
    public const COLOR = [
        'azul' => '#12284C',    // azul Nexo
        'verde' => '#0B7F83',   // verde Conexión
        'verdeTexto' => '#066266',
        'ambar' => '#F5A623',
        'ambarTexto' => '#7A4600',
        'ambarFondo' => '#FFF1D6',
        'gris' => '#EEF2F6',
        'texto' => '#1F2933',
        'tenue' => '#4A5B70',
        'blanco' => '#FFFFFF',
        'linea' => '#CBD5E1',
        'ok' => '#1B6B3A', 'okFondo' => '#DCF2E3',
        'mal' => '#A8261B', 'malFondo' => '#FBE3E0',
    ];
    /** Pares texto/fondo que aparecen en la interfaz (para CP-24). */
    public const PARES = [
        ['texto', 'blanco'], ['texto', 'gris'], ['tenue', 'blanco'], ['tenue', 'gris'], ['blanco', 'azul'],
        ['blanco', 'verde'], ['verdeTexto', 'gris'], ['verdeTexto', 'blanco'], ['ambarTexto', 'ambarFondo'],
        ['ok', 'okFondo'], ['mal', 'malFondo'], ['azul', 'blanco'],
    ];

    public static function css(): string
    {
        $c = self::COLOR;
        return <<<CSS
*{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{margin:0;background:{$c['gris']};color:{$c['texto']};font:16px/1.5 Calibri,"Segoe UI",system-ui,sans-serif}
h1,h2,h3{font-family:Poppins,"Segoe UI",system-ui,sans-serif;color:{$c['azul']};line-height:1.25}
h1{font-size:1.6rem;margin:.2em 0 .4em}h2{font-size:1.25rem;border-bottom:3px solid {$c['verde']};padding-bottom:4px}h3{font-size:1.05rem;margin:0 0 .4em}
a{color:{$c['verdeTexto']}}
.wrap{max-width:1180px;margin:0 auto;padding:0 16px}
header.top{background:{$c['blanco']};border-bottom:1px solid {$c['linea']}}
.bar{display:flex;flex-wrap:wrap;gap:10px 20px;align-items:center;justify-content:space-between;padding:10px 0}
.marca{display:flex;gap:10px;align-items:center;text-decoration:none;color:{$c['azul']}}
.marca b{font:600 1.1rem Poppins,system-ui,sans-serif;display:block}.marca small{color:{$c['tenue']}}
.logo{width:38px;height:38px;flex:none}
nav{display:flex;flex-wrap:wrap;gap:6px;padding:0 0 10px}
nav a,.btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:8px 14px;border-radius:10px;font:600 15px Calibri,system-ui,sans-serif;text-decoration:none;cursor:pointer}
nav a{border:1px solid {$c['linea']};background:{$c['blanco']};color:{$c['texto']}}
nav a[aria-current=page]{background:{$c['verde']};border-color:{$c['verde']};color:{$c['blanco']}}
.btn{border:0;background:{$c['verde']};color:{$c['blanco']}}
.btn.alt{background:{$c['blanco']};color:{$c['verdeTexto']};border:2px solid {$c['verde']}}
.btn.azul{background:{$c['azul']}}
.btn.full{width:100%;margin-top:10px}
a:focus-visible,.btn:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible{outline:3px solid {$c['ambar']};outline-offset:2px}
main{padding:18px 0 40px}
.hero{background:{$c['azul']};color:{$c['blanco']};border-radius:16px;padding:28px 22px;margin-bottom:18px}
.hero h1{color:{$c['blanco']};font-size:2rem}.hero p{margin:0;font-size:1.1rem}
.tiles{list-style:none;padding:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
.tile{display:block;background:{$c['blanco']};border:1px solid {$c['linea']};border-left:6px solid {$c['verde']};border-radius:12px;padding:16px;text-decoration:none;color:{$c['azul']};font:600 1.05rem Poppins,system-ui,sans-serif;min-height:44px}
.tile.sta{border-left-color:{$c['azul']}}
.card.mt{margin-top:14px}
.card{background:{$c['blanco']};border:1px solid {$c['linea']};border-radius:12px;padding:16px;margin:0 0 14px}
.angosto{max-width:520px;margin:0 auto}
.field{margin:12px 0}
label{display:block;font-weight:600;margin-bottom:4px}
input,select,textarea{width:100%;min-height:44px;padding:8px 10px;font:16px Calibri,system-ui,sans-serif;border:1.5px solid {$c['tenue']};border-radius:10px;background:{$c['blanco']};color:{$c['texto']}}
input[type=checkbox]{width:auto;min-height:0}
.check{display:flex;gap:8px;align-items:center}
.err{color:{$c['mal']};background:{$c['malFondo']};border-radius:8px;padding:6px 10px;margin-top:6px;font-weight:600}
.okmsg{color:{$c['ok']};background:{$c['okFondo']};border-radius:8px;padding:8px 12px;font-weight:600}
.aviso{color:{$c['ambarTexto']};background:{$c['ambarFondo']};border-radius:8px;padding:8px 12px}
.nota{color:{$c['tenue']};font-size:.95rem}
.sim{display:inline-block;background:{$c['ambarFondo']};color:{$c['ambarTexto']};border-radius:999px;padding:2px 10px;font-size:.85rem;font-weight:600}
.kv{display:flex;justify-content:space-between;gap:12px;border-bottom:1px dashed {$c['linea']};padding:6px 0}
.grande{font:700 2.2rem Poppins,system-ui,monospace;letter-spacing:.2em;color:{$c['azul']}}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:14px}
.kpi{background:{$c['blanco']};border:1px solid {$c['linea']};border-radius:12px;padding:12px}
.kpi b{display:block;font:700 1.7rem Poppins,system-ui,sans-serif;color:{$c['azul']}}
.cols{display:grid;grid-template-columns:3fr 2fr;gap:16px}
.filtros{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:0 10px}
.fila{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.fila .btn{flex:0 1 auto}
.tabla{overflow-x:auto;background:{$c['blanco']};border:1px solid {$c['linea']};border-radius:12px;margin-bottom:12px}
table{border-collapse:collapse;width:100%;font-size:.95rem}
th{background:{$c['azul']};color:{$c['blanco']};text-align:left;padding:8px}
td{padding:7px 8px;border-top:1px solid {$c['linea']};vertical-align:top}
.pill{display:inline-block;border-radius:999px;padding:1px 10px;font-weight:600;font-size:.85rem}
.p-reg{background:{$c['ambarFondo']};color:{$c['ambarTexto']}}.p-pag{background:{$c['okFondo']};color:{$c['ok']}}
.pasa{color:{$c['ok']};font-weight:700}.falla{color:{$c['mal']};font-weight:700}.pend{color:{$c['ambarTexto']};font-weight:700}
footer{border-top:1px solid {$c['linea']};background:{$c['blanco']};padding:14px 0;color:{$c['tenue']};font-size:.9rem}
@media (max-width:820px){.cols{grid-template-columns:1fr}.hero h1{font-size:1.6rem}}
@media (max-width:480px){.wrap{padding:0 12px}nav a{flex:1 1 auto}.btn{width:100%}.fila .btn{width:100%}.grande{font-size:1.8rem}td,th{padding:6px}}
CSS;
    }

    private const LOGO = '<svg class="logo" viewBox="0 0 40 40" aria-hidden="true"><path d="M9 31V9l22 22V9" fill="none" stroke="#12284C" stroke-width="3" stroke-linejoin="round"/><circle cx="9" cy="31" r="4" fill="#12284C"/><circle cx="9" cy="9" r="4" fill="#12284C"/><circle cx="31" cy="31" r="4" fill="#0B7F83"/><circle cx="31" cy="9" r="4.5" fill="#F5A623"/></svg>';

    /** Estructura común de todas las páginas. */
    public static function pagina(string $titulo, string $vista, string $cuerpo, ?string $rol): string
    {
        $menu = [['inicio', 'Inicio'], ['registro', 'Registro'], ['consulta', 'Consulta de estatus']];
        if ($rol === 'coordinador') {
            $menu[] = ['panel', 'Panel'];
            $menu[] = ['auditoria', 'Bitácora'];
        }
        if ($rol !== null) {
            $menu[] = ['caja', 'Reporte para caja'];
        }
        $menu[] = ['pruebas', 'Pruebas'];
        $menu[] = ['ayuda', 'Ayuda'];
        $nav = '';
        foreach ($menu as [$r, $t]) {
            $nav .= '<a href="?r=' . $r . '"' . ($r === $vista ? ' aria-current="page"' : '') . '>' . e($t) . '</a>';
        }
        $sesion = $rol === null
            ? '<a class="btn alt" href="?r=acceso">Entrar</a>'
            : '<form method="post" action="?r=salir">' . self::csrf() . '<span class="nota">' . e(Seguridad::usuario()) . ' · </span><button class="btn alt" type="submit">Cerrar sesión</button></form>';
        $t = e($titulo);
        $logo = self::LOGO;
        return <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$t} · EduNex Systems</title>
<link rel="stylesheet" href="?r=estilos">
</head>
<body>
<header class="top"><div class="wrap">
 <div class="bar"><a class="marca" href="?r=inicio">{$logo}<span><b>EduNex Systems</b><small>Registro al examen de ubicación</small></span></a>{$sesion}</div>
 <nav aria-label="Pantallas">{$nav}</nav>
</div></header>
<main class="wrap">
{$cuerpo}
</main>
<footer><div class="wrap">EduNex Systems · Trámites escolares que fluyen, sin papeles ni filas. Prototipo escolar (SCC-1007) con <b>datos simulados</b>.</div></footer>
</body>
</html>
HTML;
    }

    public static function csrf(): string
    {
        return '<input type="hidden" name="csrf" value="' . e(Seguridad::tokenCsrf()) . '">';
    }

    private static function opciones(array $lista, string $sel, string $vacia): string
    {
        $o = '<option value="">' . e($vacia) . '</option>';
        foreach ($lista as $x) {
            $o .= '<option' . ($x === $sel ? ' selected' : '') . '>' . e($x) . '</option>';
        }
        return $o;
    }

    private static function mensaje(?array $m): string
    {
        if (!$m) {
            return '';
        }
        $cls = ['ok' => 'okmsg', 'mal' => 'err', 'aviso' => 'aviso'][$m[0]] ?? 'aviso';
        return '<div class="' . $cls . '" role="status">' . e($m[1]) . '</div>';
    }

    private static function pill(string $s): string
    {
        return '<span class="pill ' . ($s === 'Registrado' ? 'p-reg' : 'p-pag') . '">' . e($s) . '</span>';
    }

    /* ------------------------------------------------------------------ pantallas públicas */

    public static function inicio(?string $rol): string
    {
        return <<<HTML
<div class="hero"><p>Coordinación de Lenguas Extranjeras</p><h1>Examen de ubicación</h1><p>Regístrate, paga y consulta tu nivel desde tu celular, sin filas ni papeles.</p></div>
<h2>Para aspirantes</h2>
<ul class="tiles"><li><a class="tile" href="?r=registro">Regístrate al examen</a></li><li><a class="tile" href="?r=consulta">Consulta tu estatus y nivel</a></li><li><a class="tile" href="?r=ayuda">Cómo registrarte y pagar</a></li></ul>
<h2>Coordinación y caja <span class="nota">(requiere usuario y contraseña)</span></h2>
<ul class="tiles"><li><a class="tile sta" href="?r=panel">Panel de la Coordinación</a></li><li><a class="tile sta" href="?r=caja">Reporte para caja</a></li><li><a class="tile sta" href="?r=pruebas">Casos de prueba</a></li></ul>
<p class="nota"><span class="sim">Datos simulados</span> Prototipo escolar. Nada de lo que escribas aquí es real.</p>
HTML;
    }

    public static function ayuda(): string
    {
        return <<<HTML
<div class="angosto"><h1>Ayuda</h1>
<div class="card"><h3>¿Cómo me registro?</h3><p>Entra a «Registro», llena 4 datos (número de control, nombre, carrera y grupo) y guarda tu referencia de pago y tu código de consulta.</p></div>
<div class="card"><h3>¿Cómo pago?</h3><p>Paga $500 en banco o en caja con tu referencia, dentro de la ventana de pago. No necesitas entregar comprobante. El pago es solo para el examen de este semestre, no es transferible y no hay fecha extraordinaria.</p></div>
<div class="card"><h3>¿Cómo veo mi estatus y mi nivel?</h3><p>En «Consulta de estatus» escribe tu número de control y tu código de consulta. Solo verás tu estatus y tu nivel.</p></div>
<div class="card"><h3>Perdí mi código</h3><p>Acude a la Coordinación de Lenguas Extranjeras con tu número de control.</p></div>
<a class="btn" href="?r=registro">Ir al registro</a></div>
HTML;
    }

    public static function registro(array $st, ?string $rol, array $errores, array $viejo, ?array $hecho): string
    {
        $c = $st['conv'];
        $cierre = e(Nucleo::fechaTexto($c['cierre']));
        $pago = e(Nucleo::fechaTexto($c['payFrom']) . ' al ' . Nucleo::fechaTexto($c['payTo']));
        $exam = e(Nucleo::fechaTexto($c['exam']));
        if ($hecho) {
            $ref = e($hecho['ref']);
            $con = e($hecho['con']);
            $code = e($hecho['code']);
            $ctl = e($hecho['c']);
            return <<<HTML
<div class="angosto"><span class="sim">Datos simulados</span>
<h1>Listo, quedaste registrado</h1>
<p>Paga $500 con esta referencia dentro de la ventana de pago. No necesitas entregar comprobante: el pago se cruza solo.</p>
<div class="card"><div class="kv"><span>Referencia de pago</span><b>{$ref}</b></div><div class="kv"><span>Concepto</span><b>{$con}</b></div><div class="kv"><span>Monto</span><b>$500.00</b></div><div class="kv"><span>Ventana de pago</span><b>{$pago}</b></div></div>
<div class="card"><b>Tu código de consulta</b><div class="grande" id="codigo">{$code}</div><p class="nota">Anótalo o tómale captura. Con tu número de control ({$ctl}) y este código ves tu estatus y tu nivel sin ir a la oficina.</p></div>
<a class="btn full" href="?r=consulta&amp;c={$ctl}">Consultar mi estatus</a>
<a class="btn alt full" href="?r=registro">Registrar a otra persona</a></div>
HTML;
        }
        $err = static fn (string $k) => isset($errores[$k]) ? '<div class="err" role="alert">' . e($errores[$k]) . '</div>' : '';
        $cerrado = strcmp(Nucleo::hoy($st), $c['cierre']) > 0
            ? '<div class="err" role="alert">El registro cerró el ' . $cierre . '. Ya no se aceptan más registros.</div>' : $err('general');
        $vc = e($viejo['control'] ?? '');
        $vn = e($viejo['nombre'] ?? '');
        $car = self::opciones(Nucleo::CARRERAS, $viejo['carrera'] ?? '', 'Elige tu carrera');
        $gru = self::opciones(Nucleo::GRUPOS, $viejo['grupo'] ?? '', 'Elige tu grupo');
        $asis = $rol === 'coordinador'
            ? '<div class="field check"><input type="checkbox" id="asistido" name="asistido" value="1"><label for="asistido">Registro asistido en la oficina (solo Coordinación)</label></div>' : '';
        $csrf = self::csrf();
        $eC = $err('control');
        $eN = $err('nombre');
        $eCa = $err('carrera');
        $eG = $err('grupo');
        return <<<HTML
<div class="angosto"><span class="sim">Datos simulados</span>
<h1>Regístrate al examen de ubicación</h1>
<div class="card" id="instr"><h3>Cómo registrarte y pagar</h3>
<ol><li>Llena los 4 datos de abajo.</li><li>Anota tu referencia de pago y tu código de consulta.</li><li>Paga $500 en banco o en caja con esa referencia.</li><li>Consulta tu estatus cuando quieras, sin ir a la oficina.</li></ol>
<div class="kv"><span>Registro hasta</span><b>{$cierre}</b></div><div class="kv"><span>Pago</span><b>{$pago}</b></div><div class="kv"><span>Examen</span><b>{$exam}</b></div>
<p class="aviso"><b>Importante:</b> el pago es solo para el examen de este semestre, no es transferible y no hay fecha extraordinaria.</p></div>
{$cerrado}
<form id="f" method="post" action="?r=registrar" novalidate>{$csrf}
<div class="field"><label for="control">Número de control</label><input id="control" name="control" inputmode="numeric" maxlength="8" autocomplete="off" placeholder="Ej. 26249001" value="{$vc}">{$eC}</div>
<div class="field"><label for="nombre">Nombre completo</label><input id="nombre" name="nombre" maxlength="80" autocomplete="name" placeholder="Nombre y apellidos" value="{$vn}">{$eN}</div>
<div class="field"><label for="carrera">Carrera</label><select id="carrera" name="carrera">{$car}</select>{$eCa}</div>
<div class="field"><label for="grupo">Grupo</label><select id="grupo" name="grupo">{$gru}</select>{$eG}</div>
{$asis}
<button class="btn full" type="submit">Registrarme</button>
</form></div>
HTML;
    }

    public static function consulta(array $st, ?array $res, ?string $error, string $control): string
    {
        $out = '';
        if ($res) {
            $hint = $res['nivel'] !== '' ? 'Ya puedes consultar tu nivel.'
                : ($res['estatus'] === 'Pagado' ? 'Tu pago está confirmado. El nivel aparece después del examen.'
                    : 'Aún no vemos tu pago. Paga con tu referencia dentro de la ventana de pago.');
            $out = '<div class="card" id="resultado"><div class="kv"><span>Estatus</span>' . self::pill($res['estatus'])
                . '</div><div class="kv"><span>Tu nivel</span><b>' . e($res['nivel'] ?: 'Aún no disponible') . '</b></div><p class="nota">' . e($hint) . '</p></div>';
        }
        $err = $error ? '<div class="err" role="alert">' . e($error) . '</div>' : '';
        $ej = array_slice($st['regs'], 0, 2);
        $demo = $ej ? '<p class="nota"><b>Para probar:</b> ' . implode(', o ', array_map(static fn ($r) => 'control <code>' . e($r['c']) . '</code> con código <code>' . e($r['code']) . '</code>', $ej)) . '.</p>' : '';
        $csrf = self::csrf();
        $vc = e($control);
        return <<<HTML
<div class="angosto"><span class="sim">Datos simulados</span>
<h1>Consulta tu estatus</h1>
<p>Escribe tu número de control y el código que te dimos al registrarte.</p>
<form id="q" method="post" action="?r=consultar" novalidate>{$csrf}
<div class="field"><label for="qc">Número de control</label><input id="qc" name="control" inputmode="numeric" maxlength="8" autocomplete="off" value="{$vc}"></div>
<div class="field"><label for="qk">Código de consulta</label><input id="qk" name="codigo" maxlength="4" autocomplete="off" autocapitalize="characters"></div>
{$err}
<button class="btn full" type="submit">Ver mi estatus</button>
</form>
{$out}{$demo}</div>
HTML;
    }

    public static function acceso(?string $error, string $motivo): string
    {
        $err = $error ? '<div class="err" role="alert">' . e($error) . '</div>' : '';
        $csrf = self::csrf();
        $m = e($motivo);
        return <<<HTML
<div class="angosto"><h1>Acceso de Coordinación y caja</h1><p class="nota">{$m}</p>
<form method="post" action="?r=entrar" novalidate>{$csrf}
<div class="field"><label for="lu">Usuario</label><input id="lu" name="usuario" autocomplete="username" autocapitalize="none"></div>
<div class="field"><label for="lp">Contraseña</label><input id="lp" name="clave" type="password" autocomplete="current-password"></div>
{$err}<button class="btn full" type="submit">Entrar</button></form>
<div class="card mt"><b>Para probar el prototipo:</b><br>Coordinación: usuario <code>coordinador</code>, contraseña <code>edunex2026</code>.<br>Caja: usuario <code>caja</code>, contraseña <code>caja2026</code>.<br><span class="nota">Son claves de demostración. El sistema real usaría la cuenta institucional del Tec.</span></div></div>
HTML;
    }

    /* ------------------------------------------------------------------ panel */

    private static function kpis(array $st): string
    {
        $s = Nucleo::estadisticas($st);
        $h = '<div class="kpis">';
        foreach ([[$s['registrados'], 'Registrados'], [$s['pagados'], 'Pagados'], [$s['pendientes'], 'Pendientes de pago'],
                     [$s['asistentes'], 'Con resultado'], [$s['excepciones'], 'Excepciones']] as [$n, $t]) {
            $h .= '<div class="kpi"><b>' . (int) $n . '</b><span>' . e($t) . '</span></div>';
        }
        return $h . '</div>';
    }

    public static function panel(array $st, array $f, array $flash, ?array $prev, string $urlBase): string
    {
        $csrf = self::csrf();
        $s = Nucleo::estadisticas($st);
        $niv = [];
        foreach (Nucleo::NIVELES as $n) {
            if (!empty($s['niveles'][$n])) {
                $niv[] = $n . ' ' . $s['niveles'][$n];
            }
        }
        $nivTxt = $niv ? implode(' · ', $niv) : 'aún sin resultados';
        $hoy = e(Nucleo::fechaTexto(Nucleo::hoy($st)) . ($st['hoy'] !== '' ? ' (fecha simulada)' : ''));

        // Tabla de aspirantes con búsqueda y filtros (RF-20). Se envía por GET: se puede guardar el enlace.
        $lista = Nucleo::filtrar($st, $f);
        $filas = '';
        foreach (array_slice($lista, 0, 200) as $r) {
            $filas .= '<tr><td>' . e($r['c']) . '</td><td>' . e($r['n']) . ($r['asistido'] ? ' <span class="nota">(asistido)</span>' : '')
                . '</td><td>' . e($r['ca']) . '</td><td>' . e($r['g']) . '</td><td>' . self::pill(Nucleo::estatusDe($r)) . '</td><td>' . e($r['pub'] ? $r['niv'] : '—') . '</td></tr>';
        }
        if ($filas === '') {
            $filas = '<tr><td colspan="6">Ningún aspirante coincide con la búsqueda.</td></tr>';
        }
        if (count($lista) > 200) {
            $filas .= '<tr><td colspan="6" class="nota">Se muestran los primeros 200. Usa la búsqueda para acotar.</td></tr>';
        }
        $cuenta = count($lista) . ' de ' . count($st['regs']);
        $fq = e($f['q'] ?? '');
        $fEst = self::opciones(['Registrado', 'Pagado', 'Nivel disponible'], $f['estatus'] ?? '', 'Todos');
        $fCar = self::opciones(Nucleo::CARRERAS, $f['carrera'] ?? '', 'Todas');
        $fGru = self::opciones(Nucleo::GRUPOS, $f['grupo'] ?? '', 'Todos');

        $avisos = '';
        foreach (array_slice($st['avisos'], 0, 8) as $a) {
            $tipo = ['recordatorio' => 'Recordatorio', 'pago' => 'Confirmación de pago', 'registro' => 'Confirmación de registro'][$a['tipo']] ?? $a['tipo'];
            $avisos .= '<li><b>' . e($tipo) . '</b> · para ' . e($a['para']) . ' · ' . e($a['fecha']) . '<br>' . e($a['texto']) . '</li>';
        }
        $avisos = $avisos ?: '<li>Sin avisos todavía.</li>';

        // Excepciones (RF-08)
        $pend = array_values(array_filter($st['regs'], static fn ($r) => $r['st'] !== 'Pagado'));
        $opPend = '<option value="">Elige aspirante</option>';
        foreach (array_slice($pend, 0, 300) as $r) {
            $opPend .= '<option value="' . e($r['c']) . '">' . e($r['c'] . ' · ' . $r['n']) . '</option>';
        }
        $exc = '';
        foreach ($st['exc'] as $x) {
            $exc .= '<div class="card"><b>' . e($x['tipo']) . '</b><div class="nota">' . e($x['detalle']) . '</div>';
            if ($x['kind'] === 'pago') {
                $exc .= '<form method="post" action="?r=asociar" class="fila">' . $csrf . '<input type="hidden" name="id" value="' . (int) $x['id'] . '">'
                    . '<select name="control" aria-label="Aspirante a asociar">' . $opPend . '</select><button class="btn" type="submit">Asociar pago</button></form>';
            }
            $exc .= '</div>';
        }
        $exc = $exc ?: '<p class="nota">Sin excepciones por ahora.</p>';

        $c = $st['conv'];
        $conv = e(Nucleo::textoConvocatoria($st, $urlBase));
        $rem = Nucleo::recordatorios($st);
        $remTxt = e('Se arma la lista de quien no ha pagado 24 horas antes del cierre del pago (' . Nucleo::fechaTexto($rem['fecha']) . '). Pendientes ahora: ' . count($rem['pendientes']) . '.');
        $confirmar = $prev ? '<form method="post" action="?r=publicar">' . $csrf . '<button class="btn" type="submit">Confirmar y publicar ' . count($prev['asociados']) . ' niveles</button></form>' : '';
        $m = static fn (string $k) => self::mensaje($flash[$k] ?? null);
        $kpis = self::kpis($st);
        [$mFechas, $mPagos, $mRes, $mRem, $mDatos, $mExc] = [$m('fechas'), $m('pagos'), $m('resultados'), $m('recordatorio'), $m('datos'), $m('excepciones')];
        $hoyVal = e($st['hoy']);
        return <<<HTML
<h1>Panel del Coordinador</h1>
<p class="nota">Convocatoria enero–junio 2027. <span class="sim">Datos simulados</span> Hoy: <b>{$hoy}</b></p>
{$kpis}
<p class="nota">Niveles: {$nivTxt}</p>
<div class="cols">
<div>
 <h2>Aspirantes <span class="nota">({$cuenta})</span></h2>
 <form method="get" class="filtros"><input type="hidden" name="r" value="panel">
  <div class="field"><label for="q">Buscar por control o nombre</label><input id="q" name="q" value="{$fq}" placeholder="Ej. 2624010"></div>
  <div class="field"><label for="fe">Estatus</label><select id="fe" name="estatus">{$fEst}</select></div>
  <div class="field"><label for="fc">Carrera</label><select id="fc" name="carrera">{$fCar}</select></div>
  <div class="field"><label for="fg">Grupo</label><select id="fg" name="grupo">{$fGru}</select></div>
  <div class="field"><button class="btn" type="submit">Buscar</button></div>
 </form>
 <div class="tabla"><table><thead><tr><th>Control</th><th>Nombre</th><th>Carrera</th><th>Grupo</th><th>Estatus</th><th>Nivel</th></tr></thead><tbody>{$filas}</tbody></table></div>
 <div class="fila"><a class="btn alt" href="?r=descargar&amp;que=pagados">Descargar lista del examen (pagados)</a><a class="btn alt" href="?r=descargar&amp;que=nivel">Descargar lista por nivel</a></div>
 <div class="card mt"><h3>Bandeja de avisos (simulada)</h3><p class="nota">Lo que el sistema enviaría. Los avisos a aspirantes se muestran en pantalla; los recordatorios van a la Coordinación por Teams.</p><ul>{$avisos}</ul></div>
</div>
<div>
 <div class="card"><h3>1. Fechas de la convocatoria</h3><p class="nota">Se capturan una sola vez y alimentan validaciones, avisos y listas.</p>
  <form method="post" action="?r=fechas">{$csrf}
   <div class="field"><label for="d1">Cierre de registro</label><input id="d1" name="cierre" type="date" value="{$c['cierre']}"></div>
   <div class="field"><label for="d2">Pago, del</label><input id="d2" name="payFrom" type="date" value="{$c['payFrom']}"></div>
   <div class="field"><label for="d3">Pago, al</label><input id="d3" name="payTo" type="date" value="{$c['payTo']}"></div>
   <div class="field"><label for="d4">Examen</label><input id="d4" name="exam" type="date" value="{$c['exam']}"></div>
   <div class="field"><label for="d0">Hoy (simulado, para probar cierres; vacío = fecha real)</label><input id="d0" name="hoy" type="date" value="{$hoyVal}"></div>
   <button class="btn" type="submit">Guardar fechas</button></form>{$mFechas}</div>
 <div class="card"><h3>2. Convocatoria para compartir</h3><p class="nota">Texto listo para Facebook, avisos o Teams. Selecciónalo y cópialo.</p><label for="conv">Texto</label><textarea id="conv" rows="5" readonly>{$conv}</textarea></div>
 <div class="card"><h3>3. Cruce de pagos</h3><p class="nota">Carga el reporte de pagos (CSV con referencia, monto y fecha).</p>
  <form method="post" action="?r=pagos" enctype="multipart/form-data">{$csrf}<div class="field"><label for="fp">Archivo de pagos</label><input id="fp" name="archivo" type="file" accept=".csv,text/csv"></div>
   <div class="fila"><button class="btn" type="submit" name="modo" value="archivo">Cruzar archivo</button><button class="btn alt" type="submit" name="modo" value="ejemplo">Usar reporte de ejemplo</button></div></form>{$mPagos}</div>
 <div class="card"><h3>4. Excepciones</h3>{$mExc}{$exc}</div>
 <div class="card"><h3>5. Resultado del examen</h3><p class="nota">Carga el archivo de la plataforma externa (CSV con control y nivel). Revisas el resumen y confirmas.</p>
  <form method="post" action="?r=resultados" enctype="multipart/form-data">{$csrf}<div class="field"><label for="fr">Archivo de resultados</label><input id="fr" name="archivo" type="file" accept=".csv,text/csv"></div>
   <div class="fila"><button class="btn" type="submit" name="modo" value="archivo">Revisar archivo</button><button class="btn alt" type="submit" name="modo" value="ejemplo">Usar resultado de ejemplo</button></div></form>{$mRes}{$confirmar}</div>
 <div class="card"><h3>6. Recordatorio de pago</h3><p class="nota">{$remTxt}</p>
  <div class="fila"><form method="post" action="?r=recordatorio">{$csrf}<button class="btn" type="submit">Enviar lista a la Coordinación (simulado)</button></form><a class="btn alt" href="?r=descargar&amp;que=pendientes">Descargar lista</a></div>{$mRem}</div>
 <div class="card"><h3>7. Datos de prueba</h3><p class="nota">Reinicia la base simulada o mide tiempos (RNF-07).</p>
  <form method="post" action="?r=datos" class="fila">{$csrf}<button class="btn alt" name="n" value="60">Reiniciar con 60</button><button class="btn alt" name="n" value="600">Reiniciar con 600</button><button class="btn alt" name="n" value="0">Dejar vacío</button><button class="btn alt" name="n" value="medir">Medir tiempos</button></form>{$mDatos}</div>
</div>
</div>
HTML;
    }

    public static function caja(array $st): string
    {
        $filas = '';
        $rows = Nucleo::reporteCaja($st);
        foreach (array_slice($rows, 0, 300) as $r) {
            $filas .= '<tr><td>' . e($r['referencia']) . '</td><td>' . e($r['concepto']) . '</td><td>' . e($r['control']) . '</td><td>' . e($r['nombre'])
                . '</td><td>$' . (int) $r['monto'] . '.00</td><td>' . self::pill($r['estatus']) . '</td></tr>';
        }
        if (count($rows) > 300) {
            $filas .= '<tr><td colspan="6" class="nota">Se muestran 300 de ' . count($rows) . '. El archivo descargable trae todos.</td></tr>';
        }
        $filas = $filas ?: '<tr><td colspan="6">No hay registros.</td></tr>';
        $kpis = self::kpis($st);
        return <<<HTML
<h1>Reporte para caja</h1><p class="nota">Registrados con su estatus de pago. Coincide con la base del panel. <span class="sim">Datos simulados</span></p>
<p><a class="btn alt" href="?r=descargar&amp;que=caja">Descargar hoja de cálculo (CSV)</a></p>
{$kpis}
<div class="tabla"><table><thead><tr><th>Referencia</th><th>Concepto</th><th>Control</th><th>Nombre</th><th>Monto</th><th>Estatus de pago</th></tr></thead><tbody>{$filas}</tbody></table></div>
HTML;
    }

    public static function auditoria(array $eventos): string
    {
        $filas = '';
        foreach ($eventos as $x) {
            $filas .= '<tr><td>' . e($x['fecha']) . '</td><td>' . e($x['usuario']) . '</td><td>' . e($x['rol']) . '</td><td>' . e($x['accion'])
                . '</td><td>' . e($x['resultado']) . '</td><td>' . e($x['detalle']) . '</td><td>' . e($x['ip']) . '</td><td>' . e($x['https']) . '</td><td class="nota">' . e($x['navegador']) . '</td></tr>';
        }
        $filas = $filas ?: '<tr><td colspan="9">Sin eventos todavía.</td></tr>';
        return <<<HTML
<h1>Bitácora de auditoría</h1>
<p class="nota">Quién entró, qué hizo, cuándo y desde dónde (IP y navegador). Se muestran los últimos 200 eventos. No se guardan contraseñas ni códigos de consulta.</p>
<p><a class="btn alt" href="?r=descargar&amp;que=auditoria">Descargar bitácora (CSV)</a></p>
<div class="tabla"><table><thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Rol</th><th>Acción</th><th>Resultado</th><th>Detalle</th><th>IP</th><th>HTTPS</th><th>Navegador</th></tr></thead><tbody>{$filas}</tbody></table></div>
HTML;
    }

    public static function pruebas(array $res, float $ms): string
    {
        $p = $f = $n = 0;
        $filas = '';
        foreach ($res as $x) {
            $est = $x['estado'];
            $cls = ['Pasa' => 'pasa', 'Falla' => 'falla'][$est] ?? 'pend';
            $p += $est === 'Pasa' ? 1 : 0;
            $f += $est === 'Falla' ? 1 : 0;
            $n += ($est !== 'Pasa' && $est !== 'Falla') ? 1 : 0;
            $filas .= '<tr><td>' . e($x['cp']) . '</td><td>' . e($x['req']) . '</td><td>' . e($x['que']) . '</td><td>' . e($x['esp']) . '</td><td>' . e($x['obtenido']) . '</td><td class="' . $cls . '">' . e($est) . '</td></tr>';
        }
        $tot = count($res);
        $t = number_format($ms, 0);
        $fecha = date('Y-m-d H:i');
        return <<<HTML
<h1>Casos de prueba CP-01 a CP-29</h1>
<p>Ejecutados ahora ({$fecha}) en este servidor, sobre una copia de datos de prueba: no cambian los datos del panel. Tardaron {$t} ms.</p>
<p class="okmsg">{$tot} casos: {$p} pasan, {$f} fallan, {$n} dependen de personas o de medición en el servidor real.</p>
<p><a class="btn alt" href="?r=descargar&amp;que=pruebas">Descargar resultados (CSV)</a></p>
<div class="tabla"><table><thead><tr><th>Caso</th><th>Requisito</th><th>Qué se prueba</th><th>Resultado esperado</th><th>Resultado obtenido</th><th>Estado</th></tr></thead><tbody>{$filas}</tbody></table></div>
HTML;
    }

    public static function noEncontrado(): string
    {
        return '<div class="angosto"><h1>No encontramos esa página</h1><a class="btn" href="?r=inicio">Ir al inicio</a></div>';
    }
}
