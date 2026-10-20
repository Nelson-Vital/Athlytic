<?php
require_once 'includes/funcoes.php';

$titulo = 'Dashboard';
$pagina = 'dashboard';
require 'includes/header.php';

$semana = estatisticas_semana($u['id']);

// Diferença para os objetivos (só se o utilizador os tiver preenchido)
$faltaPeso = ($u['peso_atual'] !== null && $u['peso_objetivo'] !== null) ? $u['peso_atual'] - $u['peso_objetivo'] : null;
$faltaGordura = ($u['massa_gorda'] !== null && $u['massa_gorda_objetivo'] !== null) ? $u['massa_gorda'] - $u['massa_gorda_objetivo'] : null;

// Histórico de peso (para o gráfico e a lista)
$stmt = db()->prepare('SELECT id, data, peso, massa_gorda FROM pesagens WHERE utilizador_id = ? ORDER BY data');
$stmt->execute([$u['id']]);
$pesagens = $stmt->fetchAll();
$graficoPeso = [
    'datas'   => array_map(fn($p) => data_curta($p['data']), $pesagens),
    'peso'    => array_map(fn($p) => (float) $p['peso'], $pesagens),
    'gordura' => array_map(fn($p) => $p['massa_gorda'] === null ? null : (float) $p['massa_gorda'], $pesagens),
    'objetivo'=> $u['peso_objetivo'] === null ? null : (float) $u['peso_objetivo'],
];

// Planos que o próprio utilizador criou para hoje
limpar_planos_antigos();
$stmt = db()->prepare('SELECT * FROM planos_pessoais WHERE utilizador_id = ? AND data = CURDATE() ORDER BY categoria DESC, id');
$stmt->execute([$u['id']]);
$meusPlanosHoje = $stmt->fetchAll();

$treinoHoje = dia_do_plano($u['plano_musculacao_id'], (int) date('N'));
$corridaHoje = dia_do_plano($u['plano_corrida_id'], (int) date('N'));
// Planos (corrida/musculação) já marcados como feitos hoje
$stmt = db()->prepare('SELECT plano_id FROM plano_concluidos WHERE utilizador_id = ? AND data = CURDATE()');
$stmt->execute([$u['id']]);
$planosFeitosHoje = $stmt->fetchAll(PDO::FETCH_COLUMN);
$musculacaoFeita = $u['plano_musculacao_id'] && in_array($u['plano_musculacao_id'], $planosFeitosHoje);
$corridaFeita = $u['plano_corrida_id'] && in_array($u['plano_corrida_id'], $planosFeitosHoje);

$frases = [
    'Cada treino que fazes hoje aproxima-te do corpo e da disciplina que queres amanhã. Continua.',
    'A consistência vence a motivação. Aparece, mesmo nos dias difíceis.',
    'Não tens de ser extremo, só tens de ser consistente.',
    'O único treino mau é aquele que não fizeste.',
    'Pequenos progressos todos os dias somam grandes resultados.',
    'O teu único adversário é quem foste ontem.',
    'Descansar também é treinar. Respeita a recuperação.',
    'O corpo alcança o que a mente acredita.',
    'Não pares quando estiveres cansado. Para quando tiveres terminado.',
    'Daqui a um ano vais agradecer ter começado hoje.',
    'Suor é só gordura a chorar. Mais uma série!',
    'A disciplina é fazer o que tem de ser feito, mesmo quando não apetece.',
    'Cada quilómetro conta. Cada repetição conta. Tu contas.',
    'Os resultados não aparecem de um dia para o outro, mas aparecem.',
    'Comer bem é uma forma de respeito pelo teu corpo.',
    'Hoje é um bom dia para bater o teu recorde.',
    'Não procures desculpas, procura resultados.',
    'A dor de hoje é a força de amanhã.',
    'Mais vale devagar do que parado.',
    'Acredita no processo. O progresso vem com o tempo.',
];
// Uma frase ao acaso sempre que a página abre, sem repetir a da última visita
do {
    $indiceFrase = random_int(0, count($frases) - 1);
} while ($indiceFrase === ($_SESSION['ultima_frase'] ?? null));
$_SESSION['ultima_frase'] = $indiceFrase;
$frase = $frases[$indiceFrase];

/** Mostra um valor ou "—" se ainda não foi preenchido. */
function valor_ou_traco($v, string $sufixo): string
{
    return $v === null ? '—' : numero($v) . $sufixo;
}
?>
        <h1 class="mb-4"><i class="bi bi-graph-up-arrow text-primary"></i> Resumo da Tua Performance</h1>

        <div class="alert alert-primary" role="alert">
            <strong>Bem-vindo de volta, <?= e(strtok($u['nome'], ' ')) ?>!</strong> Hoje é <?= e(data_extenso()) ?>
        </div>

        <!-- Primeira linha de stats -->
        <div class="row g-4">
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <h5><i class="bi bi-trophy text-warning"></i> Peso Objetivo</h5>
                    <h2><?= valor_ou_traco($u['peso_objetivo'], ' kg') ?></h2>
                    <p class="text-muted mb-0">
                        <?php if ($faltaPeso === null): ?>Define em Configurações
                        <?php elseif ($faltaPeso > 0): ?>Faltam perder <?= numero($faltaPeso) ?> kg
                        <?php elseif ($faltaPeso < 0): ?>Faltam ganhar <?= numero(-$faltaPeso) ?> kg
                        <?php else: ?>Objetivo atingido! 🎉<?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <h5><i class="bi bi-droplet-half text-info"></i> % Massa Gorda Objetivo</h5>
                    <h2><?= valor_ou_traco($u['massa_gorda_objetivo'], '%') ?></h2>
                    <p class="text-muted mb-0">
                        <?php if ($faltaGordura === null): ?>Define em Configurações
                        <?php elseif ($faltaGordura > 0): ?>Faltam <?= numero($faltaGordura) ?>%
                        <?php else: ?>Objetivo atingido! 🎉<?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <h5><i class="bi bi-calendar-check text-success"></i> Treinos Esta Semana</h5>
                    <h2><?= (int) $semana['atividades'] ?></h2>
                    <p class="text-muted mb-0">Meta semanal: <?= (int) $u['meta_treinos_semana'] ?></p>
                </div>
            </div>
        </div>

        <!-- Segunda linha de stats -->
        <div class="row g-4 mt-1">
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <h5><i class="bi bi-speedometer2 text-danger"></i> Peso Atual</h5>
                    <h2><?= valor_ou_traco($u['peso_atual'], ' kg') ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <h5><i class="bi bi-percent text-primary"></i> % Massa Gorda Atual</h5>
                    <h2><?= valor_ou_traco($u['massa_gorda'], '%') ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <h5><i class="bi bi-geo-alt-fill text-success"></i> KMs Corridos Esta Semana</h5>
                    <h2><?= numero($semana['km_corrida']) ?> km</h2>
                    <p class="text-muted mb-0">Meta: <?= numero($u['meta_km_semana']) ?> km</p>
                    <?php $pct = $u['meta_km_semana'] > 0 ? min(100, round($semana['km_corrida'] / $u['meta_km_semana'] * 100)) : 0; ?>
                    <div class="progress mt-2" style="height: 8px;" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-success" style="width: <?= $pct ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plano de hoje -->
        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-trophy-fill text-danger"></i> Musculação hoje</h5>
                        <?php if ($treinoHoje): ?>
                            <p class="fw-bold mb-1"><?= e($treinoHoje['titulo']) ?><?php if ($musculacaoFeita): ?> <span class="badge text-bg-success ms-1"><i class="bi bi-check-lg"></i> Feito</span><?php endif; ?></p>
                            <small class="text-muted"><?= e($treinoHoje['plano_nome']) ?></small>
                        <?php else: ?>
                            <p class="text-muted mb-0">Ainda não escolheste um plano.</p>
                        <?php endif; ?>
                        <a href="plano.php?tipo=musculacao" class="stretched-link"></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-stopwatch text-success"></i> Corrida hoje</h5>
                        <?php if ($corridaHoje): ?>
                            <p class="fw-bold mb-1"><?= e($corridaHoje['titulo']) ?><?php if ($corridaFeita): ?> <span class="badge text-bg-success ms-1"><i class="bi bi-check-lg"></i> Feito</span><?php endif; ?></p>
                            <small class="text-muted"><?= e($corridaHoje['plano_nome']) ?></small>
                        <?php else: ?>
                            <p class="text-muted mb-0">Ainda não escolheste um plano.</p>
                        <?php endif; ?>
                        <a href="plano.php?tipo=corrida" class="stretched-link"></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Os meus planos para hoje -->
        <div class="card card-custom mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="card-title mb-0"><i class="bi bi-calendar2-week text-primary"></i> Os meus planos para hoje</h5>
                    <a href="meus_planos.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus"></i> Planear</a>
                </div>
                <?php if (!$meusPlanosHoje): ?>
                    <p class="text-muted mb-0">Não tens planos teus para hoje.</p>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($meusPlanosHoje as $p):
                            [$nomeP, $iconeP, $corP] = $p['modalidade'] ? tipo_info($p['modalidade']) : array_merge(CATEGORIAS_PLANO[$p['categoria']], ['text-success']); ?>
                            <li class="py-1 <?= $p['concluido'] ? 'text-decoration-line-through text-muted' : '' ?>">
                                <i class="bi <?= $p['concluido'] ? 'bi-check-circle-fill text-success' : e($iconeP) . ' ' . e($corP) ?>"></i>
                                <strong><?= e($p['titulo']) ?></strong> <small class="text-muted">· <?= e($nomeP) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Histórico de peso -->
        <div class="row g-4 mt-1" id="peso">
            <div class="col-lg-8">
                <div class="card card-custom h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-graph-down text-primary"></i> Evolução do Peso</h5>
                        <?php if (count($pesagens) >= 2): ?>
                            <div style="position: relative; height: 280px;">
                                <canvas id="graficoPeso" aria-label="Gráfico da evolução do peso" role="img"></canvas>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">Regista pelo menos duas pesagens para veres o gráfico.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card card-custom h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-plus-circle text-success"></i> Registar Pesagem</h5>
                        <form method="post" action="pesagem.php" class="row g-2">
                            <?= csrf_campo() ?>
                            <div class="col-12">
                                <label class="form-label small mb-1" for="p_data">Data</label>
                                <input type="date" class="form-control" id="p_data" name="data" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1" for="p_peso">Peso (kg)</label>
                                <input type="number" step="0.1" min="30" max="300" class="form-control" id="p_peso" name="peso" value="<?= e($u['peso_atual']) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1" for="p_gordura">% M. Gorda</label>
                                <input type="number" step="0.1" min="3" max="60" class="form-control" id="p_gordura" name="massa_gorda" placeholder="opcional">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-gold w-100 mt-1"><i class="bi bi-check2"></i> Guardar</button>
                            </div>
                        </form>

                        <?php if ($pesagens): ?>
                            <ul class="list-group list-group-flush mt-3 small">
                                <?php foreach (array_slice(array_reverse($pesagens), 0, 4) as $p): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span><?= e(data_curta($p['data'])) ?></span>
                                        <span>
                                            <strong><?= numero($p['peso']) ?> kg</strong>
                                            <?php if ($p['massa_gorda'] !== null): ?><span class="text-muted">· <?= numero($p['massa_gorda']) ?>%</span><?php endif; ?>
                                            <form method="post" action="pesagem.php" class="d-inline" onsubmit="return confirm('Apagar esta pesagem?');">
                                                <?= csrf_campo() ?>
                                                <button type="submit" name="apagar" value="<?= (int) $p['id'] ?>" class="btn btn-sm btn-link text-danger p-0 ms-2" title="Apagar"><i class="bi bi-x-circle"></i></button>
                                            </form>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensagem de incentivo -->
        <div class="mt-5 motivational-box">
            <h3><i class="bi bi-heart-fill text-white"></i> Mensagem de Incentivo</h3>
            <p>"<?= e($frase) ?>"</p>
        </div>
<?php
if (count($pesagens) >= 2) {
    $scripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>'
        . '<script>const DADOS_PESO = ' . json_encode($graficoPeso) . ';</script>'
        . '<script src="assets/js/grafico_peso.js"></script>';
}
require 'includes/footer.php';
