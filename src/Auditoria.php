<?php
declare(strict_types=1);

/*
 * Bitácora de auditoría: quién hizo qué, cuándo y desde dónde.
 *
 * Cada evento es una línea JSON en datos/auditoria.log. Solo se agregan líneas al final
 * (nunca se editan), para que sirva como evidencia. No guarda contraseñas ni códigos de
 * consulta; del aspirante solo guarda su número de control.
 */
final class Auditoria
{
    private const ARCHIVO = 'auditoria.log';

    public static function registrar(string $accion, string $resultado, string $detalle = ''): void
    {
        $linea = [
            'fecha' => date('Y-m-d H:i:s'),                      // hora de México (config.php)
            'usuario' => Seguridad::usuario(),
            'rol' => Seguridad::rol() ?? 'público',
            'accion' => $accion,
            'resultado' => $resultado,
            'detalle' => mb_substr($detalle, 0, 200),
            'ip' => Seguridad::ip(),
            'navegador' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'línea de comandos', 0, 160),
            'https' => Seguridad::esHttps() ? 'sí' : 'no',
        ];
        Almacen::agregarLinea(self::ARCHIVO, json_encode($linea, JSON_UNESCAPED_UNICODE));
    }

    /** Últimos $n eventos, del más reciente al más antiguo. */
    public static function ultimos(int $n = 200): array
    {
        $ruta = EDUNEX_DATOS . '/' . self::ARCHIVO;
        if (!is_file($ruta)) {
            return [];
        }
        $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $sal = [];
        foreach (array_reverse(array_slice($lineas, -$n)) as $l) {
            $d = json_decode($l, true);
            if (is_array($d)) {
                $sal[] = $d;
            }
        }
        return $sal;
    }

    public const COLS = [['fecha', 'Fecha y hora'], ['usuario', 'Usuario'], ['rol', 'Rol'], ['accion', 'Acción'],
        ['resultado', 'Resultado'], ['detalle', 'Detalle'], ['ip', 'IP'], ['navegador', 'Navegador'], ['https', 'HTTPS']];
}
