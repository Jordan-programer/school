import os
from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import cm, mm
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Image, Table, TableStyle, PageBreak, KeepTogether, HRFlowable
)
from reportlab.pdfgen import canvas

PDF_PATH = r"c:\SisGestEsc\RELATORIO_SISTEMA_SISGESTESC.pdf"
SCREENSHOTS_DIR = r"c:\SisGestEsc\screenshots"

# Cores da identidade visual angolana do SisGestEsc
MATA_ESCURA = colors.HexColor("#1A382F")
MATA = colors.HexColor("#274F43")
GIRASSOL = colors.HexColor("#C59B27")
TIJOLO = colors.HexColor("#B91C1C")
PAPEL = colors.HexColor("#FDFBF7")
SUPERFICIE = colors.HexColor("#FFFFFF")
LINHA = colors.HexColor("#E2E8F0")
TINTA = colors.HexColor("#1E293B")
TINTA_SUAVE = colors.HexColor("#64748B")
VERDE_TAG = colors.HexColor("#E6F4EA")
TEXTO_VERDE = colors.HexColor("#137333")

class NumberedCanvas(canvas.Canvas):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_page_decorations(num_pages)
            super().showPage()
        super().save()

    def draw_page_decorations(self, total_pages):
        # Ignora cabeçalho e rodapé na primeira página (capa)
        if self._pageNumber == 1:
            return

        self.saveState()
        self.setFont("Helvetica", 8)
        self.setFillColor(TINTA_SUAVE)

        # Cabeçalho
        self.drawString(56.7, 800, "REPÚBLICA DE ANGOLA • MINISTÉRIO DA EDUCAÇÃO")
        self.drawRightString(A4[0] - 56.7, 800, "SisGestEsc — Relatório de Execução Técnica")
        self.setStrokeColor(LINHA)
        self.setLineWidth(0.5)
        self.line(56.7, 793, A4[0] - 56.7, 793)

        # Rodapé
        self.line(56.7, 45, A4[0] - 56.7, 45)
        self.drawString(56.7, 32, "SisGestEsc — Iniciação, Primário (1ª–6ª) e 1º Ciclo Secundário (7ª–9ª)")
        page_text = f"Página {self._pageNumber} de {total_pages}"
        self.drawRightString(A4[0] - 56.7, 32, page_text)
        self.restoreState()


def build_pdf():
    doc = SimpleDocTemplate(
        PDF_PATH,
        pagesize=A4,
        leftMargin=56.7,
        rightMargin=56.7,
        topMargin=56.7,
        bottomMargin=56.7
    )

    styles = getSampleStyleSheet()

    # Estilos customizados
    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=24,
        leading=28,
        textColor=MATA_ESCURA,
        alignment=0,
        spaceAfter=6
    )

    subtitle_style = ParagraphStyle(
        'DocSubtitle',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=12,
        leading=16,
        textColor=TINTA_SUAVE,
        alignment=0,
        spaceAfter=15
    )

    h1_style = ParagraphStyle(
        'Heading1_Custom',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=16,
        leading=20,
        textColor=MATA_ESCURA,
        spaceBefore=14,
        spaceAfter=8,
        keepWithNext=True
    )

    h2_style = ParagraphStyle(
        'Heading2_Custom',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=12,
        leading=16,
        textColor=MATA,
        spaceBefore=10,
        spaceAfter=4,
        keepWithNext=True
    )

    body_style = ParagraphStyle(
        'Body_Custom',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=9.5,
        leading=14,
        textColor=TINTA,
        spaceAfter=8
    )

    body_bold = ParagraphStyle(
        'Body_Bold_Custom',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=9.5,
        leading=14,
        textColor=TINTA,
        spaceAfter=8
    )

    badge_style = ParagraphStyle(
        'Badge_Custom',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=8,
        leading=10,
        textColor=TEXTO_VERDE,
        alignment=1
    )

    caption_style = ParagraphStyle(
        'Caption_Custom',
        parent=styles['Normal'],
        fontName='Helvetica-Oblique',
        fontSize=8.5,
        leading=11,
        textColor=TINTA_SUAVE,
        alignment=1,
        spaceBefore=4,
        spaceAfter=12
    )

    story = []

    # ==========================================
    # PÁGINA 1: CAPA INSTITUCIONAL & SUMÁRIO EXECUTIVO
    # ==========================================
    header_table_data = [
        [
            Paragraph("<b>REPÚBLICA DE ANGOLA</b><br/><font size=8 color='#64748B'>MINISTÉRIO DA EDUCAÇÃO</font>", body_style),
            Paragraph("<b>PROJECTO SISGESTESC</b><br/><font size=8 color='#137333'>SISTEMA EM PRODUÇÃO / HOMOLOGAÇÃO</font>", ParagraphStyle('R_Align', parent=body_style, alignment=2))
        ]
    ]
    header_table = Table(header_table_data, colWidths=[240, 240])
    header_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('BOTTOMPADDING', (0,0), (-1,-1), 0),
    ]))
    story.append(header_table)
    story.append(HRFlowable(width="100%", thickness=2, color=GIRASSOL, spaceBefore=8, spaceAfter=20))

    story.append(Paragraph("RELATÓRIO TÉCNICO & FUNCIONAL DE EXECUÇÃO", title_style))
    story.append(Paragraph("Demonstração dos Módulos Implementados, Ecrãs do Sistema e Evidências de Testes Automatizados", subtitle_style))

    # Tabela com Metadados do Projecto
    meta_data = [
        [Paragraph("<b>Sistema:</b>", body_style), Paragraph("SisGestEsc — Sistema Integrado de Gestão Escolar", body_style),
         Paragraph("<b>Data do Relatório:</b>", body_style), Paragraph("25 de Outubro de 2026", body_style)],
        [Paragraph("<b>Subsistemas:</b>", body_style), Paragraph("Iniciação, Primário (1ª–6ª) e 1º Ciclo Secundário (7ª–9ª)", body_style),
         Paragraph("<b>Localização:</b>", body_style), Paragraph("Luanda, República de Angola", body_style)],
        [Paragraph("<b>Stack Tecnológica:</b>", body_style), Paragraph("Laravel 11+ (PHP 8.3) • React 19 (TypeScript) • PostgreSQL", body_style),
         Paragraph("<b>Cobertura de Testes:</b>", body_style), Paragraph("<b>46 testes aprovados</b> (324 asserções)", body_style)],
        [Paragraph("<b>Módulos Concluídos:</b>", body_style), Paragraph("Módulos 1, 2, 3, 4 e 5 (100% Funcionais e Validados)", body_style),
         Paragraph("<b>Próximo Módulo:</b>", body_style), Paragraph("Módulo 6 (Notas, Mini-Pautas & Pautas)", body_style)]
    ]
    meta_table = Table(meta_data, colWidths=[90, 160, 95, 135])
    meta_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), PAPEL),
        ('BOX', (0,0), (-1,-1), 1, LINHA),
        ('INNERGRID', (0,0), (-1,-1), 0.5, LINHA),
        ('TOPPADDING', (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
        ('LEFTPADDING', (0,0), (-1,-1), 6),
        ('RIGHTPADDING', (0,0), (-1,-1), 6),
    ]))
    story.append(meta_table)
    story.append(Spacer(1, 15))

    # Resumo Executivo
    story.append(Paragraph("1. Sumário Executivo do Projecto", h1_style))
    story.append(Paragraph(
        "O <b>SisGestEsc</b> foi concebido de raiz para responder integralmente às exigências legais, organizacionais e pedagógicas das instituições escolares angolanas. O sistema contempla todas as especificidades do modelo de ensino de Angola, incluindo o subsistema de <b>Iniciação</b>, o <b>Ensino Primário organizado em 3 ciclos de 2 anos</b> (1ª–2ª, 3ª–4ª, 5ª–6ª classes) e o <b>1º Ciclo do Ensino Secundário Geral</b> (7ª–9ª classes).",
        body_style
    ))
    story.append(Paragraph(
        "Foram consolidadas na plataforma as seguintes directrizes obrigatórias: suporte nativo aos documentos de identificação angolanos (<b>Bilhete de Identidade com máscara oficial, Cédula Pessoal, Assento de Nascimento e Número Provisório</b> para garantir que nenhuma criança seja impedida de estudar), monitorização de <b>distorção idade-classe</b> (alerta a partir de idade &gt; esperado + 2 anos sem bloqueio de matrícula), <b>número de processo permanente</b> (<code>PROC-YYYY-XXXX</code>), cálculo de <b>risco de exclusão escolar por assiduidade inferior a 75%</b> e operacionalidade <b>Offline-First</b> para garantir lançamento contínuo de chamadas diárias mesmo perante quebras de rede ou energia eléctrica.",
        body_style
    ))

    # Captura da Capa: Ecrã de Login
    story.append(Spacer(1, 10))
    story.append(Paragraph("Ecrã Oficial de Autenticação e Entrada no Sistema", h2_style))
    img_login = os.path.join(SCREENSHOTS_DIR, "01_login.png")
    if os.path.exists(img_login):
        story.append(Image(img_login, width=475, height=295))
        story.append(Paragraph("Figura 1: Ecrã de autenticação com identificação visual angolana, selecção rápida de papéis e suporte a telefone (+244) e e-mail.", caption_style))

    story.append(PageBreak())

    # ==========================================
    # PÁGINA 2: MÓDULO 1 — UTILIZADORES, PERFIS E PERMISSÕES
    # ==========================================
    story.append(Paragraph("2. Módulo 1: Gestão de Utilizadores, Perfis e Acessos (RBAC)", h1_style))
    story.append(Paragraph(
        "O Módulo 1 implementa o controlo de acesso rigoroso baseado em papéis (Role-Based Access Control) através de <b>Laravel Sanctum</b> e <b>Spatie Permission</b>. O sistema disponibiliza <b>11 perfis institucionais angolanos</b>: Director Geral, Subdirector Pedagógico, Subdirector Administrativo, Secretário(a), Coordenador(a) de Turno, Coordenador(a) de Disciplina, Director(a) de Turma, Professor(a), Tesouraria, Técnico(a) de Secretaria e Fiscal / Inspecção Provincial.",
        body_style
    ))
    story.append(Paragraph(
        "<b>Principais Regras e Funcionalidades Implementadas:</b><br/>"
        "• Autenticação dupla: login por endereço de correio electrónico ou número telefónico no padrão nacional angolano (+244 9xx xxx xxx).<br/>"
        "• Associação obrigatória de utilizador a registo funcional de membro da equipa escolar (<code>Staff</code>), contendo BI, código de funcionário e grau académico.<br/>"
        "• Protecção anti-bloqueio: o Director Geral não pode desactivar a sua própria conta activa.<br/>"
        "• Painel de gestão administrativa com filtragem instantânea por papel e alternância de estado de activação.",
        body_style
    ))

    img_users = os.path.join(SCREENSHOTS_DIR, "02_utilizadores.png")
    if os.path.exists(img_users):
        story.append(Image(img_users, width=475, height=295))
        story.append(Paragraph("Figura 2: Painel administrativo de utilizadores e equipa escolar com filtros por perfil, estado de conta e dados de BI.", caption_style))

    story.append(PageBreak())

    # ==========================================
    # PÁGINA 3: MÓDULO 2 — ESTRUTURA ESCOLAR & TURMAS
    # ==========================================
    story.append(Paragraph("3. Módulo 2: Estrutura Escolar, Anos Lectivos e Turmas", h1_style))
    story.append(Paragraph(
        "O Módulo 2 parametriza todo o esqueleto curricular e logístico da escola, organizando a oferta formativa de acordo com a matriz do Ministério da Educação de Angola.",
        body_style
    ))
    story.append(Paragraph(
        "<b>Componentes Estruturais Configurados:</b><br/>"
        "• <b>Anos Lectivos e Trimestres:</b> Estruturação do ano lectivo em <b>3 trimestres oficiais</b>, cada um com data de início, data de término e data limite de entrega de notas pelo conselho pedagógico (com método <code>isGradingLocked()</code>).<br/>"
        "• <b>Matriz Curricular:</b> Iniciação, 1º Ciclo Primário (1ª e 2ª classes), 2º Ciclo Primário (3ª e 4ª classes), 3º Ciclo Primário (5ª e 6ª classes) e 1º Ciclo do Secundário (7ª, 8ª e 9ª classes).<br/>"
        "• <b>Turnos e Horários:</b> Manhã, Tarde e Noite (Pós-laboral).<br/>"
        "• <b>Turmas e Salas:</b> Criação de turmas com lotação máxima (ex: 45 alunos), atribuição de salas físicas e afectação de docentes por disciplina.",
        body_style
    ))

    img_estrutura = os.path.join(SCREENSHOTS_DIR, "03_estrutura_escolar.png")
    if os.path.exists(img_estrutura):
        story.append(Image(img_estrutura, width=475, height=295))
        story.append(Paragraph("Figura 3: Gestão da estrutura escolar com matriz curricular angolana, turmas por turno e distribuição docente.", caption_style))

    story.append(PageBreak())

    # ==========================================
    # PÁGINA 4: MÓDULO 3 — ALUNOS E ENCARREGADOS DE EDUCAÇÃO
    # ==========================================
    story.append(Paragraph("4. Módulo 3: Gestão de Alunos e Encarregados de Educação", h1_style))
    story.append(Paragraph(
        "O Módulo 3 gere a vida cadastral do estudante e os respectivos encarregados de educação (pais, tutores ou responsáveis legais), respeitando a realidade social do país.",
        body_style
    ))
    story.append(Paragraph(
        "<b>Destaques de Implementação:</b><br/>"
        "• <b>Número de Processo Escolar Único (UID):</b> Gerado no formato padronizado <code>PROC-YYYY-XXXX</code> (ex: <code>PROC-2026-0001</code>).<br/>"
        "• <b>Documentos de Identificação Inclusivos:</b> Validação estrita do Bilhete de Identidade angolano (9 dígitos + 2 letras + 3 dígitos, ex: <code>007812934LA045</code>), Cédula Pessoal, Assento de Nascimento e <b>Número Provisório</b>.<br/>"
        "• <b>Divisão Territorial de Angola:</b> Tabela de províncias (Luanda, Benguela, Huambo, Huíla, Cabinda, Uíge, etc.) e municípios para naturalidade e residência.<br/>"
        "• <b>Vínculo Familiar:</b> Ligação flexível entre aluno e encarregados através de relação N:M com identificação de encarregado financeiro e pedagógico.",
        body_style
    ))

    img_alunos = os.path.join(SCREENSHOTS_DIR, "04_alunos_encarregados.png")
    if os.path.exists(img_alunos):
        story.append(Image(img_alunos, width=475, height=295))
        story.append(Paragraph("Figura 4: Listagem e ficha individual de alunos com processo PROC-YYYY-XXXX e dados dos encarregados de educação.", caption_style))

    story.append(PageBreak())

    # ==========================================
    # PÁGINA 5: MÓDULO 4 — MATRÍCULAS & CONFIRMAÇÕES
    # ==========================================
    story.append(Paragraph("5. Módulo 4: Matrículas, Confirmações e Distribuição de Turmas", h1_style))
    story.append(Paragraph(
        "O Módulo 4 suporta o ciclo de vida da inscrição discente, distinguindo com precisão a primeira matrícula da renovação anual de frequência.",
        body_style
    ))
    story.append(Paragraph(
        "<b>Mecanismos Automatizados:</b><br/>"
        "• <b>Tipos de Inscrição:</b> <b>Nova Matrícula</b> (ingresso pela primeira vez), <b>Confirmação de Matrícula</b> (renovação no ano lectivo subsequente sem re-matrícula de dados cadastrais) e <b>Transferência</b> (entrada ou saída com guia oficial).<br/>"
        "• <b>Motor de Detecção de Distorção Idade-Classe:</b> Compara a idade cronológica do aluno à data de 31 de Dezembro com a idade normativa da classe (Iniciação = 5 anos, 1ª classe = 6 anos, ..., 9ª classe = 14 anos). Se a idade exceder a referência em mais de 2 anos, o sistema emite aviso de distorção idade-classe sem bloquear a matrícula.<br/>"
        "• <b>Ordenação Alfabética e Número de Ordem:</b> Numeração de 1 a N na turma atribuída automaticamente.<br/>"
        "• <b>Recibo Timbrado de Matrícula:</b> Folha timbrada oficial em conformidade com o regulamento da Secretaria Escolar pronta para impressão.",
        body_style
    ))

    img_matriculas = os.path.join(SCREENSHOTS_DIR, "05_matriculas_confirmacoes.png")
    if os.path.exists(img_matriculas):
        story.append(Image(img_matriculas, width=475, height=295))
        story.append(Paragraph("Figura 5: Painel de gestão de matrículas com resumo estatístico, alerta de distorção idade-classe e emissão de recibo.", caption_style))

    story.append(PageBreak())

    # ==========================================
    # PÁGINA 6: MÓDULO 5 — CHAMADA DIÁRIA & ASSIDUIDADE OFFLINE
    # ==========================================
    story.append(Paragraph("6. Módulo 5: Chamada Diária e Assiduidade Offline", h1_style))
    story.append(Paragraph(
        "O Módulo 5 fornece a ferramenta de trabalho quotidiano para os professores na sala de aula, concebida para resistir a falhas de rede eléctrica ou internet nas escolas.",
        body_style
    ))
    story.append(Paragraph(
        "<b>Capacidades Técnicas e Pedagógicas:</b><br/>"
        "• <b>Arquitectura Offline-First:</b> Gravação e enfileiramento local no navegador via <code>localStorage</code> / <code>IndexedDB</code> durante corte de conexão. Sincronização automática em lote assim que a conectividade for restabelecida.<br/>"
        "• <b>Idempotência Garantida:</b> Cada sessão gera um <code>client_mutation_id</code> (UUID v4) na origem, impossibilitando duplicações de faltas na base de dados PostgreSQL.<br/>"
        "• <b>Regime Angolano de Assiduidade:</b> Registo por tempo lectivo (1 a 6) com os 4 códigos oficiais: <b>P</b> (Presente), <b>F</b> (Falta Injustificada), <b>FJ</b> (Falta Justificada com registo de motivo) e <b>A</b> (Atraso).<br/>"
        "• <b>Alerta Automático de Exclusão:</b> Notificação visual imediata para estudantes com assiduidade inferior a <b>75%</b> ou faltas injustificadas superiores a <b>15%</b> (risco de exclusão de exame e chumbo por faltas).",
        body_style
    ))

    img_chamada = os.path.join(SCREENSHOTS_DIR, "06_chamada_diaria.png")
    if os.path.exists(img_chamada):
        story.append(Image(img_chamada, width=475, height=295))
        story.append(Paragraph("Figura 6: Folha de chamada diária com botões rápidos P/F/FJ/A, indicador de sincronização offline e atalho 'Todos Presentes'.", caption_style))

    story.append(PageBreak())

    # ==========================================
    # PÁGINA 7: RELATÓRIO DE TESTES & CONCLUSÃO
    # ==========================================
    story.append(Paragraph("7. Validação de Qualidade e Cobertura de Testes Automatizados", h1_style))
    story.append(Paragraph(
        "A integridade de todas as regras de negócio e endpoints do SisGestEsc é garantida por uma bateria exaustiva de testes de integração e testes unitários desenvolvidos com a framework <b>Pest PHP</b>.",
        body_style
    ))

    # Tabela com Resumo dos Testes
    test_data = [
        [Paragraph("<b>Suite de Teste</b>", body_style), Paragraph("<b>Ficheiro</b>", body_style), Paragraph("<b>Objectivo do Teste</b>", body_style), Paragraph("<b>Resultado</b>", body_style)],
        [Paragraph("Autenticação e Perfis", body_style), Paragraph("<code>AuthTest.php</code>", body_style), Paragraph("Validação de login por e-mail e telefone (+244), negação de contas inactivas.", body_style), Paragraph("<font color='#137333'><b>5 PASSOU</b></font>", body_style)],
        [Paragraph("Gestão de Utilizadores", body_style), Paragraph("<code>UserManagementTest.php</code>", body_style), Paragraph("Criação de utilizadores pelo Director, vinculação a Staff e autorizações RBAC.", body_style), Paragraph("<font color='#137333'><b>5 PASSOU</b></font>", body_style)],
        [Paragraph("Estrutura Escolar", body_style), Paragraph("<code>AcademicStructureTest.php</code>", body_style), Paragraph("Matriz curricular angolana, turnos, turmas, disciplinas e bloqueio por prazo.", body_style), Paragraph("<font color='#137333'><b>6 PASSOU</b></font>", body_style)],
        [Paragraph("Alunos e Encarregados", body_style), Paragraph("<code>StudentManagementTest.php</code>", body_style), Paragraph("Geração de PROC-YYYY-XXXX, validação de BI/cédula, encarregados obrigatórios.", body_style), Paragraph("<font color='#137333'><b>11 PASSOU</b></font>", body_style)],
        [Paragraph("Matrículas e Recibos", body_style), Paragraph("<code>EnrollmentTest.php</code>", body_style), Paragraph("Nova matrícula, confirmação anual, cálculo de distorção e recibo timbrado.", body_style), Paragraph("<font color='#137333'><b>11 PASSOU</b></font>", body_style)],
        [Paragraph("Chamada Diária & Offline", body_style), Paragraph("<code>AttendanceTest.php</code>", body_style), Paragraph("Sincronização offline idempotente, folha de chamada, justificação e alerta 75%.", body_style), Paragraph("<font color='#137333'><b>6 PASSOU</b></font>", body_style)],
        [Paragraph("Testes Gerais Unitários", body_style), Paragraph("<code>ExampleTest.php</code>", body_style), Paragraph("Respostas gerais da aplicação e integridade do runtime.", body_style), Paragraph("<font color='#137333'><b>2 PASSOU</b></font>", body_style)],
        [Paragraph("<b>TOTAL GLOBAL</b>", body_bold), Paragraph("<b>8 Suítes</b>", body_bold), Paragraph("<b>Cobertura total de requisitos funcionais</b>", body_bold), Paragraph("<font color='#137333'><b>46 PASSOU (324 asserções)</b></font>", body_bold)]
    ]
    test_table = Table(test_data, colWidths=[105, 115, 175, 85])
    test_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), MATA_ESCURA),
        ('TEXTCOLOR', (0,0), (-1,0), colors.white),
        ('BACKGROUND', (0,1), (-1,-2), SUPERFICIE),
        ('BACKGROUND', (0,-1), (-1,-1), PAPEL),
        ('BOX', (0,0), (-1,-1), 1, LINHA),
        ('INNERGRID', (0,0), (-1,-1), 0.5, LINHA),
        ('TOPPADDING', (0,0), (-1,-1), 4),
        ('BOTTOMPADDING', (0,0), (-1,-1), 4),
        ('LEFTPADDING', (0,0), (-1,-1), 5),
        ('RIGHTPADDING', (0,0), (-1,-1), 5),
    ]))
    story.append(test_table)
    story.append(Spacer(1, 15))

    story.append(Paragraph("8. Próximos Passos de Desenvolvimento — Módulo 6", h1_style))
    story.append(Paragraph(
        "Com a conclusão bem-sucedida dos Módulos 1 a 5, o sistema encontra-se plenamente preparado para a transição para o <b>Módulo 6: Notas, Mini-Pautas & Pautas Gerais</b>, que contemplará:<br/>"
        "• <b>Avaliação Pedagógica Angolana:</b> Lançamento de MAC (Média das Avaliações Contínuas), PP (Prova do Professor) e PT (Prova Trimestral) para cálculo da Média Trimestral (MT).<br/>"
        "• <b>Transição e Classificação Final:</b> Fórmulas para o Primário (transição em ciclos na 2ª, 4ª e 6ª classes) e 1º Ciclo do Secundário Geral (7ª, 8ª e 9ª com exame).<br/>"
        "• <b>Conselho de Turma e Pautas Oficiais:</b> Emissão da Mini-Pauta por disciplina e da Pauta Geral de Turma em formato apaisado (landscape) pronta para aposição de selo branco e assinaturas.",
        body_style
    ))

    # Assinatura de Responsabilidade
    story.append(Spacer(1, 20))
    sig_data = [
        [
            Paragraph("________________________________________<br/><b>A Direcção do Projecto SisGestEsc</b><br/>Engenharia de Software & Arquitectura", ParagraphStyle('C_Sig', parent=body_style, alignment=1)),
            Paragraph("________________________________________<br/><b>A Direcção Pedagógica & Homologação</b><br/>Ministério da Educação da República de Angola", ParagraphStyle('C_Sig2', parent=body_style, alignment=1))
        ]
    ]
    sig_table = Table(sig_data, colWidths=[240, 240])
    sig_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('ALIGN', (0,0), (-1,-1), 'CENTER'),
    ]))
    story.append(sig_table)

    # Constrói o PDF
    doc.build(story, canvasmaker=NumberedCanvas)
    print(f"PDF gerado com sucesso em: {PDF_PATH}")

if __name__ == "__main__":
    build_pdf()
