# Conexión del prototipo en PHP con Power Automate

El prototipo puede trabajar solo (con sus propios datos) o conectado a los flujos de Power Automate
y al Excel de la cuenta institucional. Conectado, queda así:

```
Aspirante ──► Prototipo en PHP (pantallas, validación, seguridad, bitácora)
                 │  HTTPS (PHP es el cliente; curl revisa el certificado de Microsoft)
                 ▼
          Power Automate ──► Excel en OneDrive (tabla Registros)
             Flujo A: registro  → agrega una fila
             Flujo B: consulta  → busca la fila por número de control
             Flujo C: cruce de pagos (el que ya tienes) → marca «pagado»
```

**Con la conexión activa, el Excel manda.** El panel ya no arranca con los 60 aspirantes inventados:
al abrirlo trae la tabla `Registros` completa del Excel (botón «Traer registros del Excel»). Si borras
o cambias filas en el Excel, pulsa ese botón y el panel queda igual que el Excel. Caja siempre ve lo
que dice el Excel. El cruce de pagos y las excepciones del panel quedan como **simulación** (no
cambian el Excel); el cruce real es el flujo C.

Por qué así: Power Automate y Excel son la solución recomendada al Tec (cuenta institucional, sin
costo extra, el Coordinador ya usa Excel). PHP pone las pantallas, valida y protege. Además, en un
hosting gratuito sin disco permanente (como Render gratis), los datos del servidor se borran al
reiniciar; con la conexión, los registros quedan guardados en el Excel.

> Estado: la conexión se probó con un **simulador** de los flujos (`tests/flujo_simulado.php`).
> Falta probarla con tus flujos reales. Falta confirmar con TI del Tec que la licencia permita el
> disparador HTTP (en tu prueba T3 corrió sin aviso de licencia premium).

## 1. La tabla en Excel

En el archivo de Excel de OneDrive, la tabla **Registros** debe tener estas columnas
(se pueden escribir con mayúscula o minúscula):

`control, nombre, carrera, grupo, referencia, concepto, codigo, estatus, nivel, fecha_registro, asistido`

El flujo C (cruce de pagos) escribe `pagado` en la columna `estatus`. Si se publica el resultado del
examen en el Excel, va en la columna `nivel`.

## 2. Flujo A · Registro

1. Power Automate → **Crear** → **Flujo de nube instantáneo** → disparador
   **Cuando se recibe una solicitud HTTP**.
2. En «Esquema JSON del cuerpo de la solicitud» pega:

```json
{"type":"object","properties":{
 "control":{"type":"string"},"nombre":{"type":"string"},"carrera":{"type":"string"},
 "grupo":{"type":"string"},"referencia":{"type":"string"},"concepto":{"type":"string"},
 "codigo":{"type":"string"},"estatus":{"type":"string"},"fecha_registro":{"type":"string"},
 "asistido":{"type":"string"}}}
```

3. Acción **Excel Online (Business) → Agregar una fila a una tabla**: ubicación OneDrive, tu archivo,
   tabla `Registros`. En cada columna elige el dato del mismo nombre del disparador.
4. Acción **Respuesta**: código de estado `200`, cuerpo `{"ok": true}`.
5. Guarda. En el disparador aparece la **dirección HTTP POST**: cópiala.

## 3. Flujo B · Consulta (es tu flujo 1, con un ajuste)

1. Disparador **Cuando se recibe una solicitud HTTP** con este esquema:

```json
{"type":"object","properties":{"control":{"type":"string"},"codigo":{"type":"string"}}}
```

2. Acción **Enumerar las filas presentes en una tabla** (tabla `Registros`). En «Consulta de filtro»
   pon esta **expresión** (pestaña «Expresión» o fx), para que con control vacío regrese **toda la tabla**:

   ```
   if(empty(triggerBody()?['control']), '', concat('control eq ''', triggerBody()?['control'], ''''))
   ```

   Si Power Automate no acepta el filtro vacío, usa una **Condición**: «control está vacío» →
   en «Sí», *Enumerar las filas* sin filtro y *Respuesta* con su `value`; en «No», *Enumerar las filas*
   con `control eq '...'` y *Respuesta* con su `value`.
3. Acción **Respuesta**: código `200`, encabezado `Content-Type: application/json`, cuerpo:
   el `value` de la acción anterior (la lista de filas).
4. Guarda y copia la dirección HTTP POST.

El prototipo compara el **código de consulta** con la columna `codigo` de la fila que regresa el flujo:
si no coincide, no muestra nada. Por eso la columna `codigo` es obligatoria.

## 3 bis. Flujo C · Cruce de pagos desde el panel (opcional)

Tu flujo C cruza la tabla de pagos con `Registros` y marca `pagado`. Para ejecutarlo con un botón
del panel, cambia su disparador por **Cuando se recibe una solicitud HTTP** (sin esquema), agrega al
final una acción **Respuesta** con código `200`, guarda y copia la dirección. Ponla en la variable
`EDUNEX_PA_CRUCE`. Al pulsar «Ejecutar cruce de pagos (flujo C)», el panel lo ejecuta y luego trae los
registros del Excel ya actualizados. Si prefieres dejar el disparador manual, ejecútalo en Power
Automate y después pulsa «Traer registros del Excel».

## 4. Poner las direcciones en el servidor (nunca en GitHub)

La dirección del flujo lleva una firma secreta (`sig=...`). Quien la tenga puede usar el flujo, así
que se trata como una contraseña: **no se escribe en el código ni se sube a GitHub**.

En Render: tu servicio → **Environment** → **Add Environment Variable**:

| Variable | Valor |
|---|---|
| `EDUNEX_PA_REGISTRO` | la dirección HTTP POST del flujo A |
| `EDUNEX_PA_CONSULTA` | la dirección HTTP POST del flujo B |
| `EDUNEX_PA_CRUCE` | opcional: la dirección HTTP POST del flujo C |
| `EDUNEX_PROXIES` | `*` (Render atiende HTTPS y le pasa la petición al contenedor) |

Guarda y Render vuelve a desplegar. En tu computadora (PowerShell), antes de `php -S`:

```powershell
$env:EDUNEX_PA_REGISTRO="https://...";  $env:EDUNEX_PA_CONSULTA="https://..."
php -S 127.0.0.1:8080 -t public
```

En Windows, si sale un error de curl, activa `extension=curl` en `php.ini` (igual que mbstring).

## 5. Cómo comprobar que funciona

1. En el panel, sección **7. Conexión con Power Automate**, los dos flujos deben decir «Conectado».
2. Regístrate con datos inventados: debe aparecer una fila nueva en la tabla `Registros` del Excel.
3. Corre tu flujo C con un pago de esa referencia (o cambia `estatus` a `pagado` a mano en el Excel).
4. Consulta tu estatus en el prototipo: debe decir **Pagado**, aunque el prototipo no haya cruzado nada.
5. Revisa la **Bitácora**: aparecen `power_automate_registro` y `power_automate_consulta` con el
   código HTTP (200) y el tiempo de respuesta.

Si un flujo no responde, el prototipo usa su propia copia de los datos y lo anota en la bitácora;
los registros que no llegaron al Excel se reenvían con el botón **Reenviar registros pendientes**.
