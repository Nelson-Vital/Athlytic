<?php
/**
 * Histórico de treinos: gráficos semanais de corrida, natação e ciclismo
 * e lista de todas as atividades com filtro por tipo e paginação.
 */
require_once 'includes/funcoes.php';
requer_login();
$u = utilizador_atual();

// ---------------- Período dos gráficos ----------------
$periodos = [4 => '4 semanas', 12 => '3 meses', 26 => '6 meses', 52 => '1 ano'];
$semanas = (int) ($_GET['semanas'] ?? 12);
if (!isset($periodos[$semanas])) {
    $semanas = 12;
}
$inicio = date('Y-m-d', strtotime('monday this week -' . ($semanas - 1) . ' weeks'));

// Segunda-feira de cada semana do período (chave) => rótulo do gráfico
$listaSemanas = [];
for ($i = 0; $i < $semanas; $i++) {
    $segunda = date('Y-m-d', strtotime("$inicio +$i weeks"));
    $listaSemanas[$segunda] = data_curta($segunda);
}

// Modalidades com gráfico: unidade do ritmo e como se calcula
$modalidades = [
    'corrida'  => ['ritmo' => 'Ritmo (min/km)',    'cor' => '#dc3545'],
    'natacao'  => ['ritmo' => 'Ritmo (min/100 m)', 'cor' => '#0d6efd'],
    'ciclismo' => ['ritmo' => 'Velocidade (km/h)', 'cor' => '#198754'],
];

/** Ritmo ou velocidade de um conjunto de treinos (null se não houver distância). */
function ritmo(string $tipo, float $minutos, float $km): ?float
{
    if ($km <= 0 || $minutos <= 0) {
        return null;
    }
    switch ($tipo) {
        case 'corrida':  return round($minutos / $km, 2);          // min/km
        case 'natacao':  return round($minutos / ($km * 10), 2);   // min/100 m
        default:         return round($km / ($minutos / 60), 1);   // km/h
    }
}

/** 5.5 -> "5:30" (minutos decimais em mm:ss) */
function mm_ss(?float $minutos): string
{
    if ($minutos === null) {
        return '—';
    }
    $seg = (int) round($minutos * 60);
    return sprintf('%d:%02d', intdiv($seg, 60), $seg % 60);
}

$stmt = db()->prepare(
    "SELECT tipo, data, duracao_seg / 60 AS minutos, distancia_km FROM atividades
     WHERE utilizador_id = ? AND tipo IN ('corrida', 'natacao', 'ciclismo') AND data >= ?
     ORDER BY data"
);
$stmt->execute([$u['id'], $inicio]);

// Totais por modalidade e por semana
$dados = [];
foreach ($modalidades as $tipo => $_) {
    $dados[$tipo] = [
        'sessoes' => 0, 'km' => 0.0, 'minutos' => 0.0, 'maior' => 0.0,
        'semanas' => array_fill_keys(array_keys($listaSemanas), ['km' => 0.0, 'minutos' => 0.0, 'minutosComKm' => 0.0]),
    ];
}
foreach ($stmt as $a) {
    $t = $a['tipo'];
    $segunda = date('Y-m-d', strtotime('monday this week', strtotime($a['data'])));
    $km = (float) $a['distancia_km'];
    $dados[$t]['sessoes']++;
    $dados[$t]['km'] += $km;
    $dados[$t]['minutos'] += (float) $a['minutos'];
    $dados[$t]['maior'] = max($dados[$t]['maior'], $km);
    if (isset($dados[$t]['semanas'][$segunda])) {
        $dados[$t]['semanas'][$segunda]['km'] += $km;
        $dados[$t]['semanas'][$segunda]['minutos'] += (float) $a['minutos'];
        // Para o ritmo só contam os treinos com distância registada
        if ($km > 0) {
            $dados[$t]['semanas'][$segunda]['minutosComKm'] += (float) $a['minutos'];
        }
    }
}

// Dados para os gráficos (JavaScript)
$graficos = ['semanas' => array_values($listaSemanas), 'modalidades' => []];
foreach ($modalidades as $tipo => $cfg) {
    $graficos['modalidades'][$tipo] = [
        'km'    => array_map(fn($s) => round($s['km'], 2), array_values($dados[$tipo]['semanas'])),
        'ritmo' => array_map(fn($s) => ritmo($tipo, $s['minutosComKm'], $s['km']), array_values($dados[$tipo]['semanas'])),
        'rotuloRitmo' => $cfg['ritmo'],
        'cor'   => $cfg['cor'],
        'mmss'  => $tipo !== 'ciclismo',
    ];
}

// ---------------- Lista de treinos ----------------
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
$paginas = max(1, (int) ceil($total / $porPagina));
$paginaLista = min($paginaLista, $paginas);

$stmt = db()->prepare("SELECT * FROM atividades WHERE $where ORDER BY data DESC, hora DESC LIMIT $porPagina OFFSET " . (($paginaLista - 1) * $porPagina));
$stmt->execute($params);
$lista = $stmt->fetchAll();

/** Link para esta página mantendo os filtros atuais. */
function link_historico(array $mudar): string
{
    $q = array_merge(['semanas' => $_GET['semanas'] ?? null, 'tipo' => $_GET['tipo'] ?? null, 'p' => $_GET['p'] ?? null], $mudar);
    $qs = http_build_query(array_filter($q, fn($v) => $v !== null && $v !== ''));
    return 'historico.php' . ($qs !== '' ? '?' . $qs : '');
}

$titulo = 'Histórico de Treinos';
$pagina = 'historico';   // link ativo no menu lateral
require 'includes/header.php';
?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <h1 class="mb-0"><i class="bi bi-clock-history text-info"></i> Histórico de Treinos</h1>
            <div class="btn-group" role="group" aria-label="Período">
                <?php foreach ($periodos as $n => $nome): ?>
                    <a href="<?= e(link_historico(['semanas' => $n, 'p' => null])) ?>" class="btn btn-sm <?= $n === $semanas ? 'btn-primary' : 'btn-outline-primary' ?>"><?= e($nome) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Gráficos por modalidade -->
        <?php foreach ($modalidades as $tipo => $cfg): [$nome, $icone, $cor] = tipo_info($tipo); $m = $dados[$tipo]; ?>
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <h4 class="mb-0"><i class="bi <?= e($icone) ?> <?= e($cor) ?>"></i> <?= e($nome) ?></h4>
                        <div class="d-flex flex-wrap gap-4 text-center small">
                            <div><div class="text-muted">Treinos</div><strong class="fs-5"><?= $m['sessoes'] ?></strong></div>
                            <div><div class="text-muted">Distância</div><strong class="fs-5"><?= numero($m['km']) ?> km</strong></div>
                            <div><div class="text-muted">Tempo</div><strong class="fs-5"><?= e(formatar_duracao((int) round($m['minutos']))) ?></strong></div>
                            <div><div class="text-muted"><?= $tipo === 'ciclismo' ? 'Vel. média' : 'Ritmo médio' ?></div>
                                <strong class="fs-5">
                                    <?php $r = ritmo($tipo, $m['minutos'], $m['km']); ?>
                                    <?= $tipo === 'ciclismo' ? ($r === null ? '—' : numero($r) . ' km/h') : mm_ss($r) . ($tipo === 'corrida' ? '/km' : '/100m') ?>
                                </strong>
                            </div>
                            <div><div class="text-muted">Mais longo</div><strong class="fs-5"><?= numero($m['maior']) ?> km</strong></div>
                        </div>
                    </div>
                    <?php if ($m['sessoes'] > 0): ?>
                        <div style="position: relative; height: 260px;">
                            <canvas id="grafico_<?= e($tipo) ?>" role="img" aria-label="Gráfico semanal de <?= e($nome) ?>"></canvas>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Sem treinos de <?= e(mb_strtolower($nome)) ?> neste período.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Lista de todos os treinos (o filtro e as páginas mudam sem recarregar: assets/js/historico_lista.js) -->
        <div class="card card-custom mb-4" id="listaTreinos">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h4 class="mb-0"><i class="bi bi-list-ul"></i> Todos os Treinos <small class="text-muted fs-6">(<?= $total ?>)</small></h4>
                    <div class="btn-group flex-wrap" role="group" aria-label="Filtrar por tipo">
                        <a href="<?= e(link_historico(['tipo' => null, 'p' => null])) ?>" class="btn btn-sm <?= $filtro === '' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Todos</a>
                        <?php foreach (TIPOS_ATIVIDADE as $valor => [$nome]): ?>
                            <a href="<?= e(link_historico(['tipo' => $valor, 'p' => null])) ?>" class="btn btn-sm <?= $filtro === $valor ? 'btn-secondary' : 'btn-outline-secondary' ?>"><?= e($nome) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (!$lista): ?>
                    <?php if ($filtro): ?>
                        <p class="text-muted mb-0">Ainda não há treinos de <?= e(mb_strtolower(TIPOS_ATIVIDADE[$filtro][0])) ?> registados.</p>
                    <?php else: ?>
                        <p class="text-muted mb-0">Ainda não há treinos registados. <a href="inserir_atividade.php">Registar atividade</a></p>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Telemóvel: lista compacta com os botões sempre visíveis -->
                    <div class="list-group list-group-flush d-lg-none lista-treinos">
                        <?php foreach ($lista as $a): [$nome, $icone, $cor] = tipo_info($a['tipo']);
                            $r = ritmo($a['tipo'], $a['duracao_seg'] / 60, (float) $a['distancia_km']);
                            $detalhes = [formatar_tempo((int) $a['duracao_seg'])];
                            if ($a['distancia_km'] !== null) {
                                $detalhes[] = numero($a['distancia_km'], 2) . ' km';
                            }
                            if ($r !== null && isset($modalidades[$a['tipo']])) {
                                $detalhes[] = $a['tipo'] === 'ciclismo' ? numero($r) . ' km/h' : mm_ss($r) . ($a['tipo'] === 'corrida' ? '/km' : '/100m');
                            } ?>
                            <div class="list-group-item px-0 d-flex align-items-center gap-2">
                                <a href="editar_atividade.php?id=<?= (int) $a['id'] ?>&amp;voltar=historico" class="flex-grow-1 text-reset text-decoration-none">
                                    <div class="fw-bold"><i class="bi <?= e($icone) ?> <?= e($cor) ?>"></i> <?= e($nome) ?></div>
                                    <div class="small"><?= e(implode(' • ', $detalhes)) ?></div>
                                    <div class="small text-muted"><?= e(data_curta($a['data'])) ?> <?= date('Y', strtotime($a['data'])) ?>, <?= e(substr($a['hora'], 0, 5)) ?> • <?= e(INTENSIDADES[$a['intensidade']]) ?></div>
                                </a>
                                <?php $voltarPara = 'historico'; require 'includes/botoes_atividade.php'; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="table-responsive d-none d-lg-block">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th><th>Tipo</th><th>Duração</th><th>Distância</th><th>Ritmo</th><th>Intensidade</th><th>Notas</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista as $a): [$nome, $icone, $cor] = tipo_info($a['tipo']);
                                    $r = ritmo($a['tipo'], $a['duracao_seg'] / 60, (float) $a['distancia_km']); ?>
                                    <tr>
                                        <td class="text-nowrap"><?= e(data_curta($a['data'])) ?> <?= date('Y', strtotime($a['data'])) ?><br><small class="text-muted"><?= e(substr($a['hora'], 0, 5)) ?></small></td>
                                        <td class="text-nowrap"><i class="bi <?= e($icone) ?> <?= e($cor) ?>"></i> <?= e($nome) ?></td>
                                        <td class="text-nowrap"><?= e(formatar_tempo((int) $a['duracao_seg'])) ?></td>
                                        <td class="text-nowrap"><?= $a['distancia_km'] === null ? '—' : numero($a['distancia_km'], 2) . ' km' ?></td>
                                        <td class="text-nowrap">
                                            <?php if ($r === null || $a['tipo'] === 'musculacao' || !isset($modalidades[$a['tipo']])): ?>—
                                            <?php elseif ($a['tipo'] === 'ciclismo'): ?><?= numero($r) ?> km/h
                                            <?php else: ?><?= mm_ss($r) ?><?= $a['tipo'] === 'corrida' ? '/km' : '/100m' ?><?php endif; ?>
                                        </td>
                                        <td><?= e(INTENSIDADES[$a['intensidade']]) ?></td>
                                        <td class="small" style="max-width: 260px;"><?= e($a['notas']) ?></td>
                                        <td class="text-end">
                                            <?php $voltarPara = 'historico'; require 'includes/botoes_atividade.php'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($paginas > 1): ?>
                        <nav aria-label="Páginas" class="mt-3">
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <li class="page-item <?= $paginaLista <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(link_historico(['p' => $paginaLista - 1])) ?>">Anterior</a></li>
                                <?php for ($i = 1; $i <= $paginas; $i++): ?>
                                    <li class="page-item <?= $i === $paginaLista ? 'active' : '' ?>"><a class="page-link" href="<?= e(link_historico(['p' => $i])) ?>"><?= $i ?></a></li>
                                <?php endfor; ?>
                                <li class="page-item <?= $paginaLista >= $paginas ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(link_historico(['p' => $paginaLista + 1])) ?>">Seguinte</a></li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
<?php
$scripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>'
    . '<script>const DADOS_HISTORICO = ' . json_encode($graficos) . ';</script>'
    . '<script src="assets/js/graficos_historico.js"></script>'
    . '<script src="assets/js/historico_lista.js"></script>';
require 'includes/footer.php';
