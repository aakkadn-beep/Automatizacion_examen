# EduNex Systems · Prototipo en PHP

Registro automatizado de aspirantes al examen de ubicación de lengua extranjera (TecNM Campus Matehuala).
Proyecto escolar de Fundamentos de Ingeniería de Software (SCC-1007). **Todos los datos son simulados.**

## Qué es

Un sitio web hecho solo con PHP 8.3. No hay archivos `.html` ni JavaScript: PHP genera en el servidor
la página que ve el navegador. Los datos se guardan en un archivo JSON (`datos/estado.json`) y la
bitácora de auditoría en `datos/auditoria.log`.

| Pantalla | Quién | Requisitos |
|---|---|---|
| Registro | Aspirante | RF-01 a RF-06, RF-12, RF-21, RNF-01 a RNF-05, RN-02, RN-04, RN-08 |
| Consulta de estatus | Aspirante (control + código) | RF-13, RF-17, RNF-05, RNF-06 |
| Panel | Coordinación | RF-05, RF-07 a RF-11, RF-14, RF-16, RF-18 a RF-20 |
| Reporte para caja | Caja y Coordinación | RF-15 |
| Bitácora | Coordinación | Auditoría: quién, qué, cuándo y desde qué IP |
| Pruebas | Cualquiera | Corre CP-01 a CP-29 en el servidor |

Usuarios de demostración: `coordinador` / `edunex2026` y `caja` / `caja2026`.

## Archivos

```
public/index.php     Única puerta de entrada (la única carpeta que ve el navegador)
src/Nucleo.php       Reglas del negocio: validación, referencia, cruce de pagos, resultados, listas
src/Seguridad.php    Sesiones, contraseñas con hash, CSRF, límite de intentos, cabeceras
src/Auditoria.php    Bitácora de auditoría
src/Almacen.php      Guardar y leer datos con bloqueo de archivo
src/Vistas.php       Pantallas y estilos (generados por PHP)
src/Pruebas.php      Casos CP-01 a CP-29
tests/pruebas.php    Corre los casos desde la terminal
tests/pantallas.py   Pruebas con navegador: 360 px, flujo, seguridad y capturas
data/                CSV de ejemplo (60 aspirantes, 60 pagos, 60 resultados)
Dockerfile, compose.yaml, deploy/Caddyfile   Publicación en la nube con HTTPS
```

## Probarlo en una computadora

```bash
php -S 127.0.0.1:8080 -t public      # abre http://127.0.0.1:8080
php tests/pruebas.php --csv          # casos de prueba
```

`127.0.0.1` es la dirección de "esta misma computadora" (loopback): nadie de fuera puede entrar.
Sirve para desarrollar, no para que lo usen los aspirantes.

## Publicarlo en la nube con HTTPS

1. Renta un servidor Linux con IP pública (por ejemplo AWS EC2, Azure o Google Cloud) e instala Docker.
2. Compra o consigue un dominio y crea un registro DNS tipo **A** que apunte a la IP pública.
3. Abre los puertos 80 y 443.
4. Copia esta carpeta y ejecuta: `DOMINIO=tu.dominio.com docker compose up -d --build`
5. Caddy pide el certificado a Let's Encrypt (gratis, 90 días, renovación automática) y redirige http → https.

Sin dominio, `DOMINIO=localhost docker compose up -d --build` funciona con un certificado propio que el
navegador marca como no confiable.

> Estado: el Dockerfile y el compose **no se han probado** (en el equipo donde se preparó no había Docker),
> y no hay un despliegue real ni un certificado real todavía.

## Relación con Power Automate

La solución recomendada al Tec sigue siendo Power Automate + Excel en la cuenta institucional (sección 9 del libro).
Este prototipo aplica las mismas reglas para poder demostrarlas y probarlas.
