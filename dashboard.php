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

// Dias desta semana com pelo menos uma atividade registada (para os círculos "Esta semana")
[$inicioSemana, $fimSemana] = semana_atual();
$stmt = db()->prepare('SELECT DISTINCT data FROM atividades WHERE utilizador_id = ? AND data BETWEEN ? AND ?');
$stmt->execute([$u['id'], $inicioSemana, $fimSemana]);
$diasComTreino = array_map(fn($d) => (int) date('N', strtotime($d)), $stmt->fetchAll(PDO::FETCH_COLUMN));
$refeicoesHoje = dia_do_plano($u['plano_alimentacao_id'], (int) date('N'));

// Progresso do peso: da primeira pesagem até ao objetivo
$pesoInicial = $pesagens ? (float) $pesagens[0]['peso'] : null;
$progressoPeso = progresso($pesoInicial, $u['peso_atual'], $u['peso_objetivo']);
$gorduras = array_values(array_filter(array_column($pesagens, 'massa_gorda'), fn($g) => $g !== null));
$progressoGordura = progresso($gorduras ? (float) $gorduras[0] : null, $u['massa_gorda'], $u['massa_gorda_objetivo']);
$pctKm = $u['meta_km_semana'] > 0 ? min(100, round($semana['km_corrida'] / $u['meta_km_semana'] * 100)) : 0;
$pctTreinos = $u['meta_treinos_semana'] > 0 ? min(100, round($semana['atividades'] / $u['meta_treinos_semana'] * 100)) : 0;

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

/**
 * Percentagem (0-100) do caminho já feito entre o valor inicial e o objetivo.
 * Devolve null se faltar algum dos valores.
 */
function progresso($inicial, $atual, $objetivo): ?int
{
    if ($inicial === null || $atual === null || $objetivo === null) {
        return null;
    }
    $total = (float) $inicial - (float) $objetivo;
    if (abs($total) < 0.05) {
        return abs((float) $atual - (float) $objetivo) < 0.05 ? 100 : 0;
    }
    return (int) max(0, min(100, round(((float) $inicial - (float) $atual) / $total * 100)));
}

/** Divide "Agachamento 4×8-10" em ["Agachamento", "4×8-10"] e "Almoço: frango" em ["Almoço", "frango"]. */
function partir_linha(string $linha, string $tipo): array
{
    if ($tipo === 'exercicio' && preg_match('/^(.+?)\s+(\d+\s*[×x].*)$/u', $linha, $m)) {
        return [$m[1], $m[2]];
    }
    if ($tipo === 'refeicao' && preg_match('/^([^:]{2,25}):\s*(.+)$/u', $linha, $m)) {
        return [$m[1], $m[2]];
    }
    return [$linha, ''];
}

/** Linhas não vazias do conteúdo de um dia do plano. */
function linhas_plano(?array $dia): array
{
    return $dia ? array_values(array_filter(array_map('trim', preg_split('/\R/', trim($dia['conteudo']))))) : [];
}

/** Mostra um valor ou "—" se ainda não foi preenchido. */
function valor_ou_traco($v, string $sufixo): string
{
    return $v === null ? '—' : numero($v) . $sufixo;
}
?>
        <!-- Cabeçalho -->
        <div class="pagina-topo">
            <div>
                <p class="pagina-data"><?= e(data_extenso()) ?></p>
                <h1>Olá, <?= e(strtok($u['nome'], ' ')) ?></h1>
            </div>
            <a href="inserir_atividade.php" class="btn btn-contorno d-none d-lg-inline-flex"><i class="bi bi-plus-lg"></i> Registar atividade</a>
            <a href="definicoes.php" class="avatar avatar-lg d-lg-none" title="Configurações"><?= e(iniciais($u['nome'])) ?></a>
        </div>

        <div class="row g-3 g-lg-4">
            <!-- Treino de hoje -->
            <div class="col-lg-8">
                <section class="cartao-destaque h-100">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <span class="rotulo">Treino de hoje</span>
                        <?php if ($treinoHoje): ?><span class="destaque-info text-truncate small"><?= e($treinoHoje['plano_nome']) ?></span><?php endif; ?>
                    </div>
                    <?php if (!$treinoHoje): ?>
                        <h2>Sem plano de musculação</h2>
                        <p class="destaque-info">Escolhe um plano para veres aqui o treino de cada dia.</p>
                        <a href="plano.php?tipo=musculacao" class="btn btn-gold btn-destaque">Escolher plano <i class="bi bi-arrow-right"></i></a>
                    <?php else: $exercicios = linhas_plano($treinoHoje); ?>
                        <h2><?= e($treinoHoje['titulo']) ?></h2>
                        <p class="destaque-info">
                            <?= $treinoHoje['descanso'] ? 'Dia de descanso' : count($exercicios) . ' exercícios' ?>
                            · <?= e(DIAS_SEMANA[(int) date('N')]) ?>
                        </p>
                        <div class="row g-2 mb-3 mb-lg-4 d-none d-sm-flex">
                            <div class="col-4"><div class="mini-tile"><small>Corrida hoje</small><strong class="text-truncate d-block"><?= $corridaHoje ? e($corridaHoje['titulo']) : '—' ?></strong></div></div>
                            <div class="col-4"><div class="mini-tile"><small>Esta semana</small><strong><?= e(formatar_duracao((int) $semana['minutos'])) ?></strong></div></div>
                            <div class="col-4"><div class="mini-tile"><small>Estado</small><strong class="<?= $musculacaoFeita ? 'text-gold' : '' ?>"><?= $musculacaoFeita ? 'Feito' : ($treinoHoje['descanso'] ? 'Descanso' : 'Por fazer') ?></strong></div></div>
                        </div>
                        <?php if ($musculacaoFeita): ?>
                            <a href="plano.php?tipo=musculacao" class="btn btn-destaque btn-feito"><i class="bi bi-check-lg"></i> Treino feito</a>
                        <?php else: ?>
                            <a href="plano.php?tipo=musculacao" class="btn btn-gold btn-destaque">Ver treino <i class="bi bi-arrow-right"></i></a>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Peso e metas da semana -->
            <div class="col-lg-4">
                <section class="cartao h-100">
                    <h3 class="cartao-titulo d-none d-lg-block">Objetivos</h3>
                    <div class="metas">
                        <?php $p = $progressoPeso ?? 0; ?>
                        <div class="anel" style="--p: <?= $p ?>;" role="img" aria-label="Progresso do peso: <?= $p ?>%">
                            <svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="52" class="anel-fundo"/><?php if ($p > 0): ?><circle cx="60" cy="60" r="52" class="anel-valor" pathLength="100"/><?php endif; ?></svg>
                            <div class="anel-texto">
                                <?php if ($faltaPeso === null): ?><strong>—</strong><small>sem objetivo</small>
                                <?php elseif (abs($faltaPeso) < 0.05): ?><strong><i class="bi bi-check-lg"></i></strong><small>objetivo</small>
                                <?php else: ?><strong><?= numero(abs($faltaPeso)) ?></strong><small>kg a <?= $faltaPeso > 0 ? 'perder' : 'ganhar' ?></small><?php endif; ?>
                            </div>
                        </div>
                        <div class="metas-barras">
                            <div class="d-none d-lg-block mb-3">
                                <span class="valor-grande"><?= valor_ou_traco($u['peso_atual'], ' kg') ?></span>
                                <small class="text-muted d-block">objetivo <?= valor_ou_traco($u['peso_objetivo'], ' kg') ?></small>
                            </div>
                            <?php foreach ([
                                ['Massa gorda', valor_ou_traco($u['massa_gorda'], '%') . ' / ' . valor_ou_traco($u['massa_gorda_objetivo'], '%'), $progressoGordura ?? 0, 'barra-escura'],
                                ['KMs corridos', numero($semana['km_corrida']) . ' / ' . numero($u['meta_km_semana']) . ' km', $pctKm, 'barra-gold'],
                                ['Treinos', (int) $semana['atividades'] . ' / ' . (int) $u['meta_treinos_semana'], $pctTreinos, 'barra-suave'],
                            ] as [$rotulo, $texto, $pct, $classe]): ?>
                                <div class="meta">
                                    <div class="d-flex justify-content-between"><strong><?= e($rotulo) ?></strong><span class="text-muted"><?= e($texto) ?></span></div>
                                    <div class="barra" role="progressbar" aria-label="<?= e($rotulo) ?>" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"><span class="<?= $classe ?>" style="width: <?= $pct ?>%;"></span></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Exercícios de hoje -->
            <div class="col-lg-4 order-2 order-lg-0">
                <section class="cartao h-100">
                    <div class="cartao-cabecalho">
                        <h3 class="cartao-titulo">Exercícios</h3>
                        <a href="plano.php?tipo=musculacao" class="link-gold">Ver plano</a>
                    </div>
                    <?php $linhas = linhas_plano($treinoHoje); ?>
                    <?php if (!$linhas): ?>
                        <p class="text-muted mb-0">Sem plano de musculação escolhido.</p>
                    <?php else: ?>
                        <ul class="lista-linhas">
                            <?php foreach ($linhas as $linha): [$nome, $detalhe] = partir_linha($linha, 'exercicio'); ?>
                                <li><span><?= e($nome) ?></span><span class="text-muted text-nowrap"><?= e($detalhe) ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Refeições de hoje -->
            <div class="col-lg-4 order-2 order-lg-0">
                <section class="cartao h-100">
                    <div class="cartao-cabecalho">
                        <h3 class="cartao-titulo">Refeições</h3>
                        <a href="plano.php?tipo=alimentacao" class="link-gold">Ver plano</a>
                    </div>
                    <?php $linhas = linhas_plano($refeicoesHoje); ?>
                    <?php if (!$linhas): ?>
                        <p class="text-muted mb-0">Sem plano alimentar escolhido.</p>
                    <?php else: ?>
                        <ul class="lista-linhas lista-refeicoes">
                            <?php foreach ($linhas as $linha): [$refeicao, $prato] = partir_linha($linha, 'refeicao'); ?>
                                <li><span><?php if ($prato !== ''): ?><small class="text-muted d-block"><?= e($refeicao) ?></small><?= e($prato) ?><?php else: ?><?= e($refeicao) ?><?php endif; ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Esta semana + incentivo -->
            <div class="col-lg-4 d-flex flex-column gap-3 gap-lg-4">
                <section class="cartao">
                    <div class="cartao-cabecalho">
                        <h3 class="cartao-titulo">Esta semana</h3>
                        <span class="text-muted"><?= (int) $semana['atividades'] ?> de <?= (int) $u['meta_treinos_semana'] ?> treinos</span>
                    </div>
                    <ol class="semana">
                        <?php foreach (['S', 'T', 'Q', 'Q', 'S', 'S', 'D'] as $i => $letra): $dia = $i + 1;
                            $feito = in_array($dia, $diasComTreino, true); $hoje = $dia === (int) date('N'); ?>
                            <li class="<?= $hoje ? 'hoje' : '' ?>">
                                <small><?= $letra ?></small>
                                <span class="bola <?= $feito ? 'feito' : '' ?>" title="<?= e(DIAS_SEMANA[$dia]) ?><?= $feito ? ': treino registado' : '' ?>"><?php if ($feito): ?><i class="bi bi-check-lg"></i><?php endif; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </section>
                <section class="cartao cartao-incentivo flex-grow-1">
                    <h3 class="cartao-titulo"><i class="bi bi-stars"></i> Incentivo</h3>
                    <p class="mb-0"><?= e($frase) ?></p>
                </section>
            </div>
        </div>

        <!-- Os meus planos para hoje -->
        <div class="card card-custom mt-3 mt-lg-4">
            <div class="card-body">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo">Os meus planos para hoje</h3>
                    <a href="meus_planos.php" class="link-gold"><i class="bi bi-plus"></i> Planear</a>
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
        <div class="row g-3 g-lg-4 mt-0" id="peso">
            <div class="col-lg-8">
                <div class="card card-custom h-100">
                    <div class="card-body">
                        <h3 class="cartao-titulo mb-3">Evolução do peso</h3>
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
                        <h3 class="cartao-titulo mb-3">Registar pesagem</h3>
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

<?php
if (count($pesagens) >= 2) {
    $scripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>'
        . '<script>const DADOS_PESO = ' . json_encode($graficoPeso) . ';</script>'
        . '<script src="assets/js/grafico_peso.js"></script>';
}
require 'includes/footer.php';
