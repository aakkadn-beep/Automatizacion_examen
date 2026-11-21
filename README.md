# EduNex Systems · Prototipo en PHP

Registro automatizado de aspirantes al examen de ubicación de lengua extranjera (TecNM Campus Matehuala).
Proyecto escolar de Fundamentos de Ingeniería de Software (SCC-1007). **Todos los datos son simulados.**

## Prototipo
https://automatizacion-examen.onrender.com

## Qué es

Un sitio web hecho solo con PHP 8.3. No hay archivos `.html` ni JavaScript: PHP genera en el servidor
la página que ve el navegador. Los datos se guardan en un archivo JSON (`datos/estado.json`) y la
bitácora de auditoría en `datos/auditoria.log`.

Interfaz de portal institucional: escudo del Tec, banner con las fechas y botones redondos con ícono.

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
Documentos de la práctica (E1 a libro final): carpeta documentos/

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

## Publicarlo en Render (lo que se usó)

1. Se sube el proyecto a GitHub.
2. En Render: **New → Web Service**, conecta el repositorio; Render detecta el `Dockerfile` y lo construye
   (al construir corre los casos de prueba: si uno falla, no se publica).
3. En **Environment** agrega `EDUNEX_PROXIES` = `*` para que la bitácora guarde la IP real y la sesión
   se marque como segura (Render atiende HTTPS con su propio certificado).
4. Se uso `EDUNEX_PA_REGISTRO` y `EDUNEX_PA_CONSULTA` para conectar con Power Automate.

Limitaciones del plan gratuito: la instancia se «duerme» sin visitas (tarda en despertar) y no tiene
disco permanente: los datos guardados en `datos/` se pierden al reiniciar o volver a desplegar.
Con la conexión a Power Automate, los registros quedan guardados en el Excel.

## Relación con Power Automate

La solución recomendada al Tec sigue siendo Power Automate + Excel en la cuenta institucional (sección 9 del libro).
Este prototipo aplica las mismas reglas y se puede conectar con los flujos: ver `CONEXION_POWER_AUTOMATE.md`.
