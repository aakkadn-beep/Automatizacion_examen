<?php
declare(strict_types=1);

/*
 * Casos de prueba CP-01 a CP-29 (matriz de trazabilidad de E2).
 * Cada caso tiene: requisito, qué se prueba, resultado esperado y una función que
 * devuelve el resultado obtenido. Se corren desde la terminal (tests/pruebas.php)
 * o desde la pantalla «Pruebas». Siempre usan una COPIA de datos, nunca la base del panel.
 */
final class Pruebas
{
    private const OK = ['control' => '29990001', 'nombre' => 'Ana Prueba Rivas', 'carrera' => 'Ing. en Sistemas Computacionales', 'grupo' => 'A'];

    /** Datos de prueba con la fecha simulada fija, para que el resultado no dependa del día. */
    private static function base(int $n): array
    {
        $s = Nucleo::nuevoConDatos($n);
        $s['hoy'] = '2026-11-20';
        return $s;
    }

    private static function ms(float $inicio): float
    {
        return (hrtime(true) - $inicio) / 1e6;
    }

    private static function r(bool $ok, string $obtenido): array
    {
        return ['estado' => $ok ? 'Pasa' : 'Falla', 'obtenido' => $obtenido];
    }

    private static function contraste(string $a, string $b): float
    {
        $lum = static function (string $h): float {
            $v = array_map(static function ($i) use ($h) {
                $x = hexdec(substr(ltrim($h, '#'), $i, 2)) / 255;
                return $x <= 0.03928 ? $x / 12.92 : (($x + 0.055) / 1.055) ** 2.4;
            }, [0, 2, 4]);
            return 0.2126 * $v[0] + 0.7152 * $v[1] + 0.0722 * $v[2];
        };
        [$x, $y] = [$lum($a), $lum($b)];
        return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
    }

    public static function casos(): array
    {
        $OK = self::OK;
        $T = [];
        $t = static function (string $cp, string $req, string $que, string $esp, callable $fn) use (&$T) {
            $T[] = compact('cp', 'req', 'que', 'esp', 'fn');
        };

        $t('CP-01', 'RF-01', 'Registro con datos válidos', 'Se guarda 1 registro con estatus «Registrado» en menos de 5 s', function () use ($OK) {
            $s = self::base(0);
            $a = hrtime(true);
            $r = Nucleo::registrar($s, $OK);
            $ms = self::ms($a);
            return self::r($r['ok'] && count($s['regs']) === 1 && $r['rec']['st'] === 'Registrado' && $ms < 5000,
                'Registros: ' . count($s['regs']) . ', estatus ' . ($r['rec']['st'] ?? '-') . ', ' . number_format($ms, 2) . ' ms');
        });
        $t('CP-02', 'RF-02', 'Carrera y grupo solo del catálogo', 'Carrera o grupo fuera del catálogo se rechaza; en 20 registros hay 0 variantes', function () use ($OK) {
            $s = self::base(0);
            $a = Nucleo::registrar($s, ['carrera' => 'sistemas'] + $OK);
            $b = Nucleo::registrar($s, ['grupo' => 'D'] + $OK);
            $s2 = self::base(20);
            $bad = count(array_filter(array_unique(array_column($s2['regs'], 'ca')), static fn ($v) => !in_array($v, Nucleo::CARRERAS, true)));
            return self::r(!$a['ok'] && !$b['ok'] && $bad === 0, 'Carrera libre: ' . ($a['ok'] ? 'aceptada' : 'rechazada') . '; grupo D: ' . ($b['ok'] ? 'aceptado' : 'rechazado') . '; variantes fuera de catálogo en 20: ' . $bad);
        });
        $t('CP-03', 'RF-03', 'Campo vacío o número de control inválido', 'No se guarda y el mensaje dice qué campo corregir', function () use ($OK) {
            $s = self::base(0);
            $r1 = Nucleo::registrar($s, ['control' => '123'] + $OK);
            $r2 = Nucleo::registrar($s, ['nombre' => ''] + $OK);
            $r3 = Nucleo::registrar($s, ['control' => '', 'nombre' => '', 'carrera' => '', 'grupo' => '']);
            $ok = !$r1['ok'] && isset($r1['errores']['control']) && !$r2['ok'] && isset($r2['errores']['nombre']) && count($r3['errores']) === 4 && count($s['regs']) === 0;
            return self::r($ok, 'Guardados: ' . count($s['regs']) . '; mensaje de control: «' . ($r1['errores']['control'] ?? '') . '»');
        });
        $t('CP-04', 'RF-04, RN-08', 'Mismo número de control dos veces', 'Solo existe 1 registro y el segundo intento muestra un aviso', function () use ($OK) {
            $s = self::base(0);
            Nucleo::registrar($s, $OK);
            $r = Nucleo::registrar($s, $OK);
            return self::r(!$r['ok'] && count($s['regs']) === 1 && str_contains($r['errores']['control'] ?? '', 'ya está registrado'),
                'Registros: ' . count($s['regs']) . '; aviso: «' . ($r['errores']['control'] ?? '') . '»');
        });
        $t('CP-05', 'RF-05', 'Registro asistido', 'Queda en la misma base, marcado «asistido», con las mismas validaciones', function () use ($OK) {
            $s = self::base(0);
            $bad = Nucleo::registrar($s, ['control' => '1'] + $OK, true);
            $r = Nucleo::registrar($s, $OK, true);
            return self::r(!$bad['ok'] && $r['ok'] && $r['rec']['asistido'] === true && count($s['regs']) === 1,
                'Inválido rechazado: ' . (!$bad['ok'] ? 'sí' : 'no') . '; válido guardado como asistido: ' . (!empty($r['rec']['asistido']) ? 'sí' : 'no'));
        });
        $t('CP-06', 'RF-06, RN-01, RN-04', 'Referencia y concepto', 'En el 100 % de 60 registros cumplen RN-04 (control + dígito; CLE + siglas)', function () {
            $s = self::base(60);
            $bien = 0;
            foreach ($s['regs'] as $r) {
                $sig = implode('', array_map(static fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), explode(' ', $r['n'])));
                if ($r['ref'] === $r['c'] . Nucleo::dv($r['c']) && strlen($r['ref']) === 9 && $r['con'] === 'CLE' . $sig && preg_match('/^CLE[A-Z]{3,}$/', $r['con'])) {
                    $bien++;
                }
            }
            return self::r($bien === 60 && Nucleo::CUOTA === 500, $bien . ' de 60 correctos; cuota $' . Nucleo::CUOTA);
        });
        $t('CP-07', 'RF-07, RN-03, RN-05, RN-09', 'Cruce de un reporte de 60 pagos con 3 que no coinciden', '57 quedan «pagado» sin intervención y 3 pasan a excepciones', function () {
            $s = self::base(60);
            $r = Nucleo::importarPagos($s, Nucleo::pagosEjemploCSV($s, 60, true));
            $pag = count(array_filter($s['regs'], static fn ($x) => $x['st'] === 'Pagado'));
            return self::r($r['identificados'] === 57 && $r['excepciones'] === 3 && $pag === 57,
                'Pagado: ' . $pag . '; excepciones: ' . count($s['exc']) . ' (' . implode(', ', array_column($s['exc'], 'tipo')) . ')');
        });
        $t('CP-08', 'RF-08', 'Asociar manualmente una excepción', 'El registro asociado cambia a «pagado» y la excepción desaparece', function () {
            $s = self::base(60);
            Nucleo::importarPagos($s, Nucleo::pagosEjemploCSV($s, 60, true));
            $ex = current(array_filter($s['exc'], static fn ($e) => $e['tipo'] === 'Referencia sin registro'));
            $pend = current(array_filter($s['regs'], static fn ($x) => $x['st'] !== 'Pagado'));
            $r = Nucleo::asociarExcepcion($s, $ex['id'], $pend['c']);
            $ahora = current(array_filter($s['regs'], static fn ($x) => $x['c'] === $pend['c']));
            $dup = Nucleo::importarPagos($s, "referencia,monto,fecha\n" . $s['regs'][0]['ref'] . ',500,' . $s['conv']['payFrom'] . "\n");
            return self::r($r['ok'] && $ahora['st'] === 'Pagado' && count($s['exc']) === 3 && $dup['excepciones'] === 1,
                'Asociado: ' . ($r['ok'] ? 'sí' : 'no') . '; excepciones tras asociar y tras un duplicado: ' . count($s['exc']));
        });
        $t('CP-09', 'RF-09, RN-02, RN-11', 'Fechas capturadas una vez', 'El sistema las usa en validaciones y listas sin volver a pedirlas', function () use ($OK) {
            $s = self::base(0);
            $s['conv']['cierre'] = '2026-11-19';
            $r = Nucleo::registrar($s, $OK);
            $s['conv']['cierre'] = '2026-11-27';
            $r2 = Nucleo::registrar($s, $OK);
            $ok = !$r['ok'] && str_contains($r['errores']['general'] ?? '', 'cerró el 19 de noviembre') && $r2['ok'] && str_contains(Nucleo::textoConvocatoria($s, 'x'), '27 de noviembre');
            return self::r($ok, 'Cierre 19-nov: «' . ($r['errores']['general'] ?? '') . '»; con cierre 27-nov: ' . ($r2['ok'] ? 'acepta' : 'rechaza'));
        });
        $t('CP-10', 'RF-10', 'Texto de la convocatoria', 'Incluye las 3 fechas, la cuota y el enlace', function () {
            $x = Nucleo::textoConvocatoria(self::base(0), 'https://ejemplo.mx/');
            $ok = true;
            foreach (['27 de noviembre', '30 de noviembre', '2 de diciembre', '4 de diciembre', '$500', 'https://ejemplo.mx/'] as $p) {
                $ok = $ok && str_contains($x, $p);
            }
            return self::r($ok, $x);
        });
        $t('CP-11', 'RF-11', 'Recordatorio de pago', 'Lista a quien se registró y no ha pagado; a quien ya pagó no', function () {
            $s = self::base(60);
            Nucleo::importarPagos($s, Nucleo::pagosEjemploCSV($s, 60, true));
            $r = Nucleo::enviarRecordatorio($s);
            $pagados = array_column(array_filter($s['regs'], static fn ($x) => $x['st'] === 'Pagado'), 'c');
            $mezcla = (bool) array_intersect(array_column($r['pendientes'], 'control'), $pagados);
            return self::r(count($r['pendientes']) === 3 && !$mezcla && Nucleo::fechaRecordatorio($s) === '2026-12-01',
                'Pendientes: ' . count($r['pendientes']) . '; fecha del recordatorio: ' . $r['fecha'] . '; incluye pagados: ' . ($mezcla ? 'sí' : 'no'));
        });
        $t('CP-12', 'RF-12', 'Confirmación de registro y de pago', 'Se genera la confirmación correspondiente en cada momento', function () use ($OK) {
            $s = self::base(0);
            Nucleo::registrar($s, $OK);
            Nucleo::importarPagos($s, "referencia,monto,fecha\n" . $s['regs'][0]['ref'] . ',500,' . $s['conv']['payFrom']);
            $tipos = array_column($s['avisos'], 'tipo');
            return self::r(in_array('registro', $tipos, true) && in_array('pago', $tipos, true), 'Avisos generados: ' . implode(', ', $tipos));
        });
        $t('CP-13', 'RF-13, RNF-05', 'Consulta con código correcto e incorrecto', 'Con el código correcto ve solo estatus y nivel; con uno incorrecto no ve nada', function () use ($OK) {
            $s = self::base(0);
            $r = Nucleo::registrar($s, $OK);
            $ok = Nucleo::consultar($s, $OK['control'], $r['rec']['code']);
            $mal = Nucleo::consultar($s, $OK['control'], $r['rec']['code'] === 'AAAA' ? 'BBBB' : 'AAAA');
            $campos = $ok ? array_keys($ok) : [];
            sort($campos);
            return self::r($ok !== null && $mal === null && $campos === ['estatus', 'nivel'],
                'Campos devueltos: ' . implode(', ', $campos) . '; con código incorrecto: ' . ($mal === null ? 'nada' : 'datos'));
        });
        $t('CP-14', 'RF-14, RN-07', 'Lista del examen', 'Solo aspirantes con estatus «pagado»', function () {
            $s = self::base(60);
            Nucleo::importarPagos($s, Nucleo::pagosEjemploCSV($s, 60, true));
            $l = Nucleo::listaPagados($s);
            $pag = array_column(array_filter($s['regs'], static fn ($x) => $x['st'] === 'Pagado'), 'c');
            $enc = explode("\r\n", Nucleo::aCSV(Nucleo::COLS['pagados'], $l))[0];
            $ok = count($l) === 57 && !array_diff(array_column($l, 'control'), $pag) && $enc === 'Control,Nombre,Carrera,Grupo';
            return self::r($ok, 'Filas: ' . count($l) . ', todas pagadas; encabezado del CSV: ' . $enc);
        });
        $t('CP-15', 'RF-15', 'Reporte para caja', 'Lista a todos los registrados con su estatus y coincide 100 % con la base', function () {
            $s = self::base(60);
            Nucleo::importarPagos($s, Nucleo::pagosEjemploCSV($s, 60, true));
            $r = Nucleo::reporteCaja($s);
            $igual = 0;
            foreach ($r as $i => $x) {
                $igual += ($x['control'] === $s['regs'][$i]['c'] && $x['estatus'] === $s['regs'][$i]['st'] && $x['referencia'] === $s['regs'][$i]['ref']) ? 1 : 0;
            }
            return self::r(count($r) === count($s['regs']) && $igual === count($r), $igual . ' de ' . count($s['regs']) . ' filas coinciden');
        });
        $t('CP-16', 'RF-16, RN-10', 'Importar resultados (60 válidos) y uno sin coincidencia', 'Cada nivel se asocia al aspirante correcto; lo que no coincide va a excepciones', function () {
            $s = self::base(60);
            Nucleo::importarPagos($s, Nucleo::pagosEjemploCSV($s, 60, true));
            foreach (array_filter($s['exc'], static fn ($e) => $e['kind'] === 'pago') as $e) {
                $p = current(array_filter($s['regs'], static fn ($x) => $x['st'] !== 'Pagado'));
                Nucleo::asociarExcepcion($s, $e['id'], $p['c']);
            }
            $p = Nucleo::previsualizarResultados($s, Nucleo::resultadosEjemploCSV($s, 7));
            $pub = Nucleo::publicarResultados($s, $p);
            $mal = Nucleo::previsualizarResultados($s, "control,nivel\n26249999,B1\n");
            $antes = count(array_filter($s['regs'], static fn ($x) => $x['pub']));
            $fmt = Nucleo::previsualizarResultados($s, "a,b\n1,2");
            $despues = count(array_filter($s['regs'], static fn ($x) => $x['pub']));
            return self::r($pub['publicados'] === 60 && count($mal['exc']) === 1 && !$fmt['ok'] && $antes === $despues,
                'Publicados: ' . $pub['publicados'] . '; sin coincidencia a excepciones: ' . count($mal['exc']) . '; archivo con formato inválido: ' . ($fmt['ok'] ? 'aceptado' : 'rechazado sin cambios'));
        });
        $t('CP-17', 'RF-17, RF-13', 'El aspirante ve su nivel', 'Tras importar, el aspirante ve su nivel al consultar', function () {
            $s = self::base(2);
            foreach ($s['regs'] as &$r) {
                $r['st'] = 'Pagado';
            }
            unset($r);
            $p = Nucleo::previsualizarResultados($s, "control,nivel\n" . $s['regs'][0]['c'] . ",B2\n");
            $antes = Nucleo::consultar($s, $s['regs'][0]['c'], $s['regs'][0]['code']);
            Nucleo::publicarResultados($s, $p);
            $desp = Nucleo::consultar($s, $s['regs'][0]['c'], $s['regs'][0]['code']);
            return self::r($antes['nivel'] === '' && $desp['nivel'] === 'B2' && $desp['estatus'] === 'Nivel disponible',
                'Antes: «' . $antes['estatus'] . '», sin nivel. Después: «' . $desp['estatus'] . '», nivel ' . $desp['nivel']);
        });
        $t('CP-18', 'RF-18', 'Lista por nivel', 'Contiene solo aspirantes con nivel asignado', function () {
            $s = self::base(10);
            foreach ($s['regs'] as &$r) {
                $r['st'] = 'Pagado';
            }
            unset($r);
            Nucleo::publicarResultados($s, Nucleo::previsualizarResultados($s, "control,nivel\n" . $s['regs'][0]['c'] . ",A1\n" . $s['regs'][1]['c'] . ",B1\n"));
            $l = Nucleo::listaNivel($s);
            return self::r(count($l) === 2 && count(array_filter(array_column($l, 'nivel'))) === 2, 'Filas: ' . count($l) . ' de 10 aspirantes; todas con nivel');
        });
        $t('CP-19', 'RF-19', 'Tablero', 'Las cifras coinciden con los conteos de la base', function () {
            $s = self::base(60);
            Nucleo::importarPagos($s, Nucleo::pagosEjemploCSV($s, 60, true));
            $e = Nucleo::estadisticas($s);
            $pag = count(array_filter($s['regs'], static fn ($x) => $x['st'] === 'Pagado'));
            $ok = $e['registrados'] === count($s['regs']) && $e['pagados'] === $pag && $e['pendientes'] === 3 && $e['asistentes'] === 0 && $e['excepciones'] === count($s['exc']);
            return self::r($ok, 'Registrados ' . $e['registrados'] . ', pagados ' . $e['pagados'] . ', pendientes ' . $e['pendientes'] . ', excepciones ' . $e['excepciones']);
        });
        $t('CP-20', 'RF-20', 'Búsqueda y filtros combinados', 'Buscar por control devuelve el registro en menos de 3 s y los filtros se combinan', function () {
            $s = self::base(600);
            $a = hrtime(true);
            $r = Nucleo::filtrar($s, ['q' => $s['regs'][300]['c']]);
            $f = Nucleo::filtrar($s, ['carrera' => Nucleo::CARRERAS[1], 'grupo' => 'B', 'estatus' => 'Registrado']);
            $uno = Nucleo::filtrar($s, ['carrera' => Nucleo::CARRERAS[1]]);
            $ms = self::ms($a);
            return self::r(count($r) === 1 && count($f) > 0 && count($f) < count($uno) && $ms < 3000,
                'Búsqueda: ' . count($r) . ' resultado; combinar filtros reduce de ' . count($uno) . ' a ' . count($f) . '; ' . number_format($ms, 2) . ' ms');
        });
        $t('CP-21', 'RF-21', 'Instrucciones en la página de registro', 'Muestra pasos, 3 fechas, monto, formas de pago y la advertencia de no transferible', function () {
            $html = Vistas::registro(self::base(0), null, [], [], null);
            $txt = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
            $ok = str_contains($txt, 'Paga $500 en banco o en caja') && str_contains($txt, 'Registro hasta') && str_contains($txt, 'Pago')
                && str_contains($txt, 'Examen') && str_contains($txt, 'no es transferible') && str_contains($txt, 'no hay fecha extraordinaria')
                && preg_match('/\d+ de [a-z]+/u', $txt);
            return self::r((bool) $ok, 'Texto encontrado en la página generada: pasos, fechas, $500, banco o caja y advertencia');
        });
        $t('CP-22', 'RNF-01', 'Registro en menos de 2 min sin ayuda (5 usuarios)', 'Al menos 4 de 5 lo completan', static fn () => ['estado' => 'Pendiente', 'obtenido' => 'Requiere 5 personas reales. El resultado sale de la prueba de usabilidad (sección 12.4).']);
        $t('CP-23', 'RNF-02', 'Registro y consulta en un celular de 360 px', 'Sin desplazamiento horizontal; todo operable', function () {
            $css = Vistas::css();
            $html = Vistas::pagina('x', 'registro', '', null);
            $ok = str_contains($html, 'width=device-width') && str_contains($css, '@media (max-width:480px)');
            return ['estado' => 'Pendiente', 'obtenido' => 'Aquí solo se revisa que la página se adapte (viewport y estilos para pantallas chicas: ' . ($ok ? 'sí' : 'no') . '). El ancho real se mide con un navegador a 360 px (tests/pantallas.py).'];
        });
        $t('CP-24', 'RNF-03', 'Contraste mínimo 4.5:1', 'Todos los pares texto/fondo del prototipo cumplen', function () {
            $out = [];
            $ok = true;
            foreach (Vistas::PARES as [$a, $b]) {
                $v = self::contraste(Vistas::COLOR[$a], Vistas::COLOR[$b]);
                $ok = $ok && $v >= 4.5;
                $out[] = $a . '/' . $b . ' ' . number_format($v, 1);
            }
            return self::r($ok, implode('; ', $out));
        });
        $t('CP-25', 'RNF-04', 'Mensajes de error claros', 'Cada error definido tiene mensaje claro, sin códigos técnicos', function () use ($OK) {
            $s = self::base(1);
            $s['conv']['cierre'] = '2026-11-01';
            $e1 = Nucleo::validarRegistro($s, ['control' => '', 'nombre' => '', 'carrera' => '', 'grupo' => ''])['errores'];
            $e2 = Nucleo::validarRegistro($s, ['control' => 'ab12', 'nombre' => 'Ana', 'carrera' => 'x', 'grupo' => 'x'])['errores'];
            $e3 = Nucleo::validarRegistro($s, ['control' => $s['regs'][0]['c']] + $OK)['errores'];
            $todos = array_merge(array_values($e1), array_values($e2), array_values($e3));
            $tecnico = '/(undefined|null|exception|stack|error\s*\d|http|\b[45]\d\d\b)/i';
            $ok = count($todos) >= 9 && !array_filter($todos, static fn ($m) => mb_strlen($m) <= 15 || preg_match($tecnico, $m)) && isset($e3['control'], $e1['general']);
            return self::r($ok, count($todos) . ' mensajes revisados (vacío, formato, duplicado y fuera de fecha); ninguno con códigos técnicos. Que las personas los entiendan se mide en la prueba de usabilidad.');
        });
        $t('CP-26', 'RNF-05', 'Solo los datos necesarios', 'El registro tiene exactamente 4 campos; la consulta solo pide control y código', function () {
            $reg = Vistas::registro(self::base(0), null, [], [], null);
            $con = Vistas::consulta(self::base(0), null, null, '');
            $cuenta = static function (string $html, string $id): array {
                $d = new DOMDocument();
                @$d->loadHTML('<?xml encoding="utf-8"?>' . $html);
                $x = new DOMXPath($d);
                $n = $x->query("//form[@id='$id']//input[not(@type='hidden') and not(@type='checkbox')] | //form[@id='$id']//select")->length;
                $tel = $x->query("//form[@id='$id']//input[@type='email' or @type='tel']")->length;
                return [$n, $tel];
            };
            [$n, $tel1] = $cuenta($reg, 'f');
            [$c, $tel2] = $cuenta($con, 'q');
            return self::r($n === 4 && $c === 2 && $tel1 + $tel2 === 0, 'Campos de registro: ' . $n . '; campos de consulta: ' . $c . '; correo o teléfono: ' . ($tel1 + $tel2 ? 'sí' : 'no'));
        });
        $t('CP-27', 'RNF-06', 'Prueba negativa de acceso', 'Sin sesión no se accede a listas ni reportes; la consulta solo devuelve el propio estatus', function () {
            $a = Nucleo::puedeVer(null, 'panel');
            $b = Nucleo::puedeVer(null, 'caja');
            $c = Nucleo::puedeVer('caja', 'panel');
            $d = Nucleo::puedeVer('caja', 'caja');
            $e = Nucleo::puedeVer('coordinador', 'panel');
            $g = Nucleo::puedeVer(null, 'auditoria') || Nucleo::puedeVer('caja', 'auditoria');
            $x = Seguridad::entrar('coordinador', 'mala');
            $s = self::base(2);
            $ver = Nucleo::consultar($s, $s['regs'][0]['c'], $s['regs'][1]['code']);
            return self::r(!$a && !$b && !$c && $d && $e && !$g && $x === null && $ver === null,
                'Sin sesión panel/caja: ' . ($a || $b ? 'permitido' : 'bloqueado') . '; caja al panel o a la bitácora: ' . ($c || $g ? 'permitido' : 'bloqueado')
                . '; clave incorrecta: ' . ($x === null ? 'rechazada' : 'aceptada') . '; código de otro aspirante: ' . ($ver === null ? 'nada' : 'datos'));
        });
        $t('CP-28', 'RNF-07', 'Rendimiento con 600 registros', 'Registrar, buscar y filtrar toman menos de 3 s', function () use ($OK) {
            $s = self::base(600);
            $a = hrtime(true);
            Nucleo::registrar($s, $OK);
            $r = self::ms($a);
            $a = hrtime(true);
            Nucleo::filtrar($s, ['q' => '29990001']);
            $b = self::ms($a);
            $a = hrtime(true);
            Nucleo::filtrar($s, ['q' => '2624', 'carrera' => Nucleo::CARRERAS[0], 'grupo' => 'A', 'estatus' => 'Registrado']);
            $f = self::ms($a);
            return self::r(count($s['regs']) === 601 && max($r, $b, $f) < 3000,
                '601 registros. Registrar ' . number_format($r, 2) . ' ms, buscar ' . number_format($b, 2) . ' ms, filtrar ' . number_format($f, 2) . ' ms (solo la lógica, sin red)');
        });
        $t('CP-29', 'RNF-08', 'Disponibilidad de 99 % en las ventanas', 'Disponibilidad medida', static fn () => ['estado' => 'Pendiente', 'obtenido' => 'Se mide con el servicio publicado en la nube durante las ventanas de registro y pago. No se ha medido.']);

        return $T;
    }

    /** Ejecuta todos los casos y devuelve [resultados, milisegundos]. */
    public static function correr(): array
    {
        $a = hrtime(true);
        $out = [];
        foreach (self::casos() as $c) {
            try {
                $r = ($c['fn'])();
            } catch (Throwable $ex) {
                $r = ['estado' => 'Falla', 'obtenido' => 'Error en la prueba: ' . $ex->getMessage()];
            }
            $out[] = ['cp' => $c['cp'], 'req' => $c['req'], 'que' => $c['que'], 'esp' => $c['esp']] + $r;
        }
        return [$out, self::ms($a)];
    }
}
