<?php
/**
 * Página única para os três tipos de plano: plano.php?tipo=corrida | musculacao | alimentacao
 */
require_once 'includes/funcoes.php';
requer_login();
$u = utilizador_atual();

$tipos = [
    'corrida' => [
        'titulo' => 'Plano de Corrida', 'icone' => 'bi-stopwatch text-danger', 'alerta' => 'alert-success',
        'coluna' => 'plano_corrida_id', 'badge' => 'bg-success', 'botao' => 'btn-outline-primary',
        'acao'   => 'Marcar treino de hoje como concluído',
    ],
    'musculacao' => [
        'titulo' => 'Plano de Musculação', 'icone' => 'bi-trophy text-danger', 'alerta' => 'alert-danger',
        'coluna' => 'plano_musculacao_id', 'badge' => 'bg-danger', 'botao' => 'btn-danger',
        'acao'   => 'Marcar treino de hoje como concluído',
    ],
    'alimentacao' => [
        'titulo' => 'Plano Alimentar Semanal', 'icone' => 'bi-egg-fried text-success', 'alerta' => 'alert-success',
        'coluna' => 'plano_alimentacao_id', 'badge' => 'bg-success', 'botao' => 'btn-success',
        'acao'   => 'Marcar refeições de hoje como concluídas',
    ],
];

$tipo = $_GET['tipo'] ?? 'corrida';
if (!isset($tipos[$tipo])) {
    redirecionar('plano.php?tipo=corrida');
}
$cfg = $tipos[$tipo];

// Trocar de plano
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $stmt = db()->prepare('SELECT id FROM planos WHERE id = ? AND tipo = ?');
    $stmt->execute([$_POST['plano_id'] ?? 0, $tipo]);
    if ($novo = $stmt->fetchColumn()) {
        // O nome da coluna vem da lista fixa acima, nunca do utilizador
        $stmt = db()->prepare("UPDATE utilizadores SET {$cfg['coluna']} = ? WHERE id = ?");
        $stmt->execute([$novo, $u['id']]);
        flash('success', 'Plano atualizado.');
    }
    redirecionar('plano.php?tipo=' . $tipo);
}

$stmt = db()->prepare('SELECT * FROM planos WHERE tipo = ? ORDER BY id');
$stmt->execute([$tipo]);
$planos = $stmt->fetchAll();

$planoId = $u[$cfg['coluna']];
$plano = null;
foreach ($planos as $p) {
    if ($p['id'] == $planoId) {
        $plano = $p;
    }
}

$dias = [];
$concluidoHoje = false;
$concluidosSemana = 0;
if ($plano) {
    $stmt = db()->prepare('SELECT * FROM plano_dias WHERE plano_id = ? ORDER BY dia_semana');
    $stmt->execute([$plano['id']]);
    $dias = $stmt->fetchAll();

    [$inicio, $fim] = semana_atual();
    $stmt = db()->prepare('SELECT data FROM plano_concluidos WHERE utilizador_id = ? AND plano_id = ? AND data BETWEEN ? AND ?');
    $stmt->execute([$u['id'], $plano['id'], $inicio, $fim]);
    $datasConcluidas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $concluidosSemana = count($datasConcluidas);
    $concluidoHoje = in_array(date('Y-m-d'), $datasConcluidas, true);
}
$hoje = (int) date('N');
$diaHoje = null;
foreach ($dias as $dia) {
    if ($dia['dia_semana'] == $hoje) {
        $diaHoje = $dia;
    }
}


$titulo = $cfg['titulo'];
$pagina = $tipo;
require 'includes/header.php';
?>
        <div class="tema-<?= e($tipo) ?>">
            <h1 class="mb-4"><i class="bi <?= e($cfg['icone']) ?>"></i> <?= e($cfg['titulo']) ?></h1>

            <div class="alert <?= e($cfg['alerta']) ?>">
                <strong>Hoje é <?= e(data_extenso()) ?></strong>
            </div>

            <div class="card card-custom mb-4">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <?php if ($plano): ?>
                            <h3 class="card-title mb-1"><?= e($plano['nome']) ?></h3>
                            <p class="text-muted mb-0"><?= e($plano['descricao']) ?></p>
                            <small class="text-success"><strong>Nível:</strong> <?= e($plano['nivel']) ?>
                                <?php if ($tipo !== 'alimentacao'): ?> · <strong>Concluídos esta semana:</strong> <?= $concluidosSemana ?><?php endif; ?>
                            </small>
                        <?php else: ?>
                            <h3 class="card-title mb-1">Ainda não escolheste um plano</h3>
                            <p class="text-muted mb-0">Escolhe um plano ao lado para começares.</p>
                        <?php endif; ?>
                    </div>
                    <form method="post" class="d-flex gap-2">
                        <?= csrf_campo() ?>
                        <select name="plano_id" class="form-select" aria-label="Escolher plano">
                            <?php foreach ($planos as $p): ?>
                                <option value="<?= (int) $p['id'] ?>" <?= $p['id'] == $planoId ? 'selected' : '' ?>><?= e($p['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-outline-secondary text-nowrap">Mudar plano</button>
                    </form>
                </div>
            </div>

            <div class="row">
                <?php foreach ($dias as $dia): $eHoje = $dia['dia_semana'] == $hoje; $feito = $eHoje && $concluidoHoje; ?>
                    <div class="col-md-6 col-xxl-4 mb-4">
                        <div class="card dia-card <?= $eHoje ? 'hoje' : '' ?> <?= $feito ? 'feito' : '' ?> <?= $dia['descanso'] ? 'descanso' : '' ?>">
                            <div class="card-body">
                                <?php if ($feito): ?>
                                    <span class="badge text-bg-success float-end"><i class="bi bi-check-lg"></i> FEITO</span>
                                <?php elseif ($eHoje): ?>
                                    <span class="badge <?= e($cfg['badge']) ?> float-end">HOJE</span>
                                <?php endif; ?>
                                <h4><?= e(DIAS_SEMANA[$dia['dia_semana']]) ?></h4>
                                <div class="treino-titulo">
                                    <?php if ($dia['descanso']): ?><i class="bi bi-moon"></i><?php endif; ?>
                                    <?= e($dia['titulo']) ?>
                                </div>
                                <?= lista_conteudo($dia['conteudo']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($plano): ?>
                <div class="mt-4 text-center">
                    <form method="post" action="concluir.php">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
                        <input type="hidden" name="voltar" value="plano.php">
                        <?php if ($concluidoHoje): ?>
                            <p class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Hoje já está concluído!</p>
                            <button type="submit" name="desmarcar" value="1" class="btn btn-sm btn-outline-secondary">Desmarcar</button>
                        <?php elseif ($tipo === 'alimentacao'): ?>
                            <button type="submit" class="btn btn-lg <?= e($cfg['botao']) ?>">
                                <i class="bi bi-check2-circle"></i> <?= e($cfg['acao']) ?>
                            </button>
                        <?php elseif ($diaHoje && $diaHoje['descanso']): ?>
                            <p class="text-muted"><i class="bi bi-moon"></i> Hoje é dia de descanso.</p>
                        <?php else: ?>
                            <button type="button" class="btn btn-lg <?= e($cfg['botao']) ?>" data-bs-toggle="modal" data-bs-target="#modalConcluir">
                                <i class="bi bi-check2-circle"></i> <?= e($cfg['acao']) ?>
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($plano && $tipo !== 'alimentacao' && !$concluidoHoje): ?>
                <!-- Modal - concluir treino (regista também a atividade) -->
                <div class="modal fade" id="modalConcluir" tabindex="-1" aria-labelledby="modalConcluirLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-dark text-white">
                                <h5 class="modal-title" id="modalConcluirLabel">
                                    <i class="bi bi-check2-circle"></i> <?= e($diaHoje['titulo'] ?? 'Treino de hoje') ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <?php $tipoPlano = $tipo; $voltarPara = 'plano.php'; require 'includes/form_concluir.php'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
<?php require 'includes/footer.php'; ?>
