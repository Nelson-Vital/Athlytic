<?php
date_default_timezone_set('Europe/Lisbon');
$hoje_num = date('w'); // 0=Domingo ... 6=Sábado
$hoje_nome = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'][$hoje_num];
$data_hoje = date('Y-m-d');
$hora_hoje = date('H:i');
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserir Atividade | ATHLYTIC</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="icon" href="IMGS/Athlytic_logo_sem_texto.jpg" type="image/jpeg">
    <link rel="apple-touch-icon" href="IMGS/Athlytic_logo_sem_texto.jpg">

    <style>
        body { background-color: #f5f7fa; }
        .sidebar { width: 250px; height: 100vh; background: #111827; color: white; position: fixed; padding: 20px 0; }
        .sidebar h2 { font-size: 24px; text-align: center; margin-bottom: 40px; font-weight: bold; }
        .sidebar a { display: block; padding: 14px 25px; color: #d1d5db; text-decoration: none; font-size: 16px; }
        .sidebar a:hover, .sidebar a.active { background: #1f2937; color: white; }
        .content { margin-left: 250px; padding: 40px; }
        .card-custom { border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); border: none; }
        .btn-gold { background: linear-gradient(90deg, #ffcc00, #ffd700); color: #111; font-weight: 700; border: none; }
        .btn-gold:hover { background: linear-gradient(90deg, #ffd700, #ffea00); transform: translateY(-2px); }
        .treino-hoje-btn { background: #dc3545; color: white; }
        .treino-hoje-btn:hover { background: #c82333; }
        .atividade-card { transition: all 0.3s; }
        .atividade-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
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
        <a href="def.php">
            <i class="bi bi-gear-fill me-2"></i> Configurações
        </a>
    </div>

    <div class="content">
        <h1 class="mb-4"><i class="bi bi-plus-circle-dotted text-success"></i> Inserir Atividade</h1>

        <div class="row">
            <!-- Formulário de Inserção -->
            <div class="col-lg-7">
                <div class="card card-custom mb-4">
                    <div class="card-body">
                        <form id="formAtividade">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Tipo de Atividade</label>
                                    <select class="form-select" id="tipo" required>
                                        <option value="">Seleciona...</option>
                                        <option value="corrida">Corrida</option>
                                        <option value="musculacao">Treino de Musculação</option>
                                        <option value="caminhada">Caminhada</option>
                                        <option value="funcional">Treino Funcional</option>
                                        <option value="ciclismo">Ciclismo</option>
                                        <option value="nataçao">Natação</option>
                                        <option value="outro">Outro</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Intensidade</label>
                                    <select class="form-select" required>
                                        <option value="leve">Leve</option>
                                        <option value="moderada" selected>Moderada</option>
                                        <option value="alta">Alta</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Data</label>
                                    <input type="date" class="form-control" value="<?php echo $data_hoje; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Hora</label>
                                    <input type="time" class="form-control" value="<?php echo $hora_hoje; ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Duração (minutos)</label>
                                    <input type="number" class="form-control" placeholder="ex: 45" min="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Distância (km) <small class="text-muted">(opcional)</small></label>
                                    <input type="number" step="0.01" class="form-control" placeholder="ex: 8.5">
                                </div>

                                <div class="col-12" id="campoFit" style="display:none;">
                                    <label class="form-label fw-bold">Ficheiro .FIT (Strava/Garmin)</label>
                                    <input type="file" class="form-control" accept=".fit">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold">Observações / Notas</label>
                                    <textarea class="form-control" rows="3" placeholder="Como te sentiste? Algum PR?"></textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold">Fotos / Prints (opcional)</label>
                                    <input type="file" class="form-control" multiple accept="image/*">
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="button" class="btn btn-gold btn-lg px-5" onclick="alert('Atividade registada com sucesso! 💪\n(Em breve com base de dados real)')">
                                        <i class="bi bi-check2-circle"></i> Registar Atividade
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lado direito: Treino do Dia + Estatísticas -->
            <div class="col-lg-5">
                <!-- Treino Sugerido do Dia -->
                <div class="card card-custom mb-4">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="bi bi-calendar-week text-primary"></i> Treino Sugerido para Hoje</h5>
                        <p class="text-muted"><?php echo $hoje_nome; ?></p>
                        <button class="btn treino-hoje-btn btn-lg" data-bs-toggle="modal" data-bs-target="#modalTreinoHoje">
                            <i class="bi bi-eye"></i> Ver Treino do Dia
                        </button>
                    </div>
                </div>

                <!-- Estatísticas Rápidas -->
                <div class="card card-custom">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-graph-up-arrow text-success"></i> Esta Semana</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between"><span>Atividades</span> <strong>4 / 5</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Horas totais</span> <strong>5h 20min</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Quilómetros</span> <strong>28.4 km</strong></li>
                            <li class="list-group-item d-flex justify-content-between text-danger"><span>Meta semanal</span> <strong>80%</strong></li>
                        </ul>
                        <div class="progress mt-3" style="height: 12px;">
                            <div class="progress-bar bg-success" style="width: 80%;">80%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Histórico de Atividades (simulado) -->
        <h3 class="mt-5 mb-4"><i class="bi bi-clock-history text-info"></i> Histórico Recente</h3>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card atividade-card card-custom">
                    <div class="card-body">
                        <h6><i class="bi bi-geo-alt-fill text-danger"></i> Corrida - 8.2 km</h6>
                        <small class="text-muted">18 Nov • 45 min • Alta</small>
                        <p class="mt-2">Ritmo médio: 5:12/km</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card atividade-card card-custom">
                    <div class="card-body">
                        <h6><i class="bi bi-trophy text-warning"></i> Musculação - Push</h6>
                        <small class="text-muted">17 Nov • 65 min • Moderada</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card atividade-card card-custom">
                    <div class="card-body">
                        <h6><i class="bi bi-water text-primary"></i> Natação</h6>
                        <small class="text-muted">15 Nov • 40 min • Leve</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal - Treino do Dia -->
    <div class="modal fade" id="modalTreinoHoje" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="bi bi-calendar-check"></i> Treino Sugerido - <?php echo $hoje_nome; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Plano de Musculação Intermédio (Push/Pull/Legs)</strong>
                    </div>
                    <h5>Hoje: Push (Peito, Ombros, Tríceps)</h5>
                    <ul>
                        <li>Supino reto barra – 4×8-10</li>
                        <li>Supino inclinado halteres – 3×10-12</li>
                        <li>Desenvolvimento militar – 4×8-10</li>
                        <li>Elevação lateral – 3×12-15</li>
                        <li>Tríceps pulley + Francês</li>
                    </ul>
                    <div class="text-end mt-4">
                        <button class="btn btn-success btn-lg" onclick="alert('Treino marcado como concluído!')">
                            <i class="bi bi-check2-all"></i> Marcar como Concluído
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar campo .FIT só se for corrida
        document.getElementById('tipo').addEventListener('change', function() {
            document.getElementById('campoFit').style.display = (this.value === 'corrida') ? 'block' : 'none';
        });
    </script>
</body>
</html>