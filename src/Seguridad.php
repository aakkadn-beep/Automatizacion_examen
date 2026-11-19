<?php
declare(strict_types=1);

/*
 * Seguridad: sesiones, contraseñas, protección CSRF, límites de intentos,
 * cabeceras de seguridad y escape de lo que se muestra en pantalla.
 */
final class Seguridad
{
    /** ¿La petición llegó por HTTPS? Directo o a través de un proxy de confianza (Caddy). */
    public static function esHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        return self::vieneDeProxy() && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    private static function vieneDeProxy(): bool
    {
        // '*' = confiar en el proxy de la plataforma (Render, por ejemplo), cuando el contenedor
        // solo se puede alcanzar a través de ese proxy y no directo desde internet.
        return in_array('*', EDUNEX_PROXIES, true) || in_array($_SERVER['REMOTE_ADDR'] ?? '', EDUNEX_PROXIES, true);
    }

    /** IP de quien hace la petición. Solo se cree X-Forwarded-For si viene de un proxy
     *  de confianza; si no, cualquiera podría escribir una IP falsa en ese encabezado. */
    public static function ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
        if (self::vieneDeProxy() && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $primera = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
            if (filter_var($primera, FILTER_VALIDATE_IP)) {
                $ip = $primera;
            }
        }
        return $ip;
    }

    /** Cabeceras que le dicen al navegador cómo protegerse. */
    public static function cabeceras(): void
    {
        header_remove('X-Powered-By');                   // no anunciar la versión de PHP
        // CSP: solo se cargan estilos e imágenes de este mismo sitio y NO se ejecuta ningún script.
        header("Content-Security-Policy: default-src 'none'; style-src 'self'; img-src 'self' data:; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
        header('X-Content-Type-Options: nosniff');       // no "adivinar" el tipo de archivo
        header('X-Frame-Options: DENY');                  // no se puede meter en un iframe ajeno
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        if (self::esHttps()) {
            // HSTS: durante 6 meses el navegador solo entrará a este sitio por HTTPS.
            header('Strict-Transport-Security: max-age=15552000');
        }
    }

    public static function iniciarSesion(): void
    {
        session_name('EDUNEXSESS');
        session_set_cookie_params([
            'lifetime' => 0,               // la cookie se borra al cerrar el navegador
            'path' => '/',
            'secure' => self::esHttps(),   // con HTTPS, la cookie nunca viaja sin cifrar
            'httponly' => true,            // ningún script puede leer la cookie
            'samesite' => 'Strict',        // otro sitio no puede usar tu sesión
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
        // Cierra la sesión de Coordinación o caja si pasó mucho tiempo sin actividad.
        if (isset($_SESSION['rol'], $_SESSION['ultimo']) && time() - $_SESSION['ultimo'] > EDUNEX_SESION_MIN * 60) {
            Auditoria::registrar('sesion_expirada', 'ok', $_SESSION['usuario'] ?? '');
            unset($_SESSION['rol'], $_SESSION['usuario']);
        }
        $_SESSION['ultimo'] = time();
    }

    public static function rol(): ?string
    {
        return $_SESSION['rol'] ?? null;
    }

    public static function usuario(): string
    {
        return $_SESSION['usuario'] ?? 'anónimo';
    }

    /** Verifica usuario y contraseña. Devuelve el rol o null. */
    public static function entrar(string $usuario, string $clave): ?string
    {
        $u = EDUNEX_USUARIOS[strtolower(trim($usuario))] ?? null;
        // Si el usuario no existe se verifica contra un hash falso: así tarda lo mismo
        // y no se puede saber qué usuarios existen midiendo el tiempo de respuesta.
        $hash = $u['hash'] ?? '$2y$10$psC7BP/5TTe7sYecRjnbVuyPcpeet7qIwvc2TpvIjOKaCTfsNp9oa';
        if (!password_verify($clave, $hash) || $u === null) {
            return null;
        }
        session_regenerate_id(true);       // nueva sesión al entrar: evita "fijación de sesión"
        $_SESSION['rol'] = $u['rol'];
        $_SESSION['usuario'] = strtolower(trim($usuario));
        return $u['rol'];
    }

    public static function salir(): void
    {
        unset($_SESSION['rol'], $_SESSION['usuario'], $_SESSION['prev'], $_SESSION['siguiente'], $_SESSION['motivo']);
        session_regenerate_id(true);
    }

    /* ---------- CSRF: un "boleto" secreto por sesión que cada formulario debe devolver ---------- */

    public static function tokenCsrf(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function csrfValido(?string $t): bool
    {
        return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
    }

    /* ---------- límite de intentos fallidos (contra fuerza bruta) ---------- */

    public static function bloqueado(string $clave, int $max): bool
    {
        $d = Almacen::leer('intentos', static fn () => []);
        $x = $d[$clave] ?? null;
        return $x !== null && $x['n'] >= $max && time() - $x['t'] < EDUNEX_BLOQUEO_MIN * 60;
    }

    public static function fallo(string $clave): void
    {
        Almacen::transaccion('intentos', static fn () => [], static function (array &$d) use ($clave) {
            $x = $d[$clave] ?? ['n' => 0, 't' => time()];
            if (time() - $x['t'] >= EDUNEX_BLOQUEO_MIN * 60) {
                $x = ['n' => 0, 't' => time()];
            }
            $x['n']++;
            $x['t'] = time();
            $d[$clave] = $x;
        });
    }

    public static function limpiarIntentos(string $clave): void
    {
        Almacen::transaccion('intentos', static fn () => [], static function (array &$d) use ($clave) {
            unset($d[$clave]);
        });
    }
}

/** Escapa texto antes de ponerlo en la página: evita que alguien inyecte código (XSS). */
function e(mixed $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
