"""
Pruebas de pantalla y de seguridad con un navegador real (Chromium, sin ventana).

Requisitos: el prototipo corriendo (por ejemplo `php -S 127.0.0.1:8080 -t public`)
y Python con Playwright (`pip install playwright` y `playwright install chromium`).

    python3 tests/pantallas.py http://127.0.0.1:8080 capturas/

Mide CP-23 (360 px sin desplazamiento horizontal), recorre el flujo completo,
revisa la protección CSRF, el acceso por rol, el escape de texto (XSS), el bloqueo
por intentos fallidos y la bitácora, y guarda capturas para el Anexo J.
"""
import os
import re
import sys
import urllib.error
import urllib.request

from playwright.sync_api import sync_playwright

BASE = (sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8080").rstrip("/")
OUT = sys.argv[2] if len(sys.argv) > 2 else "capturas"
os.makedirs(OUT, exist_ok=True)
res = []


def caso(nombre, ok, detalle):
    res.append((nombre, "Pasa" if ok else "Falla", detalle))
    print(f"{'Pasa ' if ok else 'FALLA'} | {nombre} | {detalle}")


def foto(page, nombre):
    page.screenshot(path=os.path.join(OUT, nombre + ".png"), full_page=True)


def ancho(page):
    return page.evaluate("document.documentElement.scrollWidth"), page.evaluate("window.innerWidth")


def entrar(page, usuario, clave):
    page.goto(BASE + "/?r=acceso")
    page.fill("#lu", usuario)
    page.fill("#lp", clave)
    page.click("form[action='?r=entrar'] button")


with sync_playwright() as p:
    nav = p.chromium.launch()

    # ---------- celular de 360 px (CP-23) ----------
    cel = nav.new_context(viewport={"width": 360, "height": 780}, device_scale_factor=2, is_mobile=True, has_touch=True)
    page = cel.new_page()
    for vista in ["inicio", "registro", "consulta", "ayuda", "acceso"]:
        page.goto(f"{BASE}/?r={vista}")
        w, v = ancho(page)
        caso(f"CP-23 · {vista} a 360 px", w <= v, f"ancho del contenido {w} px de {v} px")
        foto(page, f"cel_{vista}")

    # Flujo del aspirante en el celular
    page.goto(BASE + "/?r=registro")
    page.click("#f button[type=submit]")
    errores = page.locator(".err").count()
    caso("Registro vacío muestra errores por campo", errores >= 4, f"{errores} mensajes de error")
    foto(page, "cel_registro_errores")

    page.fill("#control", "29990001")
    page.fill("#nombre", "Ana Prueba Rivas")
    page.select_option("#carrera", "Ing. en Sistemas Computacionales")
    page.select_option("#grupo", "A")
    page.click("#f button[type=submit]")
    codigo = page.inner_text("#codigo").strip()
    caso("Registro válido entrega referencia y código", bool(re.fullmatch(r"[A-Z2-9]{4}", codigo)), f"código {codigo}")
    foto(page, "cel_registro_listo")
    w, v = ancho(page)
    caso("CP-23 · confirmación de registro a 360 px", w <= v, f"{w} de {v} px")

    page.goto(BASE + "/?r=registro")
    page.fill("#control", "29990001")
    page.fill("#nombre", "Ana Prueba Rivas")
    page.select_option("#carrera", "Ing. en Sistemas Computacionales")
    page.select_option("#grupo", "A")
    page.click("#f button[type=submit]")
    caso("Duplicado avisa sin guardar", "ya está registrado" in page.content(), "mensaje de duplicado visible")

    page.goto(BASE + "/?r=consulta")
    page.fill("#qc", "29990001")
    page.fill("#qk", codigo)
    page.click("#q button")
    txt = page.inner_text("#resultado")
    caso("Consulta con código correcto", "Registrado" in txt, txt.replace("\n", " ")[:80])
    foto(page, "cel_consulta_resultado")
    page.fill("#qk", "ZZZZ" if codigo != "ZZZZ" else "YYYY")
    page.click("#q button")
    caso("Consulta con código incorrecto no muestra datos", page.locator("#resultado").count() == 0 and "No encontramos" in page.content(), "sin resultado")

    # XSS: un nombre con etiquetas se muestra como texto, no se ejecuta
    page.goto(BASE + "/?r=registro")
    page.fill("#control", "29990002")
    page.fill("#nombre", "<b>Hola</b> <img src=x onerror=alert(1)> Rivas")
    page.select_option("#carrera", "Ing. Civil")
    page.select_option("#grupo", "B")
    page.click("#f button[type=submit]")
    cel.close()

    # ---------- computadora ----------
    pc = nav.new_context(viewport={"width": 1280, "height": 900})
    page = pc.new_page()
    page.goto(BASE + "/?r=inicio")
    foto(page, "pc_inicio")

    page.goto(BASE + "/?r=panel")
    caso("Panel sin sesión redirige al acceso", "?r=acceso" in page.url, page.url)

    entrar(page, "coordinador", "mala")
    caso("Contraseña incorrecta se rechaza", "incorrectos" in page.content(), "mensaje visible")

    entrar(page, "caja", "caja2026")
    caso("Caja entra a su reporte", "Reporte para caja" in page.inner_text("h1"), page.url)
    foto(page, "pc_caja")
    page.goto(BASE + "/?r=panel")
    caso("Caja NO puede ver el panel", "?r=acceso" in page.url, page.url)
    page.goto(BASE + "/?r=auditoria")
    caso("Caja NO puede ver la bitácora", "?r=acceso" in page.url, page.url)
    page.click("form[action='?r=salir'] button")

    entrar(page, "coordinador", "edunex2026")
    caso("Coordinador entra al panel", "Panel del Coordinador" in page.content(), page.url)
    xss = page.locator("img[src=x]").count() + page.locator("td b:text('Hola')").count()
    caso("XSS: el nombre con etiquetas se muestra como texto", xss == 0 and "&lt;b&gt;Hola" in page.content(), "0 etiquetas inyectadas")
    page.click("button[value=ejemplo] >> nth=0")
    msg = page.inner_text("body")
    m = re.search(r"Reporte de (\d+) pagos: (\d+) quedaron «pagado» sin intervención y (\d+)", msg)
    caso("Cruce de pagos de ejemplo desde la pantalla", bool(m), m.group(0) if m else "sin mensaje")
    foto(page, "pc_panel")
    page.click("button[value=ejemplo] >> nth=1")
    page.click("form[action='?r=publicar'] button")
    caso("Publicar niveles", "Niveles publicados" in page.content(), "mensaje visible")

    page.goto(BASE + "/?r=panel&q=29990001")
    caso("Búsqueda por control en el panel", "Ana Prueba Rivas" in page.content(), "1 resultado")

    page.goto(BASE + "/?r=auditoria")
    filas = page.locator("tbody tr").count()
    contenido = page.inner_text("tbody")
    caso("Bitácora registra quién, cuándo y desde dónde", filas >= 10 and "inicio_sesion" in contenido and "127.0.0.1" in contenido, f"{filas} eventos")
    foto(page, "pc_bitacora")

    page.goto(BASE + "/?r=pruebas")
    resumen = page.inner_text(".okmsg")
    caso("Pantalla de pruebas", "0 fallan" in resumen, resumen)
    foto(page, "pc_pruebas")

    # panel en tableta
    tab = nav.new_context(viewport={"width": 768, "height": 1024})
    tp = tab.new_page()
    tp.goto(BASE + "/?r=registro")
    w, v = ancho(tp)
    caso("Registro en tableta (768 px)", w <= v, f"{w} de {v} px")
    foto(tp, "tab_registro")
    tab.close()
    pc.close()
    nav.close()

# ---------- sin navegador: CSRF y bloqueo por intentos ----------
def post(ruta, datos, cookie=""):
    req = urllib.request.Request(BASE + ruta, data=urllib.parse.urlencode(datos).encode(), method="POST")
    if cookie:
        req.add_header("Cookie", cookie)
    class NoRedir(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, *a, **k):
            return None
    op = urllib.request.build_opener(NoRedir)
    try:
        r = op.open(req)
        return r.status, r.read().decode()
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode()

import urllib.parse  # noqa: E402

st, cuerpo = post("/?r=registrar", {"control": "29990003", "nombre": "Sin Token Prueba", "carrera": "Ing. Civil", "grupo": "A"})
caso("CSRF: formulario sin token se rechaza", st == 400 and "venció" in cuerpo, f"HTTP {st}")

# 5 intentos fallidos de un usuario → bloqueo, aunque después ponga la contraseña correcta
op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor())
html = op.open(BASE + "/?r=acceso").read().decode()
tok = re.search(r'name="csrf" value="([0-9a-f]+)"', html).group(1)
for _ in range(5):
    op.open(urllib.request.Request(BASE + "/?r=entrar", data=urllib.parse.urlencode({"csrf": tok, "usuario": "caja", "clave": "x"}).encode()))
r = op.open(urllib.request.Request(BASE + "/?r=entrar", data=urllib.parse.urlencode({"csrf": tok, "usuario": "caja", "clave": "caja2026"}).encode()))
caso("Bloqueo tras 5 intentos fallidos", "Demasiados intentos" in r.read().decode(), "usuario caja bloqueado 15 min")

hdr = urllib.request.urlopen(BASE + "/?r=inicio").headers
caso("Cabeceras de seguridad", "script-src" not in hdr.get("Content-Security-Policy", "x") and hdr.get("X-Frame-Options") == "DENY" and hdr.get("X-Powered-By") is None,
     "CSP sin scripts, X-Frame-Options DENY, sin X-Powered-By")

pasan = sum(1 for r in res if r[1] == "Pasa")
print(f"\n{len(res)} verificaciones: {pasan} pasan, {len(res) - pasan} fallan. Capturas en {OUT}/")
with open(os.path.join(OUT, "resultados_pantallas.csv"), "w", encoding="utf-8-sig") as f:
    f.write("Verificación,Estado,Detalle\n")
    for n, e, d in res:
        f.write('"{}","{}","{}"\n'.format(n.replace('"', '""'), e, d.replace('"', '""')))
sys.exit(0 if pasan == len(res) else 1)
