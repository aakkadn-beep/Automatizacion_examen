<?php
declare(strict_types=1);

/*
 * EduNex Systems · Núcleo del sistema (reglas del negocio).
 *
 * Aquí viven TODAS las reglas: validar el registro, calcular la referencia, cruzar pagos,
 * importar resultados, armar listas y reportes. No imprime nada en pantalla; solo recibe
 * datos y devuelve resultados. Por eso se puede probar sin navegador (tests/pruebas.php).
 *
 * El "estado" es un arreglo de PHP con esta forma:
 *   conv    => fechas de la convocatoria (cierre, payFrom, payTo, exam)
 *   hoy     => fecha simulada para probar cierres ('' = fecha real del servidor)
 *   regs    => lista de aspirantes registrados
 *   exc     => excepciones (pagos o resultados que el sistema no pudo asociar solo)
 *   avisos  => confirmaciones y recordatorios que el sistema "enviaría" (simulados)
 *   nextId  => contador para darle un número único a cada excepción o aviso
 *
 * Todos los datos son simulados. Las reglas siguen los requisitos de E2 (RF, RNF y RN).
 */
final class Nucleo
{
    // Carreras del campus que pueden registrarse (lista dada por la Coordinación).
    public const ISC = 'Ingeniería en Sistemas Computacionales';
    public const CARRERAS = [
        'Contador Público', 'Ingeniería Civil', 'Ingeniería en Gestión Empresarial',
        'Ingeniería Industrial', self::ISC,
    ];
    public const GRUPOS = ['A', 'B', 'C'];                 // El grupo indica el turno.

    /** Grupos que existen en cada carrera: Sistemas solo tiene A y B. */
    public static function gruposDe(string $carrera): array
    {
        return $carrera === self::ISC ? ['A', 'B'] : self::GRUPOS;
    }
    public const NIVELES = ['A1', 'A2', 'B1', 'B2', 'C1'];
    public const CUOTA = 500;                              // Pesos. Clave A003-007-003-000.
    private const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
        'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    // Alfabeto del código de consulta: sin 0, O, 1 ni I para que no se confundan al leerlo.
    private const ALF = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /* ------------------------------------------------------------------ utilidades */

    /** Dígito verificador (simulado): pesos 3 y 1 alternados, como un código de barras.
     *  El algoritmo real lo define Recursos Financieros; aquí solo se simula. */
    public static function dv(string $control): int
    {
        $s = 0;
        foreach (str_split($control) as $i => $d) {
            $s += (int) $d * ($i % 2 === 1 ? 1 : 3);
        }
        return (10 - $s % 10) % 10;
    }

    /** RN-04: referencia = número de control + dígito verificador. */
    public static function referencia(string $control): string
    {
        return $control . self::dv($control);
    }

    /** RN-04: concepto = "CLE" + iniciales del nombre en mayúsculas. */
    public static function concepto(string $nombre): string
    {
        $ini = '';
        foreach (preg_split('/\s+/u', trim($nombre)) as $p) {
            if ($p !== '') {
                $ini .= mb_strtoupper(mb_substr($p, 0, 1));
            }
        }
        return 'CLE' . $ini;
    }

    /** Generador pseudoaleatorio con semilla (mulberry32). Solo para crear datos de prueba
     *  que salgan siempre iguales. Usa aritmética de 32 bits, igual que el prototipo en JS. */
    public static function rng(int $semilla): callable
    {
        $a = $semilla & 0xFFFFFFFF;
        $imul = static function (int $x, int $y): int {
            $lo = ($x & 0xFFFF) * $y;
            $hi = (($x >> 16) & 0xFFFF) * $y;
            return ($lo + (($hi & 0xFFFF) << 16)) & 0xFFFFFFFF;
        };
        return static function () use (&$a, $imul): float {
            $a = ($a + 0x6D2B79F5) & 0xFFFFFFFF;
            $t = $imul($a ^ ($a >> 15), 1 | $a);
            $t = (($t + $imul($t ^ ($t >> 7), 61 | $t)) & 0xFFFFFFFF) ^ $t;
            return (($t ^ ($t >> 14)) & 0xFFFFFFFF) / 4294967296;
        };
    }

    /** Código de consulta de 4 caracteres. En registros reales usa random_int (seguro
     *  para criptografía); con semilla solo se usa para los datos de prueba. */
    public static function codigo(?callable $r = null): string
    {
        $s = '';
        $n = strlen(self::ALF);
        for ($i = 0; $i < 4; $i++) {
            $s .= self::ALF[$r ? (int) floor($r() * $n) : random_int(0, $n - 1)];
        }
        return $s;
    }

    public static function fechaTexto(string $iso): string
    {
        [$y, $m, $d] = array_map('intval', explode('-', $iso));
        return $d . ' de ' . self::MESES[$m - 1];
    }

    public static function sumarDias(string $iso, int $n): string
    {
        return (new DateTimeImmutable($iso . ' 12:00:00'))->modify(($n >= 0 ? '+' : '') . $n . ' days')->format('Y-m-d');
    }

    public static function hoy(array $st): string
    {
        return $st['hoy'] !== '' ? $st['hoy'] : date('Y-m-d');
    }

    private static function aviso(array &$st, string $tipo, string $para, string $texto): void
    {
        array_unshift($st['avisos'], ['id' => $st['nextId']++, 'tipo' => $tipo, 'para' => $para,
            'texto' => $texto, 'fecha' => self::hoy($st)]);
        $st['avisos'] = array_slice($st['avisos'], 0, 200);
    }

    private static function indicePorControl(array $st, string $control): ?int
    {
        foreach ($st['regs'] as $i => $r) {
            if ($r['c'] === $control) {
                return $i;
            }
        }
        return null;
    }

    private static function indicePorReferencia(array $st, string $ref): ?int
    {
        foreach ($st['regs'] as $i => $r) {
            if ($r['ref'] === $ref) {
                return $i;
            }
        }
        return null;
    }

    /* ------------------------------------------------------------------ estado */

    public static function nuevoEstado(): array
    {
        return [
            'v' => 1,
            'conv' => ['cierre' => '2026-11-27', 'payFrom' => '2026-11-30', 'payTo' => '2026-12-02', 'exam' => '2026-12-04'],
            'hoy' => '',
            'regs' => [],
            'exc' => [],
            'avisos' => [],
            'nextId' => 1,
        ];
    }

    /* ------------------------------------------------------------------ registro
       RF-01 a RF-06, RF-12, RN-02, RN-04, RN-08 */

    public static function validarRegistro(array $st, array $d): array
    {
        $e = [];
        $c = trim((string) ($d['control'] ?? ''));
        $n = preg_replace('/\s+/u', ' ', trim((string) ($d['nombre'] ?? '')));
        if ($c === '') {
            $e['control'] = 'Escribe tu número de control.';
        } elseif (!preg_match('/^\d{8}$/', $c)) {
            $e['control'] = 'El número de control tiene 8 dígitos y solo números. Revisa que no falte ninguno.';
        } elseif (self::indicePorControl($st, $c) !== null) {
            $e['control'] = 'Este número de control ya está registrado. Consulta tu estatus con tu código.';
        }
        if (count(array_filter(explode(' ', $n))) < 2) {
            $e['nombre'] = 'Escribe tu nombre completo, con apellidos.';
        }
        if (!in_array($d['carrera'] ?? '', self::CARRERAS, true)) {
            $e['carrera'] = 'Elige tu carrera de la lista.';
        }
        if (!in_array($d['grupo'] ?? '', self::GRUPOS, true)) {
            $e['grupo'] = 'Elige tu grupo de la lista.';
        } elseif (in_array($d['carrera'] ?? '', self::CARRERAS, true) && !in_array($d['grupo'], self::gruposDe($d['carrera']), true)) {
            $e['grupo'] = $d['carrera'] . ' solo tiene grupos ' . implode(' y ', self::gruposDe($d['carrera'])) . '. Revisa tu grupo.';
        }
        if (strcmp(self::hoy($st), $st['conv']['cierre']) > 0) {
            $e['general'] = 'El registro cerró el ' . self::fechaTexto($st['conv']['cierre']) . '. Ya no se aceptan más registros.';
        }
        return ['ok' => $e === [], 'errores' => $e];
    }

    private static function nuevoReg(array $st, string $c, string $n, string $ca, string $g, string $code, bool $asistido): array
    {
        return ['c' => $c, 'n' => $n, 'ca' => $ca, 'g' => $g, 'st' => 'Registrado', 'niv' => '', 'pub' => false,
            'code' => $code, 'ref' => self::referencia($c), 'con' => self::concepto($n),
            'asistido' => $asistido, 'fReg' => self::hoy($st), 'fPago' => ''];
    }

    public static function registrar(array &$st, array $d, bool $asistido = false, ?callable $r = null): array
    {
        $v = self::validarRegistro($st, $d);
        if (!$v['ok']) {
            return ['ok' => false, 'errores' => $v['errores']];
        }
        $rec = self::nuevoReg($st, trim($d['control']), preg_replace('/\s+/u', ' ', trim($d['nombre'])),
            $d['carrera'], $d['grupo'], self::codigo($r), $asistido);
        $st['regs'][] = $rec;
        self::aviso($st, 'registro', $rec['c'], 'Registro confirmado. Referencia ' . $rec['ref'] . ', concepto '
            . $rec['con'] . ', monto $' . self::CUOTA . '.');
        return ['ok' => true, 'rec' => $rec];
    }

    /* ------------------------------------------------------------------ consulta
       RF-13, RF-17, RNF-05, RNF-06 */

    public static function estatusDe(array $r): string
    {
        return $r['pub'] ? 'Nivel disponible' : $r['st'];
    }

    /** Devuelve los datos del propio aspirante (nombre, carrera, grupo, estatus y nivel),
     *  y solo si control y código coinciden. Nunca devuelve datos de otra persona. */
    public static function consultar(array $st, string $control, string $codigo): ?array
    {
        $codigo = strtoupper(trim($codigo));
        $i = self::indicePorControl($st, trim($control));
        // hash_equals compara en tiempo constante: no da pistas de cuántas letras acertó.
        if ($i === null || $codigo === '' || !hash_equals($st['regs'][$i]['code'], $codigo)) {
            return null;
        }
        $r = $st['regs'][$i];
        return ['nombre' => $r['n'], 'carrera' => $r['ca'], 'grupo' => $r['g'],
            'estatus' => self::estatusDe($r), 'nivel' => $r['pub'] ? $r['niv'] : ''];
    }

    /* ------------------------------------------------------------------ CSV */

    private static function normalizar(string $s): string
    {
        $s = mb_strtolower(trim($s));
        return strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    }

    /** Acepta fechas como las escribe Excel en español (30/11/2026) y las pasa a 2026-11-30.
     *  Corresponde al defecto D2 del libro. Si no reconoce el formato, la deja igual. */
    public static function normalizarFecha(string $f): string
    {
        $f = trim($f);
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $f, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        return $f;
    }

    /** Lee un CSV separado por comas o por punto y coma (Excel en español usa ;). */
    public static function leerCSV(string $texto): array
    {
        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);           // quita el BOM de Excel
        $lineas = array_values(array_filter(preg_split('/\r?\n/', $texto), static fn ($l) => trim($l) !== ''));
        if (!$lineas) {
            return ['headers' => [], 'rows' => []];
        }
        $sep = substr_count($lineas[0], ';') > substr_count($lineas[0], ',') ? ';' : ',';
        $cortar = static fn (string $l) => array_map('trim', str_getcsv($l, $sep, '"', ''));
        $headers = array_map([self::class, 'normalizar'], $cortar($lineas[0]));
        $rows = [];
        foreach (array_slice($lineas, 1) as $l) {
            $c = $cortar($l);
            $o = [];
            foreach ($headers as $i => $h) {
                $o[$h] = $c[$i] ?? '';
            }
            $rows[] = $o;
        }
        return ['headers' => $headers, 'rows' => $rows];
    }

    /** Arma un CSV. Evita "inyección de fórmulas": si una celda empieza con = + - @,
     *  Excel la ejecutaría como fórmula; se le antepone un apóstrofo. */
    public static function aCSV(array $cols, array $rows): string
    {
        $celda = static function ($v): string {
            $v = $v === null ? '' : (string) $v;
            if ($v !== '' && strpbrk($v[0], '=+-@') !== false) {
                $v = "'" . $v;
            }
            return preg_match('/[",;\n]/', $v) ? '"' . str_replace('"', '""', $v) . '"' : $v;
        };
        $out = [implode(',', array_map(static fn ($c) => $celda($c[1]), $cols))];
        foreach ($rows as $r) {
            $out[] = implode(',', array_map(static fn ($c) => $celda($r[$c[0]] ?? ''), $cols));
        }
        return implode("\r\n", $out) . "\r\n";
    }

    /* ------------------------------------------------------------------ cruce de pagos
       RF-07, RF-08, RF-12, RN-03, RN-05, RN-09 */

    public static function importarPagos(array &$st, string $texto): array
    {
        $p = self::leerCSV($texto);
        foreach (['referencia', 'monto', 'fecha'] as $h) {
            if (!in_array($h, $p['headers'], true)) {
                return ['ok' => false, 'error' => 'El archivo no tiene el formato esperado. Debe traer las columnas referencia, monto y fecha.'];
            }
        }
        $vistos = [];
        $ok = 0;
        $exc = 0;
        foreach ($p['rows'] as $f) {
            $f['fecha'] = self::normalizarFecha((string) $f['fecha']);
            $ref = $f['referencia'];
            $limpio = preg_replace('/[$,\s]/', '', (string) $f['monto']);
            $monto = is_numeric($limpio) ? (float) $limpio : null;
            $montoTxt = $monto === null ? $f['monto'] : (string) (0 + $monto);
            $i = $ref === '' ? null : self::indicePorReferencia($st, $ref);
            $tipo = '';
            $det = '';
            if ($ref === '') {
                $tipo = 'Pago sin referencia';
                $det = 'El pago no trae referencia.';
            } elseif ($i === null) {
                $tipo = 'Referencia sin registro';
                $det = 'La referencia ' . $ref . ' no coincide con ningún aspirante.';
            } elseif (isset($vistos[$ref]) || $st['regs'][$i]['st'] === 'Pagado') {
                $tipo = 'Pago duplicado';
                $det = 'La referencia ' . $ref . ' ya tiene un pago aplicado.';
            } elseif ($monto === null || $monto != self::CUOTA) {
                $tipo = 'Monto distinto';
                $det = 'Referencia ' . $ref . ': pagó $' . $montoTxt . ' y la cuota es $' . self::CUOTA . '.';
            } elseif (strcmp($f['fecha'], $st['conv']['payFrom']) < 0 || strcmp($f['fecha'], $st['conv']['payTo']) > 0) {
                $tipo = 'Pago fuera de la ventana';
                $det = 'Referencia ' . $ref . ': pagó el ' . $f['fecha'] . ' y la ventana es del ' . $st['conv']['payFrom'] . ' al ' . $st['conv']['payTo'] . '.';
            }
            if ($ref !== '') {
                $vistos[$ref] = true;
            }
            if ($tipo !== '') {
                $st['exc'][] = ['id' => $st['nextId']++, 'kind' => 'pago', 'ref' => $ref, 'monto' => $montoTxt,
                    'fecha' => $f['fecha'], 'tipo' => $tipo, 'detalle' => $det];
                $exc++;
            } else {
                $st['regs'][$i]['st'] = 'Pagado';
                $st['regs'][$i]['fPago'] = $f['fecha'];
                $ok++;
                self::aviso($st, 'pago', $st['regs'][$i]['c'], 'Pago confirmado. Ya estás en la lista del examen.');
            }
        }
        return ['ok' => true, 'total' => count($p['rows']), 'identificados' => $ok, 'excepciones' => $exc];
    }

    /** RF-08: la persona de Coordinación asocia a mano un pago que el sistema no identificó. */
    public static function asociarExcepcion(array &$st, int $id, string $control): array
    {
        $k = null;
        foreach ($st['exc'] as $j => $e) {
            if ($e['id'] === $id && $e['kind'] === 'pago') {
                $k = $j;
            }
        }
        if ($k === null) {
            return ['ok' => false, 'error' => 'No se encontró esa excepción.'];
        }
        $i = self::indicePorControl($st, $control);
        if ($i === null) {
            return ['ok' => false, 'error' => 'Elige a qué aspirante corresponde el pago.'];
        }
        if ($st['regs'][$i]['st'] === 'Pagado') {
            return ['ok' => false, 'error' => 'Ese aspirante ya tiene un pago aplicado.'];
        }
        $st['regs'][$i]['st'] = 'Pagado';
        $st['regs'][$i]['fPago'] = $st['exc'][$k]['fecha'] ?? '';
        array_splice($st['exc'], $k, 1);
        self::aviso($st, 'pago', $st['regs'][$i]['c'], 'Pago confirmado. Ya estás en la lista del examen.');
        return ['ok' => true, 'rec' => $st['regs'][$i]];
    }

    /** Aspirantes sugeridos para una excepción de pago: primero el dueño de esa referencia
     *  (monto distinto o fuera de ventana) o quien tiene una referencia casi igual
     *  (solo cambia el último dígito); después el resto de los que no han pagado. */
    public static function sugeridos(array $st, array $exc, int $max = 100): array
    {
        $ref = (string) ($exc['ref'] ?? '');
        $arriba = [];
        $resto = [];
        foreach ($st['regs'] as $r) {
            if ($r['st'] === 'Pagado') {
                continue;
            }
            $parecida = $ref !== '' && ($r['ref'] === $ref || (strlen($ref) === strlen($r['ref']) && substr($ref, 0, -1) === substr($r['ref'], 0, -1)));
            if ($parecida) {
                $arriba[] = $r + ['sugerido' => true];
            } else {
                $resto[] = $r + ['sugerido' => false];
            }
        }
        return array_slice(array_merge($arriba, $resto), 0, $max);
    }

    /* ------------------------------------------------------------------ resultados
       RF-16, RF-17, RN-07, RN-10 */

    public static function previsualizarResultados(array $st, string $texto): array
    {
        $p = self::leerCSV($texto);
        if (!in_array('control', $p['headers'], true) || !in_array('nivel', $p['headers'], true)) {
            return ['ok' => false, 'error' => 'El archivo no tiene el formato esperado. Debe traer las columnas control y nivel. No se cambió nada.'];
        }
        $asociados = [];
        $exc = [];
        foreach ($p['rows'] as $f) {
            $c = $f['control'];
            $n = strtoupper($f['nivel'] ?? '');
            $i = self::indicePorControl($st, $c);
            if ($i === null) {
                $exc[] = ['control' => $c, 'tipo' => 'Número de control sin coincidencia', 'detalle' => 'El número ' . $c . ' no está en el registro.'];
            } elseif ($st['regs'][$i]['st'] !== 'Pagado') {
                $exc[] = ['control' => $c, 'tipo' => 'Aspirante sin pago confirmado', 'detalle' => 'El aspirante ' . $c . ' no tiene pago confirmado.'];
            } elseif (!in_array($n, self::NIVELES, true)) {
                $exc[] = ['control' => $c, 'tipo' => 'Nivel no válido', 'detalle' => 'El nivel «' . $f['nivel'] . '» no existe.'];
            } else {
                $asociados[] = ['c' => $c, 'niv' => $n];
            }
        }
        return ['ok' => true, 'total' => count($p['rows']), 'asociados' => $asociados, 'exc' => $exc];
    }

    public static function publicarResultados(array &$st, array $prev): array
    {
        foreach ($prev['asociados'] as $a) {
            $i = self::indicePorControl($st, $a['c']);
            if ($i !== null) {
                $st['regs'][$i]['niv'] = $a['niv'];
                $st['regs'][$i]['pub'] = true;
            }
        }
        foreach ($prev['exc'] as $e) {
            $st['exc'][] = ['id' => $st['nextId']++, 'kind' => 'resultado', 'tipo' => $e['tipo'], 'detalle' => $e['detalle']];
        }
        return ['publicados' => count($prev['asociados']), 'excepciones' => count($prev['exc'])];
    }

    /* ------------------------------------------------------------------ tablero y listas
       RF-14, RF-15, RF-18, RF-19, RF-20 */

    public static function estadisticas(array $st): array
    {
        $niveles = [];
        $pag = 0;
        $asis = 0;
        foreach ($st['regs'] as $r) {
            if ($r['st'] === 'Pagado') {
                $pag++;
            }
            if ($r['pub']) {
                $asis++;
                $niveles[$r['niv']] = ($niveles[$r['niv']] ?? 0) + 1;
            }
        }
        $n = count($st['regs']);
        return ['registrados' => $n, 'pagados' => $pag, 'pendientes' => $n - $pag, 'asistentes' => $asis,
            'niveles' => $niveles, 'excepciones' => count($st['exc'])];
    }

    public static function filtrar(array $st, array $f): array
    {
        $q = mb_strtolower(trim((string) ($f['q'] ?? '')));
        return array_values(array_filter($st['regs'], static function ($r) use ($f, $q) {
            return ($q === '' || str_contains($r['c'], $q) || str_contains(mb_strtolower($r['n']), $q))
                && (empty($f['estatus']) || self::estatusDe($r) === $f['estatus'] || $r['st'] === $f['estatus'])
                && (empty($f['carrera']) || $r['ca'] === $f['carrera'])
                && (empty($f['grupo']) || $r['g'] === $f['grupo']);
        }));
    }

    /** RN-07: solo los aspirantes con pago confirmado entran a la lista del examen. */
    public static function listaPagados(array $st): array
    {
        $o = [];
        foreach ($st['regs'] as $r) {
            if ($r['st'] === 'Pagado') {
                $o[] = ['control' => $r['c'], 'nombre' => $r['n'], 'carrera' => $r['ca'], 'grupo' => $r['g']];
            }
        }
        return $o;
    }

    public static function listaNivel(array $st): array
    {
        $o = [];
        foreach ($st['regs'] as $r) {
            if ($r['pub']) {
                $o[] = ['control' => $r['c'], 'nombre' => $r['n'], 'carrera' => $r['ca'], 'grupo' => $r['g'], 'nivel' => $r['niv']];
            }
        }
        return $o;
    }

    public static function reporteCaja(array $st): array
    {
        return array_map(static fn ($r) => ['referencia' => $r['ref'], 'concepto' => $r['con'], 'control' => $r['c'],
            'nombre' => $r['n'], 'monto' => self::CUOTA, 'estatus' => $r['st']], $st['regs']);
    }

    public const COLS = [
        'pagados' => [['control', 'Control'], ['nombre', 'Nombre'], ['carrera', 'Carrera'], ['grupo', 'Grupo']],
        'nivel' => [['control', 'Control'], ['nombre', 'Nombre'], ['carrera', 'Carrera'], ['grupo', 'Grupo'], ['nivel', 'Nivel']],
        'caja' => [['referencia', 'Referencia'], ['concepto', 'Concepto'], ['control', 'Control'], ['nombre', 'Nombre'],
            ['monto', 'Monto'], ['estatus', 'Estatus de pago']],
        'pendientes' => [['control', 'Control'], ['nombre', 'Nombre'], ['referencia', 'Referencia'], ['concepto', 'Concepto']],
    ];

    /* ------------------------------------------------------------------ convocatoria y recordatorios
       RF-09, RF-10, RF-11, RF-21 */

    public static function textoConvocatoria(array $st, string $url): string
    {
        $c = $st['conv'];
        return 'Examen de ubicación de lengua extranjera (enero–junio 2027). Registro hasta el ' . self::fechaTexto($c['cierre'])
            . '. Pago de $' . self::CUOTA . ' del ' . self::fechaTexto($c['payFrom']) . ' al ' . self::fechaTexto($c['payTo'])
            . '. Examen el ' . self::fechaTexto($c['exam']) . '. Regístrate aquí: ' . $url;
    }

    /** El recordatorio sale 24 horas antes del cierre del pago. */
    public static function fechaRecordatorio(array $st): string
    {
        return self::sumarDias($st['conv']['payTo'], -1);
    }

    public static function recordatorios(array $st): array
    {
        $pend = [];
        foreach ($st['regs'] as $r) {
            if ($r['st'] !== 'Pagado') {
                $pend[] = ['control' => $r['c'], 'nombre' => $r['n'], 'referencia' => $r['ref'], 'concepto' => $r['con']];
            }
        }
        $fecha = self::fechaRecordatorio($st);
        return ['fecha' => $fecha, 'pendientes' => $pend, 'vencido' => strcmp(self::hoy($st), $fecha) >= 0];
    }

    public static function enviarRecordatorio(array &$st): array
    {
        $r = self::recordatorios($st);
        self::aviso($st, 'recordatorio', 'Coordinación', 'Lista de ' . count($r['pendientes'])
            . ' aspirantes sin pago. La ventana cierra el ' . self::fechaTexto($st['conv']['payTo']) . '.');
        return $r;
    }

    /** Valida las fechas que captura la Coordinación (RF-09). */
    public static function validarFechas(array $v): ?string
    {
        foreach (['cierre', 'payFrom', 'payTo', 'exam'] as $k) {
            if (!isset($v[$k]) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v[$k])) {
                return 'Completa las cuatro fechas.';
            }
        }
        if (strcmp($v['payFrom'], $v['payTo']) > 0) {
            return 'El pago no puede empezar después de que termina.';
        }
        if (strcmp($v['exam'], $v['payTo']) < 0) {
            return 'El examen debe ser después del cierre de pagos.';
        }
        return null;
    }

    /* ------------------------------------------------------------------ acceso por rol (RNF-06) */

    /** null = pantalla pública; arreglo = roles que pueden entrar. */
    public const VISTAS = [
        'inicio' => null, 'ayuda' => null, 'acceso' => null, 'registro' => null, 'consulta' => null,
        'panel' => ['coordinador'], 'caja' => ['coordinador', 'caja'], 'auditoria' => ['coordinador'],
    ];

    public static function puedeVer(?string $rol, string $vista): bool
    {
        if (!array_key_exists($vista, self::VISTAS)) {
            return false;
        }
        $need = self::VISTAS[$vista];
        return $need === null || ($rol !== null && in_array($rol, $need, true));
    }

    /* ------------------------------------------------------------------ datos simulados */

    private const NOM = ['Ana', 'Luis', 'Marta', 'Diego', 'Sofía', 'Iván', 'Camila', 'Jorge', 'Valeria', 'Pablo',
        'Renata', 'Mateo', 'Daniela', 'Andrés', 'Paola', 'Hugo', 'Lucía', 'Emilio', 'Regina', 'Bruno'];
    private const APE = ['Torres', 'Mena', 'Pérez', 'Cano', 'Lara', 'Soto', 'Rivas', 'Ortiz', 'Vela', 'Ruiz', 'Díaz',
        'Gil', 'Luna', 'Salas', 'Mora', 'Ibarra', 'Reyes', 'Campos', 'Zúñiga', 'Aguilar'];

    public static function sembrar(array &$st, int $n, int $semilla = 2026): void
    {
        $r = self::rng($semilla);
        $c = 26240101;
        if ($st['regs']) {
            $c = max(array_map(static fn ($x) => (int) $x['c'], $st['regs'])) + 1;
        }
        $pick = static fn (array $a) => $a[(int) floor($r() * count($a))];
        for ($i = 0; $i < $n; $i++, $c++) {
            $nom = $pick(self::NOM) . ' ' . $pick(self::APE) . ' ' . $pick(self::APE);
            $car = $pick(self::CARRERAS);
            $gru = $pick(self::GRUPOS);
            if (!in_array($gru, self::gruposDe($car), true)) {
                $gru = 'B';      // Sistemas no tiene grupo C (se ajusta sin cambiar la secuencia de datos)
            }
            $st['regs'][] = self::nuevoReg($st, (string) $c, $nom, $car, $gru, self::codigo($r), false);
        }
    }

    public static function nuevoConDatos(int $n = 60): array
    {
        $st = self::nuevoEstado();
        self::sembrar($st, $n);
        return $st;
    }

    /** Reporte de pagos de ejemplo: hasta $n pagos; 3 no coinciden a propósito
     *  (referencia mal escrita, monto distinto y fuera de la ventana). */
    public static function pagosEjemploCSV(array $st, int $n = 60, bool $conErrores = true): string
    {
        $pend = array_slice(array_values(array_filter($st['regs'], static fn ($r) => $r['st'] !== 'Pagado')), 0, $n);
        $dias = [$st['conv']['payFrom'], self::sumarDias($st['conv']['payFrom'], 1), $st['conv']['payTo']];
        $filas = [];
        foreach ($pend as $i => $r) {
            $filas[] = ['referencia' => $r['ref'], 'monto' => self::CUOTA, 'fecha' => $dias[$i % 3]];
        }
        if ($conErrores && count($filas) >= 40) {
            $ref = $filas[9]['referencia'];
            $filas[9]['referencia'] = substr($ref, 0, -1) . (((int) substr($ref, -1) + 1) % 10);
            $filas[24]['monto'] = 300;
            $filas[39]['fecha'] = self::sumarDias($st['conv']['payTo'], 3);
        }
        return self::aCSV([['referencia', 'referencia'], ['monto', 'monto'], ['fecha', 'fecha']], $filas);
    }

    /** Tabla de registros (lo que ya está en el sistema), para comparar con los otros dos archivos. */
    public static function aspirantesCSV(array $st): string
    {
        return self::aCSV([['c', 'control'], ['n', 'nombre'], ['ca', 'carrera'], ['g', 'grupo'], ['ref', 'referencia'], ['code', 'codigo']], $st['regs']);
    }

    public static function resultadosEjemploCSV(array $st, int $semilla = 7): string
    {
        $r = self::rng($semilla);
        $filas = array_map(static fn ($x) => ['control' => $x['c'], 'nivel' => self::NIVELES[(int) floor($r() * 4)]], $st['regs']);
        return self::aCSV([['control', 'control'], ['nivel', 'nivel']], $filas);
    }
}
