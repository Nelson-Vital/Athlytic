<?php
date_default_timezone_set('Europe/Lisbon');
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações | ATHLYTIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="IMGS/Athlytic_logo_sem_texto.jpg" type="image/jpeg">
    <style>
        body { background-color: #f5f7fa; transition: background 0.3s; }
        .dark-mode { background-color: #111827 !important; color: #e5e7eb; }
        .dark-mode .card, .dark-mode .form-control, .dark-mode .form-select { background: #1f2937; border-color: #374151; color: #e5e7eb; }
        .dark-mode .text-muted { color: #9ca3af !important; }
        .sidebar { width: 250px; height: 100vh; background: #111827; color: white; position: fixed; padding: 20px 0; top: 0; left: 0; z-index: 1000; }
        .sidebar h2 { font-size: 24px; text-align: center; margin-bottom: 40px; font-weight: bold; }
        .sidebar a { display: block; padding: 14px 25px; color: #d1d5db; text-decoration: none; font-size: 16px; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #1f2937; color: white; }
        .sidebar a.navbar-brand:hover img { transform: scale(1.08); }
        .content { margin-left: 250px; padding: 40px; }
        .card-custom { border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,0.1); border: none; }
        .btn-gold { background: linear-gradient(90deg, #ffcc00, #ffd700); color: #111; font-weight: 700; border: none; }
        .btn-gold:hover { background: linear-gradient(90deg, #ffd700, #ffea00); transform: translateY(-2px); }
        .section-title { font-size: 1.4rem; font-weight: 700; color: #1f2937; border-bottom: 2px solid #ffcc00; padding-bottom: 8px; margin-bottom: 20px; }
        .dark-mode .section-title { color: #e5e7eb; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Logo clicável + Título -->
        <div class="text-center mb-4">
            <a href="dashboard.php" class="navbar-brand d-block">
                <img src="IMGS/Athlytic_logo.jpg" alt="Athlytic Logo" class="img-fluid" style="max-width: 160px; height: auto; transition: transform 0.3s;">
            </a>
            <!-- Opcional: texto ATHLYTIC por baixo do logo (podes remover se quiseres só a imagem) -->
            <!-- <h2 class="mt-2 mb-0" style="font-size: 1.4rem; letter-spacing: 1px;">ATHLYTIC</h2> -->
        </div>

        <!-- Menu com ícones -->
        <a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="bi bi-house-door-fill me-2"></i> Início
        </a>
        <a href="inserir_atividade.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'inserir_atividade.php') ? 'active' : ''; ?>">
            <i class="bi bi-plus-circle-dotted me-2"></i> Inserir Atividade
        </a>
        <a href="pCorrida.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'pCorrida.php') ? 'active' : ''; ?>">
            <i class="bi bi-geo-alt-fill me-2"></i> Plano de Corrida
        </a>
        <a href="pMusculacao.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'pMusculacao.php') ? 'active' : ''; ?>">
            <i class="bi bi-trophy-fill me-2"></i> Musculação
        </a>
        <a href="pAlimentacao.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'pAlimentacao.php') ? 'active' : ''; ?>">
            <i class="bi bi-egg-fried me-2"></i> Plano Alimentar
        </a>
        <a href="def.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'def.php') ? 'active' : ''; ?>">
            <i class="bi bi-gear-fill me-2"></i> Configurações
        </a>
    </div>

    <div class="content">
        <h1 class="mb-5"><i class="bi bi-sliders2 text-gold"></i> Configurações</h1>

        <form id="formConfig">
            <!-- 1. Dados Pessoais -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <h3 class="section-title"><i class="bi bi-person-circle me-2"></i> Dados Pessoais</h3>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nome Completo</label><input type="text" class="form-control" value="Nelson Vital"></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" value="nelsonvital@gmail.com"></div>
                        <div class="col-md-4"><label class="form-label">Data de Nascimento</label><input type="date" class="form-control" value="2003-02-20"></div>
                        <div class="col-md-4"><label class="form-label">Peso Atual (kg)</label><input type="number" step="0.1" class="form-control" value="74.3"></div>
                        <div class="col-md-4"><label class="form-label">Altura (cm)</label><input type="number" class="form-control" value="176"></div>
                        <div class="col-12"><label class="form-label">% Massa Gorda (opcional)</label><input type="number" step="0.1" class="form-control" placeholder="15.2" value="15.2"></div>
                    </div>
                </div>
            </div>

            <!-- 2. Preferências de Treino -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <h3 class="section-title"><i class="bi bi-target me-2"></i> Preferências de Treino</h3>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Meta de Passos Diários</label><input type="number" class="form-control" value="15000"></div>
                        <div class="col-md-4"><label class="form-label">Meta KMs Corridos / Semana</label><input type="number" step="0.1" class="form-control" value="25"></div>
                        <div class="col-md-4"><label class="form-label">Treinos Musculação / Semana</label><select class="form-select"><option>3</option><option selected>4</option><option>5</option><option>6</option></select></div>
                        <div class="col-md-6"><label class="form-label">Objetivo Principal</label><select class="form-select"><option>Perda de Peso</option><option selected>Recomposição Corporal</option><option>Ganho de Massa</option><option>Manutenção</option></select></div>
                        <div class="col-md-6"><label class="form-label">Intensidade Padrão</label><select class="form-select"><option>Leve</option><option selected>Moderada</option><option>Alta</option></select></div>
                    </div>
                </div>
            </div>

            <!-- 3. Notificações -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <h3 class="section-title"><i class="bi bi-bell me-2"></i> Notificações e Lembretes</h3>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="lembreteTreino" checked>
                        <label class="form-check-label" for="lembreteTreino">Lembretes diários de treino</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="metaNaoAtingida" checked>
                        <label class="form-check-label" for="metaNaoAtingida">Alertas quando metas semanais não são atingidas</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="novosPlanos">
                        <label class="form-check-label" for="novosPlanos">Notificações de novos planos personalizados</label>
                    </div>
                </div>
            </div>

            <!-- 4. Integração com Dispositivos -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <h3 class="section-title"><i class="bi bi-watch me-2"></i> Integração com Dispositivos</h3>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <strong>Garmin Connect</strong><br>
                            <small class="text-success"><i class="bi bi-check-circle-fill"></i> Conectado como nelsonvital@gmail.com</small>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm">Desconectar</button>
                    </div>
                    <button type="button" class="btn btn-outline-primary"><i class="bi bi-plus-lg"></i> Conectar outro dispositivo</button>
                </div>
            </div>

            <!-- 5. Tema e Aparência -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <h3 class="section-title"><i class="bi bi-moon-stars me-2"></i> Tema e Aparência</h3>
                    <div class="d-flex gap-3">
                        <button type="button" class="btn btn-outline-secondary" onclick="document.body.classList.remove('dark-mode')">
                            <i class="bi bi-sun"></i> Modo Claro
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="document.body.classList.add('dark-mode')">
                            <i class="bi bi-moon"></i> Modo Escuro
                        </button>
                    </div>
                </div>
            </div>

            <!-- 6. Segurança e Dados -->
            <div class="card card-custom mb-5">
                <div class="card-body">
                    <h3 class="section-title"><i class="bi bi-shield-lock me-2"></i> Segurança e Dados</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-outline-primary w-100 mb-3">Alterar Palavra-passe</button>
                            <button type="button" class="btn btn-outline-warning w-100 mb-3">Exportar Todos os Dados (CSV/PDF)</button>
                            <button type="button" class="btn btn-outline-danger w-100" onclick="if(confirm('Tem a certeza?')) alert('Histórico limpo!')">Limpar Histórico de Atividades</button>
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-danger w-100 mb-3">Terminar Sessão (Logout)</button>
                            <button type="button" class="btn btn-outline-danger w-100" onclick="if(confirm('ATENÇÃO: Esta ação é irreversível!')) alert('Conta apagada.')">Apagar Conta Permanentemente</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botão Guardar -->
            <div class="text-center">
                <button type="button" class="btn btn-gold btn-lg px-5" onclick="alert('Todas as configurações guardadas com sucesso! ✅')">
                    <i class="bi bi-check2-all"></i> Guardar Todas as Alterações
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>