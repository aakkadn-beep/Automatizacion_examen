<?php
declare(strict_types=1);

/*
 * Conexión con Power Automate (opcional).
 *
 * Aquí PHP actúa como CLIENTE: le manda una petición HTTPS al flujo de Power Automate
 * (disparador «Cuando se recibe una solicitud HTTP») y lee la respuesta en JSON.
 * Así el prototipo usa los flujos y el Excel de la cuenta institucional:
 *   - Flujo A (registro): cada registro nuevo se agrega como fila en la tabla «Registros» del Excel.
 *   - Flujo B (consulta): busca en el Excel por número de control y código y devuelve la fila.
 *   - Flujo C (cruce de pagos): marca «pagado» en el Excel. Si su disparador es HTTP
 *     (variable EDUNEX_PA_CRUCE), el panel lo puede ejecutar con un botón.
 *   - «Traer registros del Excel»: el panel le pide al flujo B la tabla completa (control vacío)
 *     y reemplaza su copia local; así ve lo mismo que el Excel, incluidos los borrados.
 *
 * Las direcciones de los flujos se ponen en variables de entorno (en Render: Environment):
 *   EDUNEX_PA_REGISTRO   y   EDUNEX_PA_CONSULTA
 * La dirección lleva una firma secreta (sig=...): se trata como una contraseña y NUNCA se sube a GitHub.
 * Si las variables están vacías, el prototipo trabaja solo con sus datos (como antes).
 */
final class Nube
{
    private const VARIABLES = ['registro' => 'EDUNEX_PA_REGISTRO', 'consulta' => 'EDUNEX_PA_CONSULTA', 'cruce' => 'EDUNEX_PA_CRUCE'];

    /** ¿Hay al menos un flujo configurado? Entonces el Excel es la fuente de los datos. */
    public static function conectado(): bool
    {
        return self::activa('registro') || self::activa('consulta');
    }

    /** Todas las filas que regresó un flujo (para traer la tabla Registros completa). */
    public static function filas(mixed $d): array
    {
        if (is_array($d) && isset($d['value']) && is_array($d['value'])) {
            $d = $d['value'];
        }
        if (!is_array($d)) {
            return [];
        }
        if (!array_is_list($d)) {
            $d = [$d];
        }
        $out = [];
        foreach ($d as $x) {
            $f = self::primeraFila($x);
            if ($f !== null) {
                $out[] = $f;
            }
        }
        return $out;
    }

    private static function url(string $flujo): string
    {
        return trim((string) getenv(self::VARIABLES[$flujo] ?? ''));
    }

    public static function activa(string $flujo): bool
    {
        return self::url($flujo) !== '';
    }

    /**
     * Envía $datos como JSON por POST al flujo y devuelve:
     *   ok (respuesta 200 a 299), http (código), datos (JSON decodificado), ms (tiempo), error (texto).
     */
    public static function enviar(string $flujo, array $datos): array
    {
        $url = self::url($flujo);
        if ($url === '') {
            return ['ok' => false, 'http' => 0, 'datos' => null, 'ms' => 0.0, 'error' => 'Flujo no configurado'];
        }
        $esHttps = str_starts_with(strtolower($url), 'https://');
        // Solo se permite http:// para pruebas locales con un flujo simulado (nunca en la nube).
        if (!$esHttps && getenv('EDUNEX_PA_PERMITIR_HTTP') !== '1') {
            return ['ok' => false, 'http' => 0, 'datos' => null, 'ms' => 0.0, 'error' => 'La dirección del flujo debe empezar con https://'];
        }
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'http' => 0, 'datos' => null, 'ms' => 0.0, 'error' => 'Falta la extensión curl de PHP (en Windows: quitar el ; de extension=curl en php.ini)'];
        }
        $a = hrtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($datos, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,              // segundos para conectarse
            CURLOPT_TIMEOUT => 30,                    // segundos máximos de espera (Excel a veces tarda)
            CURLOPT_SSL_VERIFYPEER => true,           // revisa que el certificado de Microsoft sea válido…
            CURLOPT_SSL_VERIFYHOST => 2,              // …y que sea para ese dominio
            CURLOPT_PROTOCOLS => $esHttps ? CURLPROTO_HTTPS : (CURLPROTO_HTTP | CURLPROTO_HTTPS),
        ]);
        $cuerpo = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = $cuerpo === false ? curl_error($ch) : '';
        curl_close($ch);
        $ms = (hrtime(true) - $a) / 1e6;
        $json = is_string($cuerpo) && $cuerpo !== '' ? json_decode($cuerpo, true) : null;
        return ['ok' => $cuerpo !== false && $http >= 200 && $http < 300, 'http' => $http, 'datos' => $json, 'ms' => $ms,
            'error' => $error !== '' ? $error : ($http >= 300 ? 'Respuesta HTTP ' . $http : '')];
    }

    /**
     * La acción «Enumerar filas» de Excel devuelve {"value":[{...}]}; otros flujos devuelven
     * la fila sola o una lista. Esta función toma la primera fila sin importar la forma y
     * deja los nombres de columna en minúsculas y sin acentos.
     */
    public static function primeraFila(mixed $d): ?array
    {
        if (is_array($d) && isset($d['value']) && is_array($d['value'])) {
            $d = $d['value'];
        }
        if (is_array($d) && array_is_list($d)) {
            $d = $d[0] ?? null;
        }
        if (!is_array($d) || $d === []) {
            return null;
        }
        $fila = [];
        foreach ($d as $k => $v) {
            $k = strtr(mb_strtolower(trim((string) $k)), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
            $k = self::ALIAS[$k] ?? $k;
            $fila[$k] = is_scalar($v) ? trim((string) $v) : '';
        }
        if (isset($fila['control'])) {
            $fila['control'] = self::control($fila['control']);
        }
        return $fila;
    }

    /** Otros nombres de columna que puede tener la tabla del Excel (ya en minúsculas y sin acentos). */
    private const ALIAS = [
        'numero de control' => 'control', 'no. de control' => 'control', 'no de control' => 'control',
        'num. de control' => 'control', 'numero_de_control' => 'control', 'numerodecontrol' => 'control', 'no_control' => 'control',
        'codigo de consulta' => 'codigo', 'codigo_de_consulta' => 'codigo',
        'fecha de registro' => 'fecha_registro', 'fecha registro' => 'fecha_registro',
        'nombre completo' => 'nombre', 'estado' => 'estatus',
    ];

    /** Deja el número de control como 8 dígitos aunque Excel lo mande como número (24660001.0). */
    public static function control(string $v): string
    {
        $v = trim($v);
        if (preg_match('/^(\d+)(\.0+)?$/', $v, $m)) {
            return $m[1];
        }
        return preg_replace('/\D/', '', $v);
    }

    /**
     * Busca, entre todas las filas que regresó el flujo, la del número de control pedido.
     * Si hay más de una (registros repetidos), prefiere la que tenga ese código de consulta.
     * Así funciona aunque el flujo B regrese la tabla completa sin filtrar.
     */
    public static function filaDe(mixed $d, string $control, string $codigo = ''): ?array
    {
        $hallazgos = array_values(array_filter(self::filas($d), static fn ($f) => ($f['control'] ?? '') === $control));
        if (!$hallazgos) {
            return null;
        }
        if ($codigo !== '') {
            foreach ($hallazgos as $f) {
                if (isset($f['codigo']) && hash_equals(strtoupper($f['codigo']), strtoupper($codigo))) {
                    return $f;
                }
            }
        }
        return $hallazgos[0];
    }

    /** Arma lo que se le manda al flujo A con cada registro nuevo (una fila del Excel). */
    public static function filaRegistro(array $r): array
    {
        return ['control' => $r['c'], 'nombre' => $r['n'], 'carrera' => $r['ca'], 'grupo' => $r['g'],
            'referencia' => $r['ref'], 'concepto' => $r['con'], 'codigo' => $r['code'], 'estatus' => $r['st'],
            'fecha_registro' => $r['fReg'], 'asistido' => $r['asistido'] ? 'sí' : 'no'];
    }
}
