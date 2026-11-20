<?php
declare(strict_types=1);

/*
 * Vistas: arman la página que se envía al navegador.
 *
 * El navegador solo entiende HTML y CSS para dibujar una página; eso no cambia con ningún
 * lenguaje. Lo que cambia es QUIÉN lo escribe: aquí lo genera PHP en el servidor. No hay
 * archivos .html ni JavaScript: todos los botones son enlaces o formularios que regresan al servidor.
 *
 * Diseño: portal institucional (banner con título y botones redondos con ícono y texto),
 * inspirado en portales de cursos como «Capacítate para el empleo».
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
        ['ok', 'okFondo'], ['mal', 'malFondo'], ['azul', 'blanco'], ['azul', 'gris'], ['azul', 'ambar'],
    ];

    /** Nombres de archivo que se buscan en public/ para el escudo del Tec. */
    private const ESCUDOS = ['escudo-tec-matehuala.png', 'escudo-tec-matehuala.jpg', 'escudo-tec-matehuala.svg',
        'escudo-tec-matehuala.webp', 'logo-tecnm.png', 'logo-tecnm.jpg', 'logo-tecnm.svg'];

    public static function archivoEscudo(): ?string
    {
        foreach (self::ESCUDOS as $f) {
            if (is_file(dirname(__DIR__) . '/public/' . $f)) {
                return $f;
            }
        }
        return null;
    }

    public static function css(): string
    {
        $c = self::COLOR;
        return <<<CSS
*{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{margin:0;background:{$c['gris']};color:{$c['texto']};font:17px/1.55 Calibri,"Segoe UI",system-ui,sans-serif}
h1,h2,h3{font-family:Poppins,"Segoe UI",system-ui,sans-serif;color:{$c['azul']};line-height:1.25}
h1{font-size:1.7rem;margin:.2em 0 .4em}h2{font-size:1.3rem;margin:1.4em 0 .6em}h3{font-size:1.08rem;margin:0 0 .4em}
a{color:{$c['verdeTexto']}}
.wrap{max-width:1180px;margin:0 auto;padding:0 16px}
/* ---------- encabezado institucional ---------- */
.inst{background:{$c['azul']};color:{$c['blanco']}}
.inst .wrap{display:flex;flex-wrap:wrap;gap:10px 18px;align-items:center;justify-content:space-between;padding-top:10px;padding-bottom:10px}
.marca-tec{display:flex;align-items:center;gap:12px;color:{$c['blanco']};text-decoration:none}
.escudo-caja{background:{$c['blanco']};border-radius:10px;padding:4px;display:flex;align-items:center;justify-content:center;min-width:52px;min-height:52px}
.escudo{height:48px;width:auto;display:block}
.escudo-txt{font:700 .62rem Poppins,system-ui,sans-serif;color:{$c['azul']};text-align:center;line-height:1.1;padding:2px 4px}
.marca-tec b{display:block;font:600 1.02rem Poppins,system-ui,sans-serif}
.marca-tec small{display:block;font-size:.9rem;color:{$c['blanco']}}
.sesion{display:flex;gap:8px;align-items:center;color:{$c['blanco']};font-size:.95rem}
.sesion .btn{background:{$c['blanco']};color:{$c['azul']}}
nav.menu{background:{$c['blanco']};border-bottom:1px solid {$c['linea']}}
nav.menu .wrap{display:flex;flex-wrap:wrap;gap:6px;padding-top:8px;padding-bottom:8px}
nav.menu a{display:inline-flex;align-items:center;min-height:44px;padding:8px 14px;border-radius:999px;text-decoration:none;color:{$c['azul']};font-weight:600}
nav.menu a[aria-current=page]{background:{$c['verde']};color:{$c['blanco']}}
nav.menu .staff{margin-left:auto}
/* ---------- botones y formularios ---------- */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:46px;padding:10px 18px;border-radius:999px;border:0;background:{$c['verde']};color:{$c['blanco']};font:700 16px Calibri,system-ui,sans-serif;text-decoration:none;cursor:pointer}
.btn.alt{background:{$c['blanco']};color:{$c['verdeTexto']};border:2px solid {$c['verde']}}
.btn.azul{background:{$c['azul']}}
.btn.chico{min-height:40px;padding:6px 14px;font-size:15px}
.btn.full{width:100%;margin-top:10px}
a:focus-visible,.btn:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible{outline:3px solid {$c['ambar']};outline-offset:2px}
main{padding:20px 0 48px}
.field{margin:14px 0}
label{display:block;font-weight:700;margin-bottom:4px}
.ayuda{display:block;color:{$c['tenue']};font-size:.92rem;margin-top:4px}
input,select,textarea{width:100%;min-height:48px;padding:10px 12px;font:17px Calibri,system-ui,sans-serif;border:1.5px solid {$c['tenue']};border-radius:12px;background:{$c['blanco']};color:{$c['texto']}}
input[type=checkbox]{width:auto;min-height:0}
input[type=file]{padding:8px}
.check{display:flex;gap:8px;align-items:center}
/* Sistemas no tiene grupo C: se oculta la opción cuando se elige esa carrera (el servidor también lo valida). */
#f:has(#carrera option[value="Ingeniería en Sistemas Computacionales"]:checked) #grupo option[value="C"]{display:none}
.err{color:{$c['mal']};background:{$c['malFondo']};border-radius:10px;padding:8px 12px;margin-top:6px;font-weight:700}
.okmsg{color:{$c['ok']};background:{$c['okFondo']};border-radius:10px;padding:10px 14px;font-weight:700;margin:10px 0}
.aviso{color:{$c['ambarTexto']};background:{$c['ambarFondo']};border-radius:10px;padding:10px 14px;margin:10px 0}
.sin-margen{margin-top:0}.mt{margin-top:16px}
.nota{color:{$c['tenue']};font-size:.95rem}
.sim{display:inline-block;background:{$c['ambarFondo']};color:{$c['ambarTexto']};border-radius:999px;padding:2px 10px;font-size:.85rem;font-weight:700}
.card{background:{$c['blanco']};border:1px solid {$c['linea']};border-radius:16px;padding:18px;margin:0 0 16px}
.angosto{max-width:560px;margin:0 auto}
.kv{display:flex;justify-content:space-between;gap:12px;border-bottom:1px dashed {$c['linea']};padding:8px 0}
.grande{font:700 2.4rem Poppins,system-ui,monospace;letter-spacing:.25em;color:{$c['azul']};text-align:center;background:{$c['gris']};border-radius:12px;padding:8px}
/* ---------- portada tipo portal ---------- */
.banner{background:{$c['azul']};color:{$c['blanco']};border-radius:20px;padding:30px 24px;display:grid;grid-template-columns:3fr 2fr;gap:20px;align-items:center}
.banner h1{color:{$c['blanco']};font-size:2.1rem;margin:0 0 .3em}
.banner p{margin:0 0 14px;font-size:1.12rem}
.banner .btn{background:{$c['ambar']};color:{$c['azul']};font-size:1.1rem;padding:12px 26px}
.fechas{display:grid;gap:10px}
.fecha{background:{$c['blanco']};color:{$c['azul']};border-radius:14px;padding:10px 14px}
.fecha span{display:block;color:{$c['tenue']};font-size:.9rem}
.fecha b{font:700 1.1rem Poppins,system-ui,sans-serif}
.acciones{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:18px;text-align:center}
.accion{display:flex;flex-direction:column;align-items:center;gap:10px;text-decoration:none;color:{$c['azul']};font:700 1.02rem Poppins,system-ui,sans-serif;padding:8px;border-radius:16px}
.disco{width:96px;height:96px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:{$c['verde']}}
.disco svg{width:48px;height:48px}
.disco.azul{background:{$c['azul']}}.disco.ambar{background:{$c['ambar']}}
.accion:hover .disco{transform:scale(1.05)}
.pasos{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px}
.paso{background:{$c['blanco']};border:1px solid {$c['linea']};border-radius:16px;padding:16px;display:flex;gap:12px;align-items:flex-start}
.num{flex:none;width:38px;height:38px;border-radius:50%;background:{$c['azul']};color:{$c['blanco']};display:flex;align-items:center;justify-content:center;font:700 1.05rem Poppins,system-ui,sans-serif}
.paso b{display:block;color:{$c['azul']}}
/* indicador de pasos del trámite */
.etapas{list-style:none;padding:0;margin:8px 0 18px;display:flex;gap:6px}
.etapas li{flex:1;text-align:center;font-size:.85rem;color:{$c['tenue']};border-top:6px solid {$c['linea']};padding-top:6px}
.etapas li.hecha{border-color:{$c['verde']};color:{$c['verdeTexto']};font-weight:700}
.etapas li.actual{border-color:{$c['azul']};color:{$c['azul']};font-weight:700}
/* ---------- panel ---------- */
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:14px}
.kpi{background:{$c['blanco']};border:1px solid {$c['linea']};border-radius:14px;padding:12px 14px}
.kpi b{display:block;font:700 1.8rem Poppins,system-ui,sans-serif;color:{$c['azul']}}
.subnav{display:flex;flex-wrap:wrap;gap:6px;margin:6px 0 16px}
.subnav a{background:{$c['blanco']};border:1px solid {$c['linea']};border-radius:999px;padding:6px 12px;text-decoration:none;color:{$c['azul']};font-weight:600;font-size:.95rem}
.cols{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.filtros{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:0 10px;align-items:end}
.fila{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.tabla{overflow-x:auto;background:{$c['blanco']};border:1px solid {$c['linea']};border-radius:14px;margin-bottom:12px}
.tabla.alta{max-height:520px;overflow-y:auto}
table{border-collapse:collapse;width:100%;font-size:.95rem}
th{background:{$c['azul']};color:{$c['blanco']};text-align:left;padding:9px;position:sticky;top:0}
td{padding:8px 9px;border-top:1px solid {$c['linea']};vertical-align:middle}
td form{display:flex;gap:6px;align-items:center;min-width:260px}
td select{min-height:40px;padding:6px 8px;font-size:15px}
.pill{display:inline-block;border-radius:999px;padding:2px 10px;font-weight:700;font-size:.85rem;white-space:nowrap}
.p-reg{background:{$c['ambarFondo']};color:{$c['ambarTexto']}}.p-pag{background:{$c['okFondo']};color:{$c['ok']}}.p-mal{background:{$c['malFondo']};color:{$c['mal']}}
.pasa{color:{$c['ok']};font-weight:700}.falla{color:{$c['mal']};font-weight:700}.pend{color:{$c['ambarTexto']};font-weight:700}
footer{background:{$c['blanco']};border-top:4px solid {$c['verde']};padding:18px 0;color:{$c['tenue']};font-size:.92rem}
footer .wrap{display:flex;flex-wrap:wrap;gap:10px 24px;align-items:center;justify-content:space-between}
.edunex{display:flex;gap:8px;align-items:center;color:{$c['azul']}}
.logo{width:30px;height:30px;flex:none}
/* ---------- modo wireframe (sección 10 del libro) ---------- */
body.wire,body.wire *{font-family:"DejaVu Sans Mono",Consolas,monospace!important;box-shadow:none!important}
body.wire{background:#FFFFFF;color:#222222}
body.wire h1,body.wire h2,body.wire h3,body.wire .accion,body.wire .kpi b,body.wire .grande,body.wire nav.menu a,body.wire .paso b,body.wire .fecha{color:#222222}
body.wire .inst,body.wire .banner,body.wire th,body.wire .btn,body.wire .disco,body.wire .num,body.wire nav.menu a[aria-current=page]{background:#555555;color:#FFFFFF;border-color:#555555}
body.wire .banner h1,body.wire .marca-tec,body.wire .marca-tec small{color:#FFFFFF}
body.wire .btn.alt{background:#FFFFFF;color:#222222;border:1.5px dashed #555555}
body.wire .card,body.wire input,body.wire select,body.wire textarea,body.wire .kpi,body.wire .tabla,body.wire .paso{border:1.5px dashed #555555;background:#FFFFFF}
body.wire .sim,body.wire .aviso,body.wire .pill,body.wire .okmsg,body.wire .err,body.wire .grande{background:#EEEEEE;color:#222222}
body.wire img,body.wire svg{filter:grayscale(1)}
/* ---------- tamaños de pantalla ---------- */
@media (max-width:860px){.cols,.banner{grid-template-columns:1fr}.banner h1{font-size:1.7rem}nav.menu .staff{margin-left:0}}
@media (max-width:480px){.wrap{padding:0 12px}.btn{width:100%}.disco{width:80px;height:80px}.disco svg{width:40px;height:40px}.acciones{grid-template-columns:repeat(2,1fr)}.grande{font-size:2rem}td,th{padding:6px}.etapas li{font-size:.75rem}}
CSS;
    }

    private const LOGO = '<svg class="logo" viewBox="0 0 40 40" aria-hidden="true"><path d="M9 31V9l22 22V9" fill="none" stroke="#12284C" stroke-width="3" stroke-linejoin="round"/><circle cx="9" cy="31" r="4" fill="#12284C"/><circle cx="9" cy="9" r="4" fill="#12284C"/><circle cx="31" cy="31" r="4" fill="#0B7F83"/><circle cx="31" cy="9" r="4.5" fill="#F5A623"/></svg>';

    /** Íconos de los botones redondos (dibujos simples, en blanco). */
    private const ICONO = [
        'registro' => '<rect x="12" y="8" width="24" height="32" rx="3"/><path d="M18 18h12M18 25h12M18 32h6"/>',
        'consulta' => '<circle cx="21" cy="21" r="9"/><path d="M28 28l10 10"/>',
        'pago' => '<rect x="8" y="14" width="32" height="22" rx="3"/><path d="M8 21h32M14 30h8"/>',
        'ayuda' => '<circle cx="24" cy="24" r="15"/><path d="M19 20a5 5 0 1 1 7 4.5c-1.5.8-2 1.8-2 3.5M24 34v.5"/>',
        'panel' => '<path d="M10 38V22M20 38V12M30 38V26M40 38V18"/>',
        'caja' => '<rect x="9" y="12" width="30" height="26" rx="3"/><path d="M15 20h18M15 27h18M15 34h10"/>',
        'bitacora' => '<circle cx="24" cy="24" r="15"/><path d="M24 15v10l6 4"/>',
        'pruebas' => '<path d="M12 14l3 3 5-6M12 26l3 3 5-6M12 38l3 3 5-6M26 15h12M26 27h12M26 39h12"/>',
    ];

    private static function icono(string $k): string
    {
        return '<svg viewBox="0 0 48 48" aria-hidden="true" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">' . self::ICONO[$k] . '</svg>';
    }

    private static function accion(string $r, string $texto, string $icono, string $color = ''): string
    {
        return '<li><a class="accion" href="?r=' . $r . '"><span class="disco ' . $color . '">' . self::icono($icono) . '</span>' . e($texto) . '</a></li>';
    }

    /** Estructura común de todas las páginas. */
    public static function pagina(string $titulo, string $vista, string $cuerpo, ?string $rol): string
    {
        $archivo = self::archivoEscudo();
        $escudo = $archivo
            ? '<img class="escudo" src="' . e($archivo) . '" alt="Escudo del Tecnológico Nacional de México, Campus Matehuala">'
            : '<span class="escudo-txt">TecNM<br>Matehuala</span>';
        $wire = !empty($_SESSION['wire']);
        $claseBody = $wire ? ' class="wire"' : '';
        $volver = e(in_array($vista, ['acceso'], true) ? 'inicio' : $vista);
        $botonWire = '<a href="?r=wire&amp;v=' . $volver . '">' . ($wire ? 'Ver con diseño' : 'Ver como wireframe') . '</a>';

        $menu = [['inicio', 'Inicio'], ['registro', 'Regístrate'], ['consulta', 'Consulta tu estatus'], ['ayuda', 'Ayuda']];
        $staff = [];
        if ($rol === 'coordinador') {
            $staff = [['panel', 'Panel'], ['caja', 'Reporte para caja'], ['auditoria', 'Bitácora']];
        } elseif ($rol === 'caja') {
            $staff = [['caja', 'Reporte para caja']];
        }
        $liga = static fn (array $x) => '<a href="?r=' . $x[0] . '"' . ($x[0] === $vista ? ' aria-current="page"' : '') . '>' . e($x[1]) . '</a>';
        $nav = implode('', array_map($liga, $menu));
        if ($staff) {
            $nav .= '<span class="staff"></span>' . implode('', array_map($liga, $staff));
        }
        $sesion = $rol === null
            ? '<a class="btn chico" href="?r=acceso">Personal: entrar</a>'
            : '<form method="post" action="?r=salir" class="sesion">' . self::csrf() . '<span>' . e(Seguridad::usuario()) . '</span><button class="btn chico" type="submit">Cerrar sesión</button></form>';
        $t = e($titulo);
        $logo = self::LOGO;
        return <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$t} · Examen de ubicación · TecNM Campus Matehuala</title>
<link rel="stylesheet" href="?r=estilos">
</head>
<body{$claseBody}>
<header>
 <div class="inst"><div class="wrap">
  <a class="marca-tec" href="?r=inicio"><span class="escudo-caja">{$escudo}</span><span><b>Tecnológico Nacional de México</b><small>Campus Matehuala · Coordinación de Lenguas Extranjeras</small></span></a>
  {$sesion}
 </div></div>
 <nav class="menu" aria-label="Menú principal"><div class="wrap">{$nav}</div></nav>
</header>
<main class="wrap">
{$cuerpo}
</main>
<footer><div class="wrap">
 <span class="edunex">{$logo}<span>Desarrollado por <b>EduNex Systems</b> · Trámites escolares que fluyen, sin papeles ni filas.</span></span>
 <span><span class="sim">Datos simulados</span> · <a href="?r=pruebas">Casos de prueba</a> · {$botonWire}</span>
</div></footer>
</body>
</html>
HTML;
    }

    public static function csrf(): string
    {
        return '<input type="hidden" name="csrf" value="' . e(Seguridad::tokenCsrf()) . '">';
    }

    private static function opciones(array $lista, string $sel, string $vacia, array $etiquetas = []): string
    {
        $o = '<option value="">' . e($vacia) . '</option>';
        foreach ($lista as $x) {
            $o .= '<option value="' . e($x) . '"' . ($x === $sel ? ' selected' : '') . '>' . e($etiquetas[$x] ?? $x) . '</option>';
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

    private static function etapas(int $actual, array $nombres): string
    {
        $h = '<ol class="etapas" aria-label="Pasos del trámite">';
        foreach ($nombres as $i => $n) {
            $cls = $i < $actual ? 'hecha' : ($i === $actual ? 'actual' : '');
            $h .= '<li class="' . $cls . '"' . ($i === $actual ? ' aria-current="step"' : '') . '>' . ($i + 1) . '. ' . e($n) . '</li>';
        }
        return $h . '</ol>';
    }

    /* ------------------------------------------------------------------ pantallas públicas */

    public static function inicio(array $st, ?string $rol): string
    {
        $c = $st['conv'];
        $f = static fn (string $k) => e(Nucleo::fechaTexto($c[$k]));
        $pago = e(Nucleo::fechaTexto($c['payFrom']) . ' al ' . Nucleo::fechaTexto($c['payTo']));
        $acc = self::accion('registro', 'Regístrate al examen', 'registro')
            . self::accion('consulta', 'Consulta tu estatus y nivel', 'consulta', 'azul')
            . self::accion('ayuda', 'Cómo pagar', 'pago', 'ambar')
            . self::accion('ayuda', 'Preguntas frecuentes', 'ayuda', 'azul');
        $staff = self::accion('panel', 'Panel de la Coordinación', 'panel', 'azul') . self::accion('caja', 'Reporte para caja', 'caja', 'azul');
        $cuota = Nucleo::CUOTA;
        return <<<HTML
<section class="banner">
 <div><p>Coordinación de Lenguas Extranjeras</p><h1>Examen de ubicación de lengua extranjera</h1>
 <p>Regístrate, paga y consulta tu nivel desde tu celular, sin filas ni papeles.</p>
 <a class="btn" href="?r=registro">Regístrate aquí</a></div>
 <div class="fechas" aria-label="Fechas importantes">
  <div class="fecha"><span>Registro hasta</span><b>{$f('cierre')}</b></div>
  <div class="fecha"><span>Pago de \${$cuota}</span><b>{$pago}</b></div>
  <div class="fecha"><span>Examen</span><b>{$f('exam')}</b></div>
 </div>
</section>
<h2>¿Qué quieres hacer?</h2>
<ul class="acciones">{$acc}</ul>
<h2>Así funciona</h2>
<ol class="pasos">
 <li class="paso"><span class="num">1</span><span><b>Regístrate</b>Solo 4 datos: número de control, nombre, carrera y grupo.</span></li>
 <li class="paso"><span class="num">2</span><span><b>Guarda tu referencia y tu código</b>Te los damos al terminar el registro.</span></li>
 <li class="paso"><span class="num">3</span><span><b>Paga \${$cuota}</b>En banco o en caja, con tu referencia. No entregas comprobante.</span></li>
 <li class="paso"><span class="num">4</span><span><b>Consulta tu estatus y tu nivel</b>Con tu número de control y tu código.</span></li>
</ol>
<h2>Personal del Tec <span class="nota">(con usuario y contraseña)</span></h2>
<ul class="acciones">{$staff}</ul>
HTML;
    }

    public static function ayuda(array $st): string
    {
        $c = $st['conv'];
        $pago = e(Nucleo::fechaTexto($c['payFrom']) . ' al ' . Nucleo::fechaTexto($c['payTo']));
        return <<<HTML
<div class="angosto"><h1>Ayuda</h1>
<div class="card"><h3>¿Cómo me registro?</h3><p>Entra a «Regístrate», llena 4 datos (número de control, nombre, carrera y grupo) y guarda tu referencia de pago y tu código de consulta.</p></div>
<div class="card"><h3>¿Cómo pago?</h3><p>Paga $500 en banco o en caja con tu referencia, del {$pago}. No necesitas entregar comprobante: el sistema cruza tu pago solo. El pago es solo para el examen de este semestre, no es transferible y no hay fecha extraordinaria.</p></div>
<div class="card"><h3>¿Cómo veo mi estatus y mi nivel?</h3><p>En «Consulta tu estatus» escribe tu número de control y tu código de consulta. Verás tu nombre, carrera, grupo, estatus y nivel.</p></div>
<div class="card"><h3>Mi carrera es Sistemas y no aparece el grupo C</h3><p>Ingeniería en Sistemas Computacionales solo tiene grupos A y B.</p></div>
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
        $pasos = ['Tus datos', 'Tu referencia', 'Paga', 'Consulta'];
        if ($hecho) {
            $et = self::etapas(1, $pasos);
            $ref = e($hecho['ref']);
            $con = e($hecho['con']);
            $code = e($hecho['code']);
            $ctl = e($hecho['c']);
            $nom = e($hecho['n']);
            return <<<HTML
<div class="angosto">{$et}
<h1>Listo, {$nom}: quedaste registrado</h1>
<p>Paga $500 con esta referencia dentro de la ventana de pago. No necesitas entregar comprobante: el pago se cruza solo.</p>
<div class="card"><div class="kv"><span>Referencia de pago</span><b>{$ref}</b></div><div class="kv"><span>Concepto</span><b>{$con}</b></div><div class="kv"><span>Monto</span><b>$500.00</b></div><div class="kv"><span>Ventana de pago</span><b>{$pago}</b></div></div>
<div class="card"><b>Tu código de consulta</b><div class="grande" id="codigo">{$code}</div><p class="nota">Anótalo o tómale captura. Con tu número de control ({$ctl}) y este código ves tu estatus y tu nivel sin ir a la oficina.</p></div>
<a class="btn full" href="?r=consulta&amp;c={$ctl}">Consultar mi estatus</a>
<a class="btn alt full" href="?r=registro">Registrar a otra persona</a></div>
HTML;
        }
        $et = self::etapas(0, $pasos);
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
        [$eC, $eN, $eCa, $eG] = [$err('control'), $err('nombre'), $err('carrera'), $err('grupo')];
        return <<<HTML
<div class="angosto">{$et}
<h1>Regístrate al examen de ubicación</h1>
<div class="card" id="instr"><h3>Antes de empezar</h3>
<ol><li>Llena los 4 datos de abajo.</li><li>Anota tu referencia de pago y tu código de consulta.</li><li>Paga $500 en banco o en caja con esa referencia.</li><li>Consulta tu estatus cuando quieras, sin ir a la oficina.</li></ol>
<div class="kv"><span>Registro hasta</span><b>{$cierre}</b></div><div class="kv"><span>Pago</span><b>{$pago}</b></div><div class="kv"><span>Examen</span><b>{$exam}</b></div>
<p class="aviso"><b>Importante:</b> el pago es solo para el examen de este semestre, no es transferible y no hay fecha extraordinaria.</p></div>
{$cerrado}
<form id="f" class="card" method="post" action="?r=registrar" novalidate>{$csrf}
<div class="field"><label for="control">Número de control</label><input id="control" name="control" inputmode="numeric" maxlength="8" autocomplete="off" placeholder="Ej. 26249001" value="{$vc}"><span class="ayuda">8 dígitos, sin espacios.</span>{$eC}</div>
<div class="field"><label for="nombre">Nombre completo</label><input id="nombre" name="nombre" maxlength="80" autocomplete="name" placeholder="Nombre y apellidos" value="{$vn}">{$eN}</div>
<div class="field"><label for="carrera">Carrera</label><select id="carrera" name="carrera">{$car}</select>{$eCa}</div>
<div class="field"><label for="grupo">Grupo</label><select id="grupo" name="grupo">{$gru}</select><span class="ayuda">Ingeniería en Sistemas Computacionales solo tiene grupos A y B.</span>{$eG}</div>
{$asis}
<button class="btn full" type="submit">Registrarme</button>
</form></div>
HTML;
    }

    public static function consulta(array $st, ?array $res, ?string $error, string $control): string
    {
        $out = '';
        if ($res) {
            $paso = $res['estatus'] === 'Registrado' ? 0 : ($res['estatus'] === 'Pagado' ? 1 : 2);
            $et = self::etapas($paso, ['Registrado', 'Pago confirmado', 'Nivel disponible']);
            $hint = $res['nivel'] !== '' ? 'Ya puedes consultar tu nivel.'
                : ($res['estatus'] === 'Pagado' ? 'Tu pago está confirmado. Ya estás en la lista del examen. El nivel aparece después del examen.'
                    : 'Aún no vemos tu pago. Paga con tu referencia dentro de la ventana de pago.');
            $out = '<div class="card" id="resultado"><h2 class="sin-margen">' . e($res['nombre']) . '</h2>' . $et
                . '<div class="kv"><span>Carrera</span><b>' . e($res['carrera']) . '</b></div>'
                . '<div class="kv"><span>Grupo</span><b>' . e($res['grupo']) . '</b></div>'
                . '<div class="kv"><span>Estatus</span>' . self::pill($res['estatus']) . '</div>'
                . '<div class="kv"><span>Tu nivel</span><b>' . e($res['nivel'] ?: 'Aún no disponible') . '</b></div><p class="nota">' . e($hint) . '</p></div>';
        }
        $err = $error ? '<div class="err" role="alert">' . e($error) . '</div>' : '';
        $ej = array_slice($st['regs'], 0, 2);
        $demo = $ej ? '<p class="nota"><b>Para probar:</b> ' . implode(', o ', array_map(static fn ($r) => 'control <code>' . e($r['c']) . '</code> con código <code>' . e($r['code']) . '</code>', $ej)) . '.</p>' : '';
        $csrf = self::csrf();
        $vc = e($control);
        return <<<HTML
<div class="angosto">
<h1>Consulta tu estatus</h1>
<p>Escribe tu número de control y el código que te dimos al registrarte.</p>
<form id="q" class="card" method="post" action="?r=consultar" novalidate>{$csrf}
<div class="field"><label for="qc">Número de control</label><input id="qc" name="control" inputmode="numeric" maxlength="8" autocomplete="off" value="{$vc}"></div>
<div class="field"><label for="qk">Código de consulta</label><input id="qk" name="codigo" maxlength="4" autocomplete="off" autocapitalize="characters"><span class="ayuda">4 letras o números, por ejemplo HX3K.</span></div>
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
<div class="angosto"><h1>Acceso del personal</h1><p class="nota">{$m}</p>
<form class="card" method="post" action="?r=entrar" novalidate>{$csrf}
<div class="field"><label for="lu">Usuario</label><input id="lu" name="usuario" autocomplete="username" autocapitalize="none"></div>
<div class="field"><label for="lp">Contraseña</label><input id="lp" name="clave" type="password" autocomplete="current-password"></div>
{$err}<button class="btn full" type="submit">Entrar</button></form>
<div class="card"><b>Para probar el prototipo:</b><br>Coordinación: usuario <code>coordinador</code>, contraseña <code>edunex2026</code>.<br>Caja: usuario <code>caja</code>, contraseña <code>caja2026</code>.<br><span class="nota">Son claves de demostración. El sistema real usaría la cuenta institucional del Tec.</span></div></div>
HTML;
    }

    /* ------------------------------------------------------------------ panel */

    private static function kpis(array $st): string
    {
        $s = Nucleo::estadisticas($st);
        $h = '<div class="kpis">';
        foreach ([[$s['registrados'], 'Registrados'], [$s['pagados'], 'Pagados'], [$s['pendientes'], 'Pendientes de pago'],
                     [$s['asistentes'], 'Con nivel'], [$s['excepciones'], 'Excepciones']] as [$n, $t]) {
            $h .= '<div class="kpi"><b>' . (int) $n . '</b><span>' . e($t) . '</span></div>';
        }
        return $h . '</div>';
    }

    /** Excepciones en forma de tabla: una fila por pago o resultado que el sistema no pudo asociar. */
    private static function tablaExcepciones(array $st, string $csrf): string
    {
        if (!$st['exc']) {
            return '<p class="nota">Sin excepciones por ahora.</p>';
        }
        $cuenta = [];
        foreach ($st['exc'] as $x) {
            $cuenta[$x['tipo']] = ($cuenta[$x['tipo']] ?? 0) + 1;
        }
        $resumen = implode(' · ', array_map(static fn ($k, $v) => e($k) . ': <b>' . $v . '</b>', array_keys($cuenta), $cuenta));
        $filas = '';
        foreach ($st['exc'] as $x) {
            if ($x['kind'] === 'pago') {
                $op = '<option value="">Elige aspirante</option>';
                foreach (Nucleo::sugeridos($st, $x) as $r) {
                    $op .= '<option value="' . e($r['c']) . '">' . ($r['sugerido'] ? '★ ' : '') . e($r['c'] . ' · ' . $r['n'] . ' · ref. ' . $r['ref']) . '</option>';
                }
                $accion = '<form method="post" action="?r=asociar">' . $csrf . '<input type="hidden" name="id" value="' . (int) $x['id'] . '">'
                    . '<select name="control" aria-label="Aspirante para el pago ' . e($x['ref'] ?: 'sin referencia') . '">' . $op . '</select>'
                    . '<button class="btn chico" type="submit">Asociar</button></form>';
                $filas .= '<tr><td><span class="pill p-mal">' . e($x['tipo']) . '</span></td><td>' . e($x['ref'] ?: '—') . '</td><td>$' . e($x['monto']) . '</td><td>' . e($x['fecha']) . '</td><td>' . $accion . '</td></tr>';
            } else {
                $filas .= '<tr><td><span class="pill p-mal">' . e($x['tipo']) . '</span></td><td colspan="3">' . e($x['detalle']) . '</td><td class="nota">Revisar en la plataforma del examen</td></tr>';
            }
        }
        return '<p class="nota">' . $resumen . '. ★ = aspirante con la misma referencia o una casi igual (solo cambia el último dígito).</p>'
            . '<div class="tabla alta"><table><thead><tr><th>Tipo</th><th>Referencia</th><th>Monto</th><th>Fecha</th><th>Asociar a aspirante</th></tr></thead><tbody>' . $filas . '</tbody></table></div>';
    }

    public static function panel(array $st, array $f, array $flash, ?array $prev, string $urlBase, ?array $diag = null): string
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
        $avisoEscudo = self::archivoEscudo() ? '' : '<div class="aviso"><b>Falta el escudo del Tec.</b> Copia el archivo <code>escudo-tec-matehuala.png</code> en la carpeta <code>public/</code> del proyecto.</div>';

        $lista = Nucleo::filtrar($st, $f);
        $filas = '';
        foreach (array_slice($lista, 0, 200) as $r) {
            $filas .= '<tr><td>' . e($r['c']) . '</td><td>' . e($r['n']) . ($r['asistido'] ? ' <span class="nota">(asistido)</span>' : '')
                . '</td><td>' . e($r['ca']) . '</td><td>' . e($r['g']) . '</td><td>' . e($r['ref']) . '</td><td>' . self::pill(Nucleo::estatusDe($r)) . '</td><td>' . e($r['pub'] ? $r['niv'] : '—') . '</td></tr>';
        }
        if ($filas === '') {
            $filas = '<tr><td colspan="7">Ningún aspirante coincide con la búsqueda.</td></tr>';
        }
        if (count($lista) > 200) {
            $filas .= '<tr><td colspan="7" class="nota">Se muestran los primeros 200. Usa la búsqueda para acotar.</td></tr>';
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

        // Conexión con Power Automate
        $paR = Nube::activa('registro');
        $paC = Nube::activa('consulta');
        $paX = Nube::activa('cruce');
        $con = Nube::conectado();
        $avisoDupl = !empty($st['duplicados']) ? '<div class="err">Hay números de control repetidos en el Excel: ' . e(implode(', ', $st['duplicados'])) . '. Revisa la sección 7.</div>' : '';
        $avisoDatos = $con ? '<div class="aviso">Con la conexión activa, «Reiniciar con 60/600» carga aspirantes <b>inventados</b> que no están en el Excel; «Traer registros del Excel» los quita.</div>' : '';
        $simulacion = $con ? '<div class="aviso"><b>Solo simulación:</b> esta sección cambia la copia del servidor, no el Excel. El cruce real lo hace el flujo C en Power Automate (sección 7).</div>' : '';
        $sOk = count(array_filter($st['regs'], static fn ($r) => ($r['sync'] ?? '') === 'ok'));
        $sErr = count(array_filter($st['regs'], static fn ($r) => ($r['sync'] ?? '') === 'error'));
        $estado = static fn (bool $b) => $b ? '<span class="pill p-pag">Conectado</span>' : '<span class="pill p-reg">Sin configurar</span>';
        $botonSync = $paR ? '<form method="post" action="?r=sincronizar">' . $csrf . '<button class="btn chico alt" type="submit">Reenviar registros pendientes</button></form>' : '';
        $botonProbar = $paC ? '<form method="post" action="?r=probarb">' . $csrf . '<button class="btn chico alt" type="submit">Probar conexión con el flujo B</button></form>' : '';
        $diagHtml = $diag ? '<div class="card mt"><b>Resultado de la prueba del flujo B</b><ul>' . implode('', array_map(static fn ($x) => '<li>' . e($x) . '</li>', $diag)) . '</ul></div>' : '';
        $botonTraer = $paC ? '<form method="post" action="?r=traerexcel">' . $csrf . '<button class="btn chico" type="submit">Traer registros del Excel</button></form>' : '';
        $botonCruce = $paX ? '<form method="post" action="?r=cruceexcel">' . $csrf . '<button class="btn chico" type="submit">Ejecutar cruce de pagos (flujo C)</button></form>' : '';
        $dupl = !empty($st['duplicados']) ? '<div class="err">En el Excel hay números de control repetidos: ' . e(implode(', ', $st['duplicados'])) . '. Borra en el Excel la fila que sobra y pulsa «Traer registros del Excel». Mientras tanto el panel muestra solo una de cada uno.</div>' : '';
        $ultima = !empty($st['excel']) ? '<div class="kv"><span>Datos traídos del Excel</span><b>' . e($st['excel']) . '</b></div>' : '';
        $nube = $dupl . '<div class="kv"><span>Flujo A · Registro → Excel</span>' . $estado($paR) . '</div><div class="kv"><span>Flujo B · Consulta ← Excel</span>' . $estado($paC) . '</div>'
            . '<div class="kv"><span>Flujo C · Cruce de pagos (botón del panel)</span>' . $estado($paX) . '</div>' . $ultima
            . ($con ? '<p class="nota">Con la conexión activa, el panel muestra lo que hay en la tabla Registros del Excel. Si cambias el Excel a mano o corres el flujo C desde Power Automate, pulsa «Traer registros del Excel».</p>' : '')
            . '<div class="fila">' . $botonTraer . $botonCruce . $botonProbar . '</div>' . $diagHtml
            . ($paR ? '<div class="kv"><span>Registros enviados al Excel</span><b>' . $sOk . ' bien · ' . $sErr . ' con error</b></div>' : '')
            . '<p class="nota">Las direcciones de los flujos se configuran en el servidor (variables EDUNEX_PA_REGISTRO y EDUNEX_PA_CONSULTA), nunca en el código. El cruce de pagos real lo hace el flujo C en Power Automate.</p>' . $botonSync;

        $c = $st['conv'];
        $conv = e(Nucleo::textoConvocatoria($st, $urlBase));
        $rem = Nucleo::recordatorios($st);
        $remTxt = e('Se arma la lista de quien no ha pagado 24 horas antes del cierre del pago (' . Nucleo::fechaTexto($rem['fecha']) . '). Pendientes ahora: ' . count($rem['pendientes']) . '.');
        $confirmar = $prev ? '<form method="post" action="?r=publicar">' . $csrf . '<button class="btn" type="submit">Confirmar y publicar ' . count($prev['asociados']) . ' niveles</button></form>' : '';
        $m = static fn (string $k) => self::mensaje($flash[$k] ?? null);
        [$mFechas, $mPagos, $mRes, $mRem, $mDatos, $mExc, $mNube] = [$m('fechas'), $m('pagos'), $m('resultados'), $m('recordatorio'), $m('datos'), $m('excepciones'), $m('nube')];
        $kpis = self::kpis($st);
        $exc = self::tablaExcepciones($st, $csrf);
        $hoyVal = e($st['hoy']);
        return <<<HTML
<h1>Panel de la Coordinación</h1>
<p class="nota">Convocatoria enero–junio 2027. <span class="sim">Datos simulados</span> Hoy: <b>{$hoy}</b> · Niveles: {$nivTxt}</p>
{$avisoEscudo}
{$avisoDupl}
{$kpis}
<nav class="subnav" aria-label="Secciones del panel"><a href="#pagos">Cruce de pagos</a><a href="#excepciones">Excepciones</a><a href="#resultados">Resultados</a><a href="#aspirantes">Aspirantes</a><a href="#fechas">Fechas</a><a href="#recordatorio">Recordatorio</a><a href="#nube">Power Automate</a><a href="#datos">Datos de prueba</a></nav>
<div class="cols">
 <section class="card" id="pagos"><h3>1. Cruce de pagos</h3>{$simulacion}<p class="nota">Carga el reporte de pagos (CSV con referencia, monto y fecha).</p>
  <form method="post" action="?r=pagos" enctype="multipart/form-data">{$csrf}<div class="field"><label for="fp">Archivo de pagos</label><input id="fp" name="archivo" type="file" accept=".csv,text/csv"></div>
   <div class="fila"><button class="btn" type="submit" name="modo" value="archivo">Cruzar archivo</button><button class="btn alt" type="submit" name="modo" value="ejemplo">Usar reporte de ejemplo</button></div></form>{$mPagos}</section>
 <section class="card" id="resultados"><h3>2. Resultado del examen</h3><p class="nota">Carga el archivo de la plataforma del examen (CSV con control y nivel). Revisas el resumen y confirmas.</p>
  <form method="post" action="?r=resultados" enctype="multipart/form-data">{$csrf}<div class="field"><label for="fr">Archivo de resultados</label><input id="fr" name="archivo" type="file" accept=".csv,text/csv"></div>
   <div class="fila"><button class="btn" type="submit" name="modo" value="archivo">Revisar archivo</button><button class="btn alt" type="submit" name="modo" value="ejemplo">Usar resultado de ejemplo</button></div></form>{$mRes}{$confirmar}</section>
</div>
<section class="card" id="excepciones"><h3>3. Excepciones: pagos que el sistema no pudo identificar</h3>{$simulacion}{$mExc}{$exc}</section>
<section class="card" id="aspirantes"><h3>4. Aspirantes <span class="nota">({$cuenta})</span></h3>
 <form method="get" class="filtros"><input type="hidden" name="r" value="panel">
  <div class="field"><label for="q">Buscar por control o nombre</label><input id="q" name="q" value="{$fq}" placeholder="Ej. 2624010"></div>
  <div class="field"><label for="fe">Estatus</label><select id="fe" name="estatus">{$fEst}</select></div>
  <div class="field"><label for="fc">Carrera</label><select id="fc" name="carrera">{$fCar}</select></div>
  <div class="field"><label for="fg">Grupo</label><select id="fg" name="grupo">{$fGru}</select></div>
  <div class="field"><button class="btn" type="submit">Buscar</button></div>
 </form>
 <div class="tabla alta"><table><thead><tr><th>Control</th><th>Nombre</th><th>Carrera</th><th>Grupo</th><th>Referencia</th><th>Estatus</th><th>Nivel</th></tr></thead><tbody>{$filas}</tbody></table></div>
 <div class="fila"><a class="btn alt chico" href="?r=descargar&amp;que=pagados">Lista del examen (pagados)</a><a class="btn alt chico" href="?r=descargar&amp;que=nivel">Lista por nivel</a><a class="btn alt chico" href="?r=descargar&amp;que=aspirantes">Tabla de registros</a></div>
</section>
<div class="cols">
 <section class="card" id="fechas"><h3>5. Fechas de la convocatoria</h3><p class="nota">Se capturan una sola vez y alimentan validaciones, avisos y listas.</p>
  <form method="post" action="?r=fechas">{$csrf}
   <div class="field"><label for="d1">Cierre de registro</label><input id="d1" name="cierre" type="date" value="{$c['cierre']}"></div>
   <div class="field"><label for="d2">Pago, del</label><input id="d2" name="payFrom" type="date" value="{$c['payFrom']}"></div>
   <div class="field"><label for="d3">Pago, al</label><input id="d3" name="payTo" type="date" value="{$c['payTo']}"></div>
   <div class="field"><label for="d4">Examen</label><input id="d4" name="exam" type="date" value="{$c['exam']}"></div>
   <div class="field"><label for="d0">Hoy (simulado, para probar cierres; vacío = fecha real)</label><input id="d0" name="hoy" type="date" value="{$hoyVal}"></div>
   <button class="btn" type="submit">Guardar fechas</button></form>{$mFechas}
  <h3 class="mt">Convocatoria para compartir</h3><label for="conv">Texto listo para Facebook, avisos o Teams</label><textarea id="conv" rows="5" readonly>{$conv}</textarea></section>
 <div>
  <section class="card" id="recordatorio"><h3>6. Recordatorio de pago</h3><p class="nota">{$remTxt}</p>
   <div class="fila"><form method="post" action="?r=recordatorio">{$csrf}<button class="btn" type="submit">Enviar lista a la Coordinación (simulado)</button></form><a class="btn alt" href="?r=descargar&amp;que=pendientes">Descargar lista</a></div>{$mRem}</section>
  <section class="card" id="nube"><h3>7. Conexión con Power Automate</h3>{$nube}{$mNube}</section>
  <section class="card" id="datos"><h3>8. Datos de prueba</h3>{$avisoDatos}
   <p class="nota">Son tres archivos porque simulan tres fuentes: los <b>registros</b> (lo que ya está en el sistema), el <b>reporte de pagos</b> (lo que entrega caja o el banco) y los <b>resultados</b> (lo que exporta la plataforma del examen, que no controla el Tec). Los de ejemplo se arman con los registros de ahora, así siempre coinciden.</p>
   <div class="fila"><a class="btn alt chico" href="?r=descargar&amp;que=aspirantes">Registros</a><a class="btn alt chico" href="?r=descargar&amp;que=ejpagos">Pagos de ejemplo</a><a class="btn alt chico" href="?r=descargar&amp;que=ejresultados">Resultados de ejemplo</a></div>
   <form method="post" action="?r=datos" class="fila mt">{$csrf}<button class="btn alt chico" name="n" value="60">Reiniciar con 60</button><button class="btn alt chico" name="n" value="600">Reiniciar con 600</button><button class="btn alt chico" name="n" value="0">Dejar vacío</button><button class="btn alt chico" name="n" value="medir">Medir tiempos</button></form>
   <form method="post" action="?r=desbloquear" class="fila mt">{$csrf}<button class="btn alt chico" type="submit">Quitar bloqueos por intentos fallidos</button></form>{$mDatos}</section>
  <section class="card"><h3>Bandeja de avisos (simulada)</h3><p class="nota">Lo que el sistema enviaría.</p><ul>{$avisos}</ul></section>
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
<div class="tabla alta"><table><thead><tr><th>Referencia</th><th>Concepto</th><th>Control</th><th>Nombre</th><th>Monto</th><th>Estatus de pago</th></tr></thead><tbody>{$filas}</tbody></table></div>
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
<div class="tabla alta"><table><thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Rol</th><th>Acción</th><th>Resultado</th><th>Detalle</th><th>IP</th><th>HTTPS</th><th>Navegador</th></tr></thead><tbody>{$filas}</tbody></table></div>
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
