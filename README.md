# EduNex Systems · Prototipo en PHP

Registro automatizado de aspirantes al examen de ubicación de lengua extranjera (TecNM Campus Matehuala).
Proyecto escolar de Fundamentos de Ingeniería de Software (SCC-1007). **Todos los datos son simulados.**

## Qué es

Un sitio web hecho solo con PHP 8.3. No hay archivos `.html` ni JavaScript: PHP genera en el servidor
la página que ve el navegador. Los datos se guardan en un archivo JSON (`datos/estado.json`) y la
bitácora de auditoría en `datos/auditoria.log`.

Interfaz de portal institucional: escudo del Tec, banner con las fechas y botones redondos con ícono.

**Escudo del Tec (obligatorio):** copia el archivo `escudo-tec-matehuala.png` en la carpeta `public/`
(también sirve `.jpg` o `.svg`). Si falta, el encabezado muestra «TecNM Matehuala» y el panel avisa.

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
src/Nube.php         Conexión opcional con Power Automate por HTTPS (ver CONEXION_POWER_AUTOMATE.md)
src/Pruebas.php      Casos CP-01 a CP-29
tests/pruebas.php    Corre los casos desde la terminal
tests/pantallas.py   Pruebas con navegador: 360 px, flujo, seguridad y capturas
tests/flujo_simulado.php  Simula los flujos de Power Automate para probar la conexión sin internet
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

En Windows: si aparece `Call to undefined function mb_strtoupper()`, el prototipo ya trae un respaldo,
pero lo correcto es activar `extension=mbstring` (y `extension=curl`) en `php.ini`.

## Publicarlo en Render (lo que se usó)

1. Sube el proyecto a GitHub (sin la carpeta `datos/`).
2. En Render: **New → Web Service**, conecta el repositorio; Render detecta el `Dockerfile` y lo construye
   (al construir corre los casos de prueba: si uno falla, no se publica).
3. En **Environment** agrega `EDUNEX_PROXIES` = `*` para que la bitácora guarde la IP real y la sesión
   se marque como segura (Render atiende HTTPS con su propio certificado).
4. Opcional: `EDUNEX_PA_REGISTRO` y `EDUNEX_PA_CONSULTA` para conectar con Power Automate.

Limitaciones del plan gratuito: la instancia se «duerme» sin visitas (tarda en despertar) y no tiene
disco permanente: los datos guardados en `datos/` se pierden al reiniciar o volver a desplegar.
Con la conexión a Power Automate, los registros quedan guardados en el Excel.

## Publicarlo en otro servidor con HTTPS propio (Caddy)

1. Renta un servidor Linux con IP pública (por ejemplo AWS EC2, Azure o Google Cloud) e instala Docker.
2. Compra o consigue un dominio y crea un registro DNS tipo **A** que apunte a la IP pública.
3. Abre los puertos 80 y 443.
4. Copia esta carpeta y ejecuta: `DOMINIO=tu.dominio.com docker compose up -d --build`
5. Caddy pide el certificado a Let's Encrypt (gratis, 90 días, renovación automática) y redirige http → https.

Sin dominio, `DOMINIO=localhost docker compose up -d --build` funciona con un certificado propio que el
navegador marca como no confiable.

> Estado: el `Dockerfile` ya funcionó en Render. El `compose.yaml` con Caddy no se ha probado.

## Relación con Power Automate

La solución recomendada al Tec sigue siendo Power Automate + Excel en la cuenta institucional (sección 9 del libro).
Este prototipo aplica las mismas reglas y se puede conectar con los flujos: ver `CONEXION_POWER_AUTOMATE.md`.
