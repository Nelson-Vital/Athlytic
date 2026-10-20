<?php
require_once 'includes/funcoes.php';
requer_login();
$u = utilizador_atual();

$erros = [];
$d = atividade_vazia($u);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    // Apagar uma atividade do histórico
    if (isset($_POST['apagar'])) {
        $id = (int) $_POST['apagar'];
        $ficheiros = ficheiros_de_atividades($u['id'], $id);
        $stmt = db()->prepare('DELETE FROM atividades WHERE id = ? AND utilizador_id = ?');
        $stmt->execute([$id, $u['id']]);
        if ($stmt->rowCount()) {
            apagar_ficheiros($ficheiros);
            flash('success', 'Atividade apagada.');
        }
        redirecionar(($_POST['voltar'] ?? '') === 'historico' ? 'historico.php' : 'inserir_atividade.php');
    }

    $d = validar_atividade($_POST, $erros);

    if (!$erros) {
        $stmt = db()->prepare(
            'INSERT INTO atividades (utilizador_id, tipo, intensidade, data, hora, duracao_seg, distancia_km, notas)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(array_merge([$u['id']], valores_atividade($d)));
        $avisos = [];
        guardar_ficheiros_atividade((int) db()->lastInsertId(), $d['tipo'], $avisos);

        flash('success', 'Atividade registada com sucesso! 💪');
        foreach ($avisos as $aviso) {
            flash('warning', $aviso . ' A atividade foi guardada sem esse ficheiro.');
        }
        redirecionar('inserir_atividade.php');
    }
}

// Dados para a página
$semana = estatisticas_semana($u['id']);
$pctMeta = $u['meta_treinos_semana'] > 0 ? min(100, round($semana['atividades'] / $u['meta_treinos_semana'] * 100)) : 0;
$treinoHoje = dia_do_plano($u['plano_musculacao_id'], (int) date('N'));
$stmt = db()->prepare('SELECT 1 FROM plano_concluidos WHERE utilizador_id = ? AND plano_id = ? AND data = CURDATE()');
$stmt->execute([$u['id'], (int) $u['plano_musculacao_id']]);
$concluidoHoje = (bool) $stmt->fetch();

$stmt = db()->prepare('SELECT * FROM atividades WHERE utilizador_id = ? ORDER BY data DESC, hora DESC LIMIT 9');
$stmt->execute([$u['id']]);
$historico = $stmt->fetchAll();

// Fotos das atividades mostradas
$fotosPorAtividade = [];
if ($historico) {
    $ids = array_column($historico, 'id');
    $stmt = db()->prepare('SELECT atividade_id, caminho FROM atividade_fotos WHERE atividade_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
    $stmt->execute($ids);
    foreach ($stmt as $f) {
        $fotosPorAtividade[$f['atividade_id']][] = $f['caminho'];
    }
}

$titulo = 'Inserir Atividade';
$pagina = 'atividade';
require 'includes/header.php';
?>
        <h1 class="mb-4"><i class="bi bi-plus-circle-dotted text-success"></i> Inserir Atividade</h1>

        <div class="row">
            <!-- Formulário de Inserção -->
            <div class="col-lg-7">
                <div class="card card-custom mb-4">
                    <div class="card-body">
                        <?php $textoBotao = 'Registar Atividade'; require 'includes/form_atividade.php'; ?>
                    </div>
                </div>
            </div>

            <!-- Lado direito: Treino do Dia + Estatísticas -->
            <div class="col-lg-5">
                <div class="card card-custom mb-4">
                    <div class="card-body text-center">
                        <?php if ($concluidoHoje): ?>
                            <h5 class="card-title text-success">
                                <i class="bi bi-check-circle-fill"></i> Treino de Hoje Feito
                            </h5>
                            <p class="text-muted"><?= e(DIAS_SEMANA[(int) date('N')]) ?><?= $treinoHoje ? ' · ' . e($treinoHoje['titulo']) : '' ?></p>
                            <button class="btn btn-outline-success btn-lg" data-bs-toggle="modal" data-bs-target="#modalTreinoHoje">
                                <i class="bi bi-eye"></i> Ver Treino Feito
                            </button>
                        <?php else: ?>
                            <h5 class="card-title">
                                <i class="bi bi-calendar-week text-primary"></i> Treino Sugerido para Hoje
                            </h5>
                            <p class="text-muted"><?= e(DIAS_SEMANA[(int) date('N')]) ?></p>
                            <button class="btn treino-hoje-btn btn-lg" data-bs-toggle="modal" data-bs-target="#modalTreinoHoje">
                                <i class="bi bi-eye"></i> Ver Treino do Dia
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card card-custom">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-graph-up-arrow text-success"></i> Esta Semana
                        </h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Atividades</span> <strong><?= (int) $semana['atividades'] ?> / <?= (int) $u['meta_treinos_semana'] ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Horas totais</span> <strong><?= e(formatar_duracao((int) $semana['minutos'])) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Quilómetros</span> <strong><?= numero($semana['km']) ?> km</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between <?= $pctMeta < 100 ? 'text-danger' : 'text-success' ?>">
                                <span>Meta semanal</span> <strong><?= $pctMeta ?>%</strong>
                            </li>
                        </ul>
                        <div class="progress mt-3" style="height: 12px;" role="progressbar" aria-valuenow="<?= $pctMeta ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success" style="width: <?= $pctMeta ?>%;"><?= $pctMeta ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Histórico de Atividades -->
        <h3 class="mt-5 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><i class="bi bi-clock-history text-info"></i> Histórico Recente</span>
            <a href="historico.php" class="btn btn-sm btn-outline-info">Ver histórico completo e gráficos</a>
        </h3>
        <?php if (!$historico): ?>
            <p class="text-muted">Ainda não registaste nenhuma atividade.</p>
        <?php endif; ?>
        <div class="row g-4">
            <?php foreach ($historico as $a): [$nomeTipo, $icone, $cor] = tipo_info($a['tipo']); ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card atividade-card card-custom">
                        <div class="card-body">
                            <div class="float-end">
                                <?php $voltarPara = ''; require 'includes/botoes_atividade.php'; ?>
                            </div>
                            <h6>
                                <i class="bi <?= e($icone) ?> <?= e($cor) ?>"></i> <?= e($nomeTipo) ?>
                                <?php if ($a['distancia_km'] !== null): ?> - <?= numero($a['distancia_km'], 2) ?> km<?php endif; ?>
                            </h6>
                            <small class="text-muted">
                                <?= e(data_curta($a['data'])) ?> • <?= e(formatar_tempo((int) $a['duracao_seg'])) ?> • <?= e(INTENSIDADES[$a['intensidade']]) ?>
                            </small>
                            <?php if ($a['tipo'] === 'corrida' && $a['distancia_km'] > 0):
                                $segPorKm = (int) round($a['duracao_seg'] / $a['distancia_km']); ?>
                                <p class="mt-2 mb-0">Ritmo médio: <?= sprintf('%d:%02d', intdiv($segPorKm, 60), $segPorKm % 60) ?>/km</p>
                            <?php endif; ?>
                            <?php if ($a['notas']): ?>
                                <p class="mt-2 mb-0 small"><?= nl2br(e($a['notas'])) ?></p>
                            <?php endif; ?>
                            <?php if ($a['ficheiro_fit']): ?>
                                <a href="<?= e($a['ficheiro_fit']) ?>" class="small d-block mt-2" download><i class="bi bi-file-earmark-arrow-down"></i> Ficheiro .FIT</a>
                            <?php endif; ?>
                            <?php if (!empty($fotosPorAtividade[$a['id']])): ?>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <?php foreach ($fotosPorAtividade[$a['id']] as $foto): ?>
                                        <a href="<?= e($foto) ?>" target="_blank"><img src="<?= e($foto) ?>" alt="Foto da atividade" class="rounded" style="width: 60px; height: 60px; object-fit: cover;"></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <!-- Modal - Treino do Dia -->
    <div class="modal fade" id="modalTreinoHoje" tabindex="-1" aria-labelledby="modalTreinoHojeLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="modalTreinoHojeLabel">
                        <i class="bi bi-calendar-check"></i> <?= $concluidoHoje ? 'Treino Feito' : 'Treino Sugerido' ?> - <?= e(DIAS_SEMANA[(int) date('N')]) ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <?php if ($treinoHoje): ?>
                        <div class="alert alert-info"><strong><?= e($treinoHoje['plano_nome']) ?></strong></div>
                        <h5>Hoje: <?= e($treinoHoje['titulo']) ?></h5>
                        <?= lista_conteudo($treinoHoje['conteudo']) ?>
                        <?php if ($concluidoHoje): ?>
                            <p class="text-success fw-bold mt-4 mb-0"><i class="bi bi-check-circle-fill"></i> Treino de hoje já concluído!</p>
                        <?php elseif (!$treinoHoje['descanso']): ?>
                            <hr>
                            <?php $tipoPlano = 'musculacao'; $voltarPara = 'inserir_atividade.php'; require 'includes/form_concluir.php'; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Ainda não escolheste um plano de musculação. <a href="plano.php?tipo=musculacao">Escolher plano</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php
$scripts = '<script src="assets/js/atividade.js"></script>';
require 'includes/footer.php';
