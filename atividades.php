<?php
/**
 * Atividades: junta o registo de atividades e o histórico.
 * - resumo da semana, evolução da modalidade principal e recorde de ritmo;
 * - gráfico "Distância por treino" e evolução semanal por modalidade;
 * - lista de todas as atividades com filtro e páginas (assets/js/historico_lista.js);
 * - painel "Registar atividade" (assets/js/painel.js e form_atividade.js).
 *
 * ?registar=1 abre o painel; ?registar=hoje&modalidade=corrida abre-o preenchido com o treino de hoje.
 */
require_once 'includes/funcoes.php';
requer_login();
$u = utilizador_atual();

$erros = [];
$d = atividade_vazia($u);
$abrirPainel = isset($_GET['registar']);
$preenchido = null;   // treino de hoje usado para preencher o formulário

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    // Apagar uma atividade
    if (isset($_POST['apagar'])) {
        $id = (int) $_POST['apagar'];
        $ficheiros = ficheiros_de_atividades($u['id'], $id);
        $stmt = db()->prepare('DELETE FROM atividades WHERE id = ? AND utilizador_id = ?');
        $stmt->execute([$id, $u['id']]);
        if ($stmt->rowCount()) {
            apagar_ficheiros($ficheiros);
            flash('success', 'Atividade apagada.');
        }
        redirecionar('atividades.php');
    }

    // Registar uma atividade
    $d = validar_atividade($_POST, $erros);

    // Veio de "Registar este treino": o treino de hoje fica também marcado como feito
    $hoje = null;
    $modalidadeHoje = $_POST['treino_hoje'] ?? '';
    if ($modalidadeHoje !== '' && $d['tipo'] === $modalidadeHoje && $d['data'] === date('Y-m-d')) {
        $hoje = treino_de_hoje($u, $modalidadeHoje);
    }

    if (!$erros) {
        if ($hoje && $d['notas'] === '') {
            $d['notas'] = 'Plano: ' . $hoje['titulo'];
        }
        $stmt = db()->prepare(
            'INSERT INTO atividades (utilizador_id, tipo, intensidade, data, hora, duracao_seg, distancia_km, notas)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(array_merge([$u['id']], valores_atividade($d)));
        $novaId = (int) db()->lastInsertId();
        $avisos = [];
        guardar_ficheiros_atividade($novaId, $d['tipo'], $avisos);

        if ($hoje && !$hoje['feito']) {
            if (isset($hoje['plano_id'])) {
                db()->prepare('INSERT INTO plano_concluidos (utilizador_id, plano_id, data, atividade_id) VALUES (?, ?, CURDATE(), ?)')
                    ->execute([$u['id'], $hoje['plano_id'], $novaId]);
            } else {
                db()->prepare('UPDATE planos_pessoais SET concluido = 1 WHERE id = ? AND utilizador_id = ?')
                    ->execute([$hoje['pessoal_id'], $u['id']]);
            }
        }

        flash('success', $hoje ? 'Treino de hoje registado e marcado como feito. 💪' : 'Atividade registada. 💪');
        foreach ($avisos as $aviso) {
            flash('warning', $aviso . ' A atividade foi guardada sem esse ficheiro.');
        }
        $_SESSION['atividade_nova'] = $novaId;   // destaca a linha nova na lista
        redirecionar('atividades.php');
    }
    $abrirPainel = true;
    if ($hoje) {
        $preenchido = $hoje;
    }
} elseif (($_GET['registar'] ?? '') === 'hoje') {
    // Pré-preenchimento com o treino de hoje
    $hoje = treino_de_hoje($u, (string) ($_GET['modalidade'] ?? ''));
    if ($hoje && !$hoje['feito']) {
        $preenchido = $hoje;
        $d['tipo'] = $hoje['tipo'];
        $minutos = $hoje['duracao_min'] ?? ($hoje['tipo'] === 'musculacao' ? 60 : 40);
        $d = array_merge($d, campos_duracao($minutos * 60));
    }
}
$novaId = $_SESSION['atividade_nova'] ?? null;
unset($_SESSION['atividade_nova']);

// ---------------- Resumo da semana ----------------
$semana = estatisticas_semana($u['id']);
$meta = (int) $u['meta_treinos_semana'];
$pctMeta = $meta > 0 ? min(100, round($semana['atividades'] / $meta * 100)) : 0;

// ---------------- Período (gráficos, evolução) ----------------
$periodos = [4 => ['4 sem.', '4 semanas'], 12 => ['3 meses', '3 meses'], 26 => ['6 meses', '6 meses'], 52 => ['1 ano', '1 ano']];
$semanas = (int) ($_GET['semanas'] ?? 12);
if (!isset($periodos[$semanas])) {
    $semanas = 12;
}
$inicio = date('Y-m-d', strtotime('monday this week -' . ($semanas - 1) . ' weeks'));
$inicioAnterior = date('Y-m-d', strtotime("$inicio -$semanas weeks"));

// Modalidades com distância: cor nos gráficos (variáveis de tokens.css) e unidade do ritmo
$modalidades = [
    'corrida'  => ['ritmo' => 'Ritmo (min/km)',    'cor' => '--chart-1'],
    'ciclismo' => ['ritmo' => 'Velocidade (km/h)', 'cor' => '--chart-2'],
    'natacao'  => ['ritmo' => 'Ritmo (min/100 m)', 'cor' => '--chart-3'],
];

$stmt = db()->prepare(
    "SELECT tipo, data, duracao_seg, distancia_km FROM atividades
     WHERE utilizador_id = ? AND tipo IN ('corrida', 'natacao', 'ciclismo') AND data >= ?
     ORDER BY data, hora"
);
$stmt->execute([$u['id'], $inicioAnterior]);
$comDistancia = $stmt->fetchAll();

// Evolução: a modalidade com mais treinos no período, comparada com o período anterior
$porModalidade = [];
foreach ($modalidades as $tipo => $_) {
    $porModalidade[$tipo] = ['sessoes' => 0, 'km' => 0.0, 'kmAnterior' => 0.0];
}
foreach ($comDistancia as $a) {
    if ($a['data'] >= $inicio) {
        $porModalidade[$a['tipo']]['sessoes']++;
        $porModalidade[$a['tipo']]['km'] += (float) $a['distancia_km'];
    } else {
        $porModalidade[$a['tipo']]['kmAnterior'] += (float) $a['distancia_km'];
    }
}
$principal = 'corrida';
foreach ($porModalidade as $tipo => $m) {
    if ($m['sessoes'] > $porModalidade[$principal]['sessoes']) {
        $principal = $tipo;
    }
}
$evolucao = $porModalidade[$principal];
$variacao = $evolucao['kmAnterior'] > 0 ? (int) round(($evolucao['km'] - $evolucao['kmAnterior']) / $evolucao['kmAnterior'] * 100) : null;

// Recorde: melhor ritmo de corrida (só corridas de pelo menos 1 km)
$stmt = db()->prepare(
    "SELECT data, duracao_seg, distancia_km FROM atividades
     WHERE utilizador_id = ? AND tipo = 'corrida' AND distancia_km >= 1
     ORDER BY duracao_seg / distancia_km LIMIT 1"
);
$stmt->execute([$u['id']]);
$recorde = $stmt->fetch();

// Gráfico "Distância por treino": os últimos 30 treinos com distância do período
$treinosGrafico = array_slice(array_values(array_filter($comDistancia, fn($a) => $a['data'] >= $inicio && $a['distancia_km'] > 0)), -30);
$graficoTreinos = [
    'rotulos' => array_map(fn($a) => mb_strtolower(data_curta($a['data'])), $treinosGrafico),
    'km'      => array_map(fn($a) => round((float) $a['distancia_km'], 2), $treinosGrafico),
    'tipos'   => array_column($treinosGrafico, 'tipo'),
    'nomes'   => array_map(fn($t) => TIPOS_ATIVIDADE[$t][0], array_keys($modalidades)),
    'cores'   => array_combine(array_keys($modalidades), array_column($modalidades, 'cor')),
];

// Evolução semanal por modalidade (km por semana e ritmo médio)
$listaSemanas = [];
for ($i = 0; $i < $semanas; $i++) {
    $segunda = date('Y-m-d', strtotime("$inicio +$i weeks"));
    $listaSemanas[$segunda] = data_curta($segunda);
}
$semanal = [];
foreach ($modalidades as $tipo => $_) {
    $semanal[$tipo] = array_fill_keys(array_keys($listaSemanas), ['km' => 0.0, 'minutosComKm' => 0.0]);
}
foreach ($comDistancia as $a) {
    $segunda = date('Y-m-d', strtotime('monday this week', strtotime($a['data'])));
    if (isset($semanal[$a['tipo']][$segunda]) && $a['distancia_km'] > 0) {
        $semanal[$a['tipo']][$segunda]['km'] += (float) $a['distancia_km'];
        $semanal[$a['tipo']][$segunda]['minutosComKm'] += $a['duracao_seg'] / 60;
    }
}
$graficosSemanais = ['semanas' => array_values($listaSemanas), 'modalidades' => []];
foreach ($modalidades as $tipo => $cfg) {
    if ($porModalidade[$tipo]['sessoes'] === 0) {
        continue;
    }
    $graficosSemanais['modalidades'][$tipo] = [
        'km'          => array_map(fn($s) => round($s['km'], 2), array_values($semanal[$tipo])),
        'ritmo'       => array_map(fn($s) => ritmo($tipo, $s['minutosComKm'], $s['km']), array_values($semanal[$tipo])),
        'rotuloRitmo' => $cfg['ritmo'],
        'cor'         => $cfg['cor'],
        'mmss'        => $tipo !== 'ciclismo',
    ];
}

// ---------------- Lista de atividades ----------------
$filtro = $_GET['tipo'] ?? '';
if ($filtro !== '' && !isset(TIPOS_ATIVIDADE[$filtro])) {
    $filtro = '';
}
$porPagina = 15;
$paginaLista = max(1, (int) ($_GET['p'] ?? 1));

$where = 'utilizador_id = ?' . ($filtro ? ' AND tipo = ?' : '');
$params = $filtro ? [$u['id'], $filtro] : [$u['id']];

$stmt = db()->prepare("SELECT COUNT(*) FROM atividades WHERE $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));
$paginaLista = min($paginaLista, $totalPaginas);

$stmt = db()->prepare("SELECT * FROM atividades WHERE $where ORDER BY data DESC, hora DESC LIMIT $porPagina OFFSET " . (($paginaLista - 1) * $porPagina));
$stmt->execute($params);
$lista = $stmt->fetchAll();

$fotosPorAtividade = [];
if ($lista) {
    $ids = array_column($lista, 'id');
    $stmt = db()->prepare('SELECT atividade_id, caminho FROM atividade_fotos WHERE atividade_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
    $stmt->execute($ids);
    foreach ($stmt as $f) {
        $fotosPorAtividade[$f['atividade_id']][] = $f['caminho'];
    }
}

/** Link para esta página mantendo o período e o filtro atuais. */
function link_atividades(array $mudar): string
{
    $q = array_merge(['semanas' => $_GET['semanas'] ?? null, 'tipo' => $_GET['tipo'] ?? null, 'p' => $_GET['p'] ?? null], $mudar);
    $qs = http_build_query(array_filter($q, fn($v) => $v !== null && $v !== ''));
    return 'atividades.php' . ($qs !== '' ? '?' . $qs : '');
}

// Faixa "Treino de hoje" (telemóvel): o primeiro treino de hoje ainda por fazer
$treinoFaixa = null;
foreach (array_keys(TIPOS_ATIVIDADE) as $tipo) {
    $t = treino_de_hoje($u, $tipo);
    if ($t && !$t['feito']) {
        $treinoFaixa = $t;
        break;
    }
}

$titulo = 'Atividades';
$pagina = 'atividades';
require 'includes/header.php';

ob_start(); ?>
<nav class="segmentado segmentado-compacto d-none d-md-flex" aria-label="Período">
    <?php foreach ($periodos as $n => [$curto, $longo]): ?>
        <a href="<?= e(link_atividades(['semanas' => $n, 'p' => null])) ?>" class="<?= $n === $semanas ? 'ativo' : '' ?>"<?= $n === $semanas ? ' aria-current="true"' : '' ?>><?= e($curto) ?></a>
    <?php endforeach; ?>
</nav>
<a href="atividades.php?registar=1" class="btn btn-principal d-none d-md-inline-flex" data-abrir-painel="painelRegisto">
    <i class="bi bi-plus-lg" aria-hidden="true"></i> Registar atividade
</a>
<?php topo_pagina('Atividades', 'Regista treinos e acompanha a tua evolução.', ob_get_clean()); ?>

        <?php if ($treinoFaixa): ?>
            <div class="cartao cartao-escuro cartao-sm faixa-hoje d-md-none mb-3">
                <div class="min-w-0">
                    <span class="rotulo">Treino de hoje</span>
                    <div class="faixa-titulo text-truncate"><?= e($treinoFaixa['titulo']) ?></div>
                </div>
                <a href="atividades.php?registar=hoje&amp;modalidade=<?= e($treinoFaixa['tipo']) ?>" class="btn btn-principal">Registar</a>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-3">
            <!-- Esta semana -->
            <div class="col-12 col-xl-6">
                <section class="cartao cartao-escuro h-100" aria-labelledby="tituloSemana">
                    <div class="cartao-cabecalho mb-3">
                        <h2 class="cartao-titulo" id="tituloSemana">Esta semana</h2>
                        <?php if ($meta > 0): ?><span class="meta-semana">Meta <?= (int) $semana['atividades'] ?> de <?= $meta ?></span><?php endif; ?>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4"><div class="mini"><small>Atividades</small><strong><?= (int) $semana['atividades'] ?></strong></div></div>
                        <div class="col-4"><div class="mini"><small>Tempo</small><strong><?= e(formatar_duracao((int) $semana['minutos'])) ?></strong></div></div>
                        <div class="col-4"><div class="mini"><small>Distância</small><strong><?= numero($semana['km']) ?> km</strong></div></div>
                    </div>
                    <div class="barra" role="progressbar" aria-label="Meta semanal" aria-valuenow="<?= $pctMeta ?>" aria-valuemin="0" aria-valuemax="100"><span style="width: <?= max(2, $pctMeta) ?>%"></span></div>
                </section>
            </div>

            <!-- Período (telemóvel) -->
            <div class="col-12 d-md-none">
                <nav class="segmentado" aria-label="Período">
                    <?php foreach ($periodos as $n => [$curto, $longo]): ?>
                        <a href="<?= e(link_atividades(['semanas' => $n, 'p' => null])) ?>" class="<?= $n === $semanas ? 'ativo' : '' ?>"<?= $n === $semanas ? ' aria-current="true"' : '' ?>><?= e($curto) ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <!-- Evolução -->
            <div class="col-6 col-xl-3">
                <section class="cartao cartao-sm h-100">
                    <p class="rotulo-cartao"><?= e(TIPOS_ATIVIDADE[$principal][0]) ?> · <?= e($periodos[$semanas][1]) ?></p>
                    <p class="numero-grande mb-1"><?= numero($evolucao['km']) ?> km</p>
                    <?php if ($variacao === null): ?>
                        <p class="variacao mb-0">Sem treinos no período anterior</p>
                    <?php else: ?>
                        <p class="variacao mb-0 <?= $variacao >= 0 ? 'variacao-positiva' : 'variacao-negativa' ?>"><?= $variacao > 0 ? '+' : '' ?><?= $variacao ?>% vs. período anterior</p>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Recorde -->
            <div class="col-6 col-xl-3">
                <section class="cartao cartao-sm h-100">
                    <p class="rotulo-cartao">Recorde · melhor ritmo</p>
                    <?php if ($recorde): ?>
                        <p class="numero-grande mb-1"><?= e(texto_ritmo('corrida', (int) $recorde['duracao_seg'], $recorde['distancia_km'])) ?></p>
                        <p class="variacao mb-0">Corrida de <?= numero($recorde['distancia_km']) ?> km, <?= e(mb_strtolower(data_curta($recorde['data']))) ?></p>
                    <?php else: ?>
                        <p class="numero-grande mb-1">—</p>
                        <p class="variacao mb-0">Regista uma corrida de 1 km ou mais.</p>
                    <?php endif; ?>
                </section>
            </div>
        </div>

        <!-- Distância por treino -->
        <section class="cartao mb-3" aria-labelledby="tituloDistancia">
            <div class="cartao-cabecalho">
                <h2 class="cartao-titulo" id="tituloDistancia">Distância por treino</h2>
                <span class="text-muted small">km</span>
            </div>
            <?php if ($treinosGrafico): ?>
                <div class="grafico-caixa">
                    <canvas id="graficoTreinos" role="img" aria-label="Distância de cada treino de corrida, ciclismo e natação no período"></canvas>
                </div>
                <div class="legenda mt-3" aria-hidden="true">
                    <?php foreach ($modalidades as $tipo => $cfg): ?>
                        <span style="--cor: var(<?= e($cfg['cor']) ?>)"><?= e(TIPOS_ATIVIDADE[$tipo][0]) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">Sem treinos com distância neste período.</p>
            <?php endif; ?>

            <?php if ($graficosSemanais['modalidades']): ?>
                <details class="evolucao-semanal mt-3">
                    <summary><i class="bi bi-chevron-right" aria-hidden="true"></i> Evolução semanal por modalidade</summary>
                    <?php foreach ($graficosSemanais['modalidades'] as $tipo => $_): ?>
                        <h3 class="subtitulo-grafico"><?= e(TIPOS_ATIVIDADE[$tipo][0]) ?></h3>
                        <div class="grafico-caixa">
                            <canvas id="grafico_<?= e($tipo) ?>" role="img" aria-label="Distância e ritmo por semana em <?= e(mb_strtolower(TIPOS_ATIVIDADE[$tipo][0])) ?>"></canvas>
                        </div>
                    <?php endforeach; ?>
                </details>
            <?php endif; ?>
        </section>

        <!-- Lista (o filtro e as páginas mudam sem recarregar: assets/js/historico_lista.js) -->
        <section class="cartao cartao-lista" id="listaTreinos" aria-labelledby="tituloLista">
            <div class="cartao-cabecalho">
                <h2 class="cartao-titulo" id="tituloLista">Todas as atividades <span class="text-muted fw-normal">(<?= $total ?>)</span></h2>
                <nav class="chips" aria-label="Filtrar por modalidade">
                    <a href="<?= e(link_atividades(['tipo' => null, 'p' => null])) ?>" class="chip <?= $filtro === '' ? 'ativo' : '' ?>"<?= $filtro === '' ? ' aria-current="true"' : '' ?>>Todas</a>
                    <?php foreach (TIPOS_ATIVIDADE as $valor => [$nome]): ?>
                        <a href="<?= e(link_atividades(['tipo' => $valor, 'p' => null])) ?>" class="chip <?= $filtro === $valor ? 'ativo' : '' ?>"<?= $filtro === $valor ? ' aria-current="true"' : '' ?>><?= e($nome) ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <?php if (!$lista): ?>
                <p class="text-muted mb-0">
                    <?= $filtro ? 'Ainda não há treinos de ' . e(mb_strtolower(TIPOS_ATIVIDADE[$filtro][0])) . ' registados.' : 'Ainda não há atividades registadas.' ?>
                </p>
            <?php else: ?>
                <ul class="lista-atividades">
                    <?php foreach ($lista as $a): [$nome, $icone] = tipo_info($a['tipo']);
                        $distanciaTexto = texto_distancia($a['tipo'], $a['distancia_km']);
                        $duracaoTexto = formatar_tempo((int) $a['duracao_seg']);
                        // "Corrida · 8 km" (ou "Musculação · 1:05:00" quando não há distância)
                        $linha1 = $nome . ' · ' . ($distanciaTexto !== '' ? $distanciaTexto : $duracaoTexto);
                        $detalhes = [];
                        if ($distanciaTexto !== '') {
                            $detalhes[] = $duracaoTexto;
                        }
                        if ($r = texto_ritmo($a['tipo'], (int) $a['duracao_seg'], $a['distancia_km'])) {
                            $detalhes[] = $r;
                        }
                        $detalhes[] = INTENSIDADES[$a['intensidade']];
                        if ($a['notas']) {
                            $detalhes[] = $a['notas'];
                        } ?>
                        <li class="linha-atividade<?= (int) $a['id'] === $novaId ? ' nova' : '' ?>">
                            <div class="dia"><strong><?= date('j', strtotime($a['data'])) ?></strong><small><?= e(mb_strtolower(MESES_CURTOS[(int) date('n', strtotime($a['data']))])) ?></small></div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="titulo-linha"><i class="bi <?= e($icone) ?> icone-tipo" aria-hidden="true"></i> <?= e($linha1) ?></div>
                                <div class="detalhes"><?= e(implode(' · ', $detalhes)) ?></div>
                                <?php if ($a['ficheiro_fit'] || !empty($fotosPorAtividade[$a['id']])): ?>
                                    <div class="fotos">
                                        <?php foreach ($fotosPorAtividade[$a['id']] ?? [] as $foto): ?>
                                            <a href="<?= e($foto) ?>" target="_blank" rel="noopener"><img src="<?= e($foto) ?>" alt="Foto da atividade de <?= e(data_curta($a['data'])) ?>"></a>
                                        <?php endforeach; ?>
                                        <?php if ($a['ficheiro_fit']): ?>
                                            <a href="<?= e($a['ficheiro_fit']) ?>" class="link-acao small align-self-center" download><i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i> .FIT</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php require 'includes/botoes_atividade.php'; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($totalPaginas > 1): ?>
                    <nav aria-label="Páginas" class="mt-3">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?= $paginaLista <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(link_atividades(['p' => $paginaLista - 1])) ?>" aria-label="Página anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i></a></li>
                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <li class="page-item <?= $i === $paginaLista ? 'active' : '' ?>"><a class="page-link" href="<?= e(link_atividades(['p' => $i])) ?>"<?= $i === $paginaLista ? ' aria-current="page"' : '' ?>><?= $i ?></a></li>
                            <?php endfor; ?>
                            <li class="page-item <?= $paginaLista >= $totalPaginas ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(link_atividades(['p' => $paginaLista + 1])) ?>" aria-label="Página seguinte"><i class="bi bi-chevron-right" aria-hidden="true"></i></a></li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <!-- Registar atividade: painel lateral (computador) ou folha que sobe (telemóvel) -->
        <div class="painel-fundo" aria-hidden="true"></div>
        <section class="painel<?= $abrirPainel ? ' aberto' : '' ?>" id="painelRegisto" role="dialog" aria-labelledby="tituloRegisto"<?= $abrirPainel ? '' : ' aria-hidden="true"' ?>>
            <div class="painel-topo">
                <h2 id="tituloRegisto">Registar atividade</h2>
                <button type="button" class="btn btn-icone" data-fechar-painel aria-label="Fechar"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <form method="post" action="atividades.php" enctype="multipart/form-data" class="painel-form">
                <div class="painel-corpo">
                    <?= csrf_campo() ?>
                    <?php if ($preenchido): ?>
                        <input type="hidden" name="treino_hoje" value="<?= e($preenchido['tipo']) ?>">
                        <p class="aviso mb-3"><i class="bi bi-calendar-check" aria-hidden="true"></i>
                            <span>Preenchido com o treino de hoje: <strong><?= e($preenchido['titulo']) ?></strong></span></p>
                    <?php endif; ?>
                    <?php require 'includes/form_atividade.php'; ?>
                </div>
                <div class="painel-rodape">
                    <button type="button" class="btn btn-secundario" data-fechar-painel>Cancelar</button>
                    <button type="submit" class="btn btn-principal"><i class="bi bi-check-lg" aria-hidden="true"></i> Registar</button>
                </div>
            </form>
        </section>
<?php
$scripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>'
    . '<script>const DADOS_TREINOS = ' . json_encode($graficoTreinos) . '; const DADOS_HISTORICO = ' . json_encode($graficosSemanais) . ';</script>'
    . '<script src="assets/js/graficos_atividades.js"></script>'
    . '<script src="assets/js/historico_lista.js"></script>'
    . '<script src="assets/js/form_atividade.js"></script>';
require 'includes/footer.php';
