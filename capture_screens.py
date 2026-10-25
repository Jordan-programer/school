import os
import time
from playwright.sync_api import sync_playwright

SCREENSHOTS_DIR = r"c:\SisGestEsc\screenshots"
os.makedirs(SCREENSHOTS_DIR, exist_ok=True)

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(
            executable_path=r"C:\Program Files\Google\Chrome\Application\chrome.exe",
            headless=True,
            args=["--no-sandbox", "--disable-setuid-sandbox"]
        )
        context = browser.new_context(viewport={"width": 1440, "height": 900})
        page = context.new_page()

        print("1. Capturando Ecrã de Login...")
        page.goto("http://localhost:5174/login")
        page.wait_for_selector("text=República de Angola")
        page.wait_for_timeout(1000)
        page.screenshot(path=os.path.join(SCREENSHOTS_DIR, "01_login.png"))

        print("2. Clicando no perfil rápido Director Geral e autenticando...")
        page.click("text=Director Geral")
        page.wait_for_timeout(500)
        page.click("text=Entrar no Sistema")
        
        # Aguarda redirecionamento para o dashboard
        page.wait_for_url("**/dashboard", timeout=10000)
        page.wait_for_selector("text=Dr. Manuel Domingos António", timeout=10000)
        page.wait_for_timeout(1500)

        print("3. Capturando Módulo 1: Utilizadores & Perfis...")
        page.screenshot(path=os.path.join(SCREENSHOTS_DIR, "02_utilizadores.png"))

        print("4. Navegando para Módulo 2: Estrutura Escolar & Turmas...")
        page.click("text=Estrutura Escolar & Turmas")
        page.wait_for_url("**/estrutura", timeout=10000)
        page.wait_for_timeout(2000)
        page.screenshot(path=os.path.join(SCREENSHOTS_DIR, "03_estrutura_escolar.png"))

        print("5. Navegando para Módulo 3: Alunos & Encarregados...")
        page.click("text=Alunos & Encarregados")
        page.wait_for_url("**/alunos", timeout=10000)
        page.wait_for_timeout(2000)
        page.screenshot(path=os.path.join(SCREENSHOTS_DIR, "04_alunos_encarregados.png"))

        print("6. Navegando para Módulo 4: Matrículas & Confirmações...")
        page.click("text=Matrículas & Confirmações")
        page.wait_for_url("**/matriculas", timeout=10000)
        page.wait_for_timeout(2000)
        page.screenshot(path=os.path.join(SCREENSHOTS_DIR, "05_matriculas_confirmacoes.png"))

        print("7. Navegando para Módulo 5: Chamada Diária & Assiduidade...")
        page.click("text=Chamada Diária")
        page.wait_for_url("**/chamada", timeout=10000)
        page.wait_for_timeout(2500)
        page.screenshot(path=os.path.join(SCREENSHOTS_DIR, "06_chamada_diaria.png"))

        browser.close()
        print("Todas as 6 telas foram capturadas com sucesso após login real!")

if __name__ == "__main__":
    run()
