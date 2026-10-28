<?php
require_once 'includes/funcoes.php';

$titulo = 'Início';
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

// Treino de hoje (o primeiro por fazer: musculação, corrida, ciclismo, natação) e o plano de musculação do dia
$treinoHoje = null;
foreach (['musculacao', 'corrida', 'ciclismo', 'natacao'] as $tipo) {
    $t = treino_de_hoje($u, $tipo);
    if ($t && (!$treinoHoje || ($treinoHoje['feito'] && !$t['feito']))) {
        $treinoHoje = $t;
    }
}
$musculacaoHoje = dia_do_plano($u['plano_musculacao_id'], (int) date('N'));
$corridaHoje = dia_do_plano($u['plano_corrida_id'], (int) date('N'));

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

/** Mostra um valor ou "—" se ainda não foi preenchido. */
function valor_ou_traco($v, string $sufixo): string
{
    return $v === null ? '—' : numero($v) . $sufixo;
}

ob_start(); ?>
<a href="atividades.php?registar=1" class="btn btn-secundario d-none d-md-inline-flex"><i class="bi bi-plus-lg" aria-hidden="true"></i> Registar atividade</a>
<?php topo_pagina('Olá, ' . strtok($u['nome'], ' '), '', ob_get_clean(), data_extenso()); ?>

        <div class="row g-3">
            <!-- Treino de hoje -->
            <div class="col-lg-8">
                <section class="cartao cartao-escuro h-100 d-flex flex-column" aria-labelledby="tituloTreinoHoje">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <span class="rotulo">Treino de hoje</span>
                        <?php if ($treinoHoje): ?><span class="sub small text-truncate"><?= e($treinoHoje['plano_nome'] ?? 'Plano pessoal') ?></span><?php endif; ?>
                    </div>
                    <?php if (!$treinoHoje): ?>
                        <h2 class="titulo-hoje" id="tituloTreinoHoje"><?= $musculacaoHoje || $corridaHoje ? 'Dia de descanso' : 'Sem treino planeado' ?></h2>
                        <p class="sub mb-3"><?= $musculacaoHoje || $corridaHoje ? 'Aproveita para recuperar.' : 'Escolhe um plano de treino para veres aqui o treino de cada dia.' ?></p>
                        <div class="mt-auto"><a href="plano_treino.php" class="btn btn-principal">Ver plano de treino <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
                    <?php else: $itens = linhas_plano($treinoHoje['conteudo']); ?>
                        <h2 class="titulo-hoje" id="tituloTreinoHoje"><?= e($treinoHoje['titulo']) ?></h2>
                        <p class="sub mb-3">
                            <?= e(TIPOS_ATIVIDADE[$treinoHoje['tipo']][0]) ?>
                            <?php if ($treinoHoje['tipo'] === 'musculacao' && $itens): ?> · <?= count($itens) ?> exercícios<?php endif; ?>
                            <?php if ($treinoHoje['duracao_min']): ?> · cerca de <?= (int) $treinoHoje['duracao_min'] ?> min<?php endif; ?>
                        </p>
                        <div class="row g-2 mb-3 d-none d-sm-flex">
                            <div class="col-4"><div class="mini"><small>Corrida hoje</small><strong class="text-truncate d-block"><?= $corridaHoje ? e($corridaHoje['titulo']) : '—' ?></strong></div></div>
                            <div class="col-4"><div class="mini"><small>Esta semana</small><strong><?= e(formatar_duracao((int) $semana['minutos'])) ?></strong></div></div>
                            <div class="col-4"><div class="mini"><small>Estado</small><strong><?= $treinoHoje['feito'] ? 'Feito' : 'Por fazer' ?></strong></div></div>
                        </div>
                        <div class="mt-auto">
                            <?php if ($treinoHoje['feito']): ?>
                                <span class="etiqueta etiqueta-feito"><i class="bi bi-check-lg" aria-hidden="true"></i> Treino de hoje feito</span>
                            <?php else: ?>
                                <a href="atividades.php?registar=hoje&amp;modalidade=<?= e($treinoHoje['tipo']) ?>" class="btn btn-principal btn-grande">Começar treino <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Objetivos: peso e metas da semana -->
            <div class="col-lg-4">
                <section class="cartao h-100" aria-labelledby="tituloObjetivos">
                    <h2 class="cartao-titulo mb-3" id="tituloObjetivos">Objetivos</h2>
                    <div class="metas">
                        <?php $p = $progressoPeso ?? 0; ?>
                        <div class="anel" style="--p: <?= $p ?>;" role="img" aria-label="Progresso do peso: <?= $p ?>%">
                            <svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="52" class="anel-fundo"/><?php if ($p > 0): ?><circle cx="60" cy="60" r="52" class="anel-valor" pathLength="100"/><?php endif; ?></svg>
                            <div class="anel-texto">
                                <?php if ($faltaPeso === null): ?><strong>—</strong><small>sem objetivo</small>
                                <?php elseif (abs($faltaPeso) < 0.05): ?><strong><i class="bi bi-check-lg" aria-hidden="true"></i></strong><small>objetivo</small>
                                <?php else: ?><strong><?= numero(abs($faltaPeso)) ?></strong><small>kg a <?= $faltaPeso > 0 ? 'perder' : 'ganhar' ?></small><?php endif; ?>
                            </div>
                        </div>
                        <div class="metas-barras">
                            <div>
                                <span class="numero-grande d-block"><?= valor_ou_traco($u['peso_atual'], ' kg') ?></span>
                                <small class="text-muted">objetivo <?= valor_ou_traco($u['peso_objetivo'], ' kg') ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-3 mt-3">
                        <?php foreach ([
                            ['Massa gorda', valor_ou_traco($u['massa_gorda'], '%') . ' / ' . valor_ou_traco($u['massa_gorda_objetivo'], '%'), $progressoGordura ?? 0, ''],
                            ['Km corridos', numero($semana['km_corrida']) . ' / ' . numero($u['meta_km_semana']) . ' km', $pctKm, 'barra-2'],
                            ['Treinos', (int) $semana['atividades'] . ' / ' . (int) $u['meta_treinos_semana'], $pctTreinos, 'barra-3'],
                        ] as [$rotulo, $texto, $pct, $classe]): ?>
                            <div>
                                <div class="meta-linha"><strong><?= e($rotulo) ?></strong><span class="text-muted"><?= e($texto) ?></span></div>
                                <div class="barra <?= $classe ?>" role="progressbar" aria-label="<?= e($rotulo) ?>" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"><span style="width: <?= $pct ?>%;"></span></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <!-- Exercícios de hoje -->
            <div class="col-md-6 col-lg-4 order-2 order-lg-0">
                <section class="cartao h-100" aria-labelledby="tituloExercicios">
                    <div class="cartao-cabecalho">
                        <h2 class="cartao-titulo" id="tituloExercicios">Exercícios</h2>
                        <a href="plano_treino.php?modalidade=musculacao" class="link-acao">Ver plano</a>
                    </div>
                    <?php $linhas = $musculacaoHoje ? linhas_plano($musculacaoHoje['conteudo']) : []; ?>
                    <?php if (!$musculacaoHoje): ?>
                        <p class="text-muted mb-0">Sem plano de musculação escolhido.</p>
                    <?php elseif ($musculacaoHoje['descanso']): ?>
                        <p class="text-muted mb-0">Hoje é dia de descanso: <?= e(implode(', ', $linhas)) ?>.</p>
                    <?php else: ?>
                        <ul class="lista-linhas">
                            <?php foreach ($linhas as $linha): [$nome, $detalhe] = partir_linha($linha, 'exercicio'); ?>
                                <li><span class="nome"><?= e($nome) ?></span><span class="text-muted text-nowrap"><?= e($detalhe) ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Refeições de hoje -->
            <div class="col-md-6 col-lg-4 order-2 order-lg-0">
                <section class="cartao h-100" aria-labelledby="tituloRefeicoes">
                    <div class="cartao-cabecalho">
                        <h2 class="cartao-titulo" id="tituloRefeicoes">Refeições</h2>
                        <a href="plano_alimentar.php" class="link-acao">Ver plano</a>
                    </div>
                    <?php $linhas = $refeicoesHoje ? linhas_plano($refeicoesHoje['conteudo']) : []; ?>
                    <?php if (!$linhas): ?>
                        <p class="text-muted mb-0">Sem plano alimentar escolhido.</p>
                    <?php else: ?>
                        <ul class="lista-linhas">
                            <?php foreach ($linhas as $linha): [$refeicao, $prato] = partir_linha($linha, 'refeicao'); ?>
                                <li><span><?php if ($prato !== ''): ?><small class="text-muted d-block"><?= e($refeicao) ?></small><span class="nome"><?= e($prato) ?></span><?php else: ?><?= e($refeicao) ?><?php endif; ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Esta semana + incentivo -->
            <div class="col-lg-4 d-flex flex-column gap-3">
                <section class="cartao" aria-labelledby="tituloSemanaInicio">
                    <div class="cartao-cabecalho">
                        <h2 class="cartao-titulo" id="tituloSemanaInicio">Esta semana</h2>
                        <span class="text-muted small"><?= (int) $semana['atividades'] ?> de <?= (int) $u['meta_treinos_semana'] ?> treinos</span>
                    </div>
                    <ol class="semana">
                        <?php foreach (['S', 'T', 'Q', 'Q', 'S', 'S', 'D'] as $i => $letra): $dia = $i + 1;
                            $feito = in_array($dia, $diasComTreino, true); $eHoje = $dia === (int) date('N'); ?>
                            <li class="<?= $eHoje ? 'hoje' : '' ?>">
                                <small aria-hidden="true"><?= $letra ?></small>
                                <span class="bola <?= $feito ? 'feito' : '' ?>"><?php if ($feito): ?><i class="bi bi-check-lg" aria-hidden="true"></i><?php endif; ?>
                                    <span class="visually-hidden"><?= e(DIAS_SEMANA[$dia]) ?><?= $eHoje ? ' (hoje)' : '' ?>: <?= $feito ? 'treino registado' : 'sem treino' ?></span></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </section>
                <section class="cartao cartao-dica" aria-labelledby="tituloIncentivo">
                    <h2 class="cartao-titulo mb-2" id="tituloIncentivo"><i class="bi bi-stars" aria-hidden="true"></i> Incentivo</h2>
                    <p class="mb-0"><?= e($frase) ?></p>
                </section>
            </div>
        </div>

        <!-- Planos pessoais de hoje -->
        <?php if ($meusPlanosHoje): ?>
            <section class="cartao mt-3" aria-labelledby="tituloPessoaisHoje">
                <div class="cartao-cabecalho">
                    <h2 class="cartao-titulo" id="tituloPessoaisHoje">Os teus planos para hoje</h2>
                </div>
                <ul class="lista-linhas">
                    <?php foreach ($meusPlanosHoje as $p):
                        $link = $p['categoria'] === 'treino' ? 'plano_treino.php?modalidade=' . $p['modalidade'] : 'plano_alimentar.php';
                        $tipoTexto = $p['categoria'] === 'treino' ? TIPOS_ATIVIDADE[$p['modalidade']][0] ?? 'Treino' : (REFEICOES[$p['refeicao']] ?? 'Alimentação'); ?>
                        <li>
                            <a href="<?= e($link) ?>#dia-<?= e($p['data']) ?>" class="nome <?= $p['concluido'] ? 'text-decoration-line-through' : '' ?>"><?= e($p['titulo']) ?></a>
                            <span class="d-flex gap-2 align-items-center"><span class="text-muted small"><?= e($tipoTexto) ?></span>
                                <?php if ($p['concluido']): ?><span class="etiqueta etiqueta-feito">Feito</span><?php else: ?><span class="etiqueta etiqueta-pessoal">Pessoal</span><?php endif; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <!-- Peso -->
        <div class="row g-3 mt-0" id="peso">
            <div class="col-lg-8">
                <section class="cartao h-100" aria-labelledby="tituloPeso">
                    <h2 class="cartao-titulo mb-3" id="tituloPeso">Evolução do peso</h2>
                    <?php if (count($pesagens) >= 2): ?>
                        <div class="grafico-caixa" style="height: 280px;">
                            <canvas id="graficoPeso" aria-label="Gráfico da evolução do peso" role="img"></canvas>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Regista pelo menos duas pesagens para veres o gráfico.</p>
                    <?php endif; ?>
                </section>
            </div>
            <div class="col-lg-4">
                <section class="cartao h-100" aria-labelledby="tituloPesagem">
                    <h2 class="cartao-titulo mb-3" id="tituloPesagem">Registar pesagem</h2>
                    <form method="post" action="pesagem.php" class="row g-3">
                        <?= csrf_campo() ?>
                        <div class="col-12">
                            <label class="form-label" for="p_data">Data</label>
                            <input type="date" class="form-control" id="p_data" name="data" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="p_peso">Peso</label>
                            <div class="campo-unidade">
                                <input type="number" step="0.1" min="30" max="300" class="form-control" id="p_peso" name="peso" value="<?= e($u['peso_atual']) ?>" required>
                                <span class="unidade">kg</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="p_gordura">Massa gorda</label>
                            <div class="campo-unidade">
                                <input type="number" step="0.1" min="3" max="60" class="form-control" id="p_gordura" name="massa_gorda" placeholder="opcional">
                                <span class="unidade">%</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-principal w-100"><i class="bi bi-check-lg" aria-hidden="true"></i> Guardar pesagem</button>
                        </div>
                    </form>

                    <?php if ($pesagens): ?>
                        <ul class="lista-linhas mt-3">
                            <?php foreach (array_slice(array_reverse($pesagens), 0, 4) as $p): ?>
                                <li class="align-items-center">
                                    <span><?= e(data_curta($p['data'])) ?></span>
                                    <span class="d-flex align-items-center gap-1">
                                        <strong><?= numero($p['peso']) ?> kg</strong>
                                        <?php if ($p['massa_gorda'] !== null): ?><span class="text-muted">· <?= numero($p['massa_gorda']) ?>%</span><?php endif; ?>
                                        <form method="post" action="pesagem.php" class="m-0" onsubmit="return confirm('Apagar esta pesagem?');">
                                            <?= csrf_campo() ?>
                                            <button type="submit" name="apagar" value="<?= (int) $p['id'] ?>" class="btn btn-icone perigo" aria-label="Apagar pesagem de <?= e(data_curta($p['data'])) ?>"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                                        </form>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>
        </div>
<?php
if (count($pesagens) >= 2) {
    $scripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>'
        . '<script>const DADOS_PESO = ' . json_encode($graficoPeso) . ';</script>'
        . '<script src="assets/js/grafico_peso.js"></script>';
}
require 'includes/footer.php';
