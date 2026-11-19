<?php
declare(strict_types=1);

/*
 * Almacén de datos en archivos JSON.
 *
 * ¿Por qué un archivo y no una base de datos? Para el prototipo bastan unos cientos de
 * registros, y así todo el proyecto queda en un solo lenguaje (PHP), sin SQL. El sistema
 * real guarda los datos en tablas de Excel de la cuenta institucional (Power Automate).
 *
 * ¿Qué pasa si dos personas guardan al mismo tiempo? flock() "bloquea" el archivo:
 * mientras uno escribe, el otro espera. Así no se pierde ni se mezcla información.
 */
final class Almacen
{
    private static function ruta(string $nombre): string
    {
        if (!is_dir(EDUNEX_DATOS)) {
            mkdir(EDUNEX_DATOS, 0770, true);
        }
        return EDUNEX_DATOS . '/' . $nombre . '.json';
    }

    /** Lee el contenido sin modificarlo (bloqueo compartido: varios pueden leer a la vez). */
    public static function leer(string $nombre, callable $porDefecto): array
    {
        $ruta = self::ruta($nombre);
        if (!is_file($ruta)) {
            return self::transaccion($nombre, $porDefecto, static fn (array &$d) => null);
        }
        $fp = fopen($ruta, 'rb');
        flock($fp, LOCK_SH);
        $texto = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        $d = json_decode((string) $texto, true);
        return is_array($d) ? $d : $porDefecto();
    }

    /**
     * Lee, deja que $cambio modifique los datos y los guarda, todo con el archivo bloqueado
     * (bloqueo exclusivo). Devuelve lo que regrese $cambio.
     */
    public static function transaccion(string $nombre, callable $porDefecto, callable $cambio): mixed
    {
        $fp = fopen(self::ruta($nombre), 'c+b');      // c+: crea si no existe, no lo borra
        if ($fp === false) {
            throw new RuntimeException('No se pudo abrir el almacén de datos.');
        }
        try {
            flock($fp, LOCK_EX);
            $texto = stream_get_contents($fp);
            $d = $texto !== '' ? json_decode((string) $texto, true) : null;
            if (!is_array($d)) {
                $d = $porDefecto();
            }
            $res = $cambio($d);
            $json = json_encode($d, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $json);
            fflush($fp);
            return $res ?? $d;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /** Agrega una línea al final de un archivo de texto (se usa para la bitácora). */
    public static function agregarLinea(string $archivo, string $linea): void
    {
        if (!is_dir(EDUNEX_DATOS)) {
            mkdir(EDUNEX_DATOS, 0770, true);
        }
        file_put_contents(EDUNEX_DATOS . '/' . $archivo, $linea . "\n", FILE_APPEND | LOCK_EX);
    }
}
