<?php
/**
 * Administração: ficha de um utilizador.
 * Mostra só informação não privada (sem palavra-passe, data de nascimento, sexo, peso, % de gordura
 * nem histórico de pesagens, que são dados pessoais/de saúde), as atividades e o histórico da conta.
 */
require_once 'includes/funcoes.php';
$admin = requer_admin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT u.id, u.nome, u.email, u.papel, u.ativo, u.criado_em, u.objetivo, u.intensidade_padrao,
            u.meta_km_semana, u.meta_treinos_semana,
            pc.nome AS plano_corrida, pm.nome AS plano_musculacao, pa.nome AS plano_alimentacao
     FROM utilizadores u
     LEFT JOIN planos pc ON pc.id = u.plano_corrida_id
     LEFT JOIN planos pm ON pm.id = u.plano_musculacao_id
     LEFT JOIN planos pa ON pa.id = u.plano_alimentacao_id
     WHERE u.id = ?'
);
$stmt->execute([$id]);
$alvo = $stmt->fetch();
if (!$alvo) {
    flash('warning', 'Utilizador não encontrado.');
    redirecionar('admin.php');
}

// Resumo das atividades por modalidade
$stmt = db()->prepare(
    'SELECT tipo, COUNT(*) AS sessoes, COALESCE(SUM(distancia_km), 0) AS km, SUM(duracao_seg) AS segundos, MAX(data) AS ultima
     FROM atividades WHERE utilizador_id = ? GROUP BY tipo ORDER BY sessoes DESC'
);
$stmt->execute([$id]);
$resumo = $stmt->fetchAll();
$totalAtividades = array_sum(array_column($resumo, 'sessoes'));

// Lista de atividades (20 por página)
$porPagina = 20;
$paginas = max(1, (int) ceil($totalAtividades / $porPagina));
$paginaLista = min(max(1, (int) ($_GET['p'] ?? 1)), $paginas);
$stmt = db()->prepare(
    "SELECT a.*, (SELECT COUNT(*) FROM atividade_fotos f WHERE f.atividade_id = a.id) AS n_fotos
     FROM atividades a WHERE a.utilizador_id = ? ORDER BY a.data DESC, a.hora DESC
     LIMIT $porPagina OFFSET " . (($paginaLista - 1) * $porPagina)
);
$stmt->execute([$id]);
$atividades = $stmt->fetchAll();

// Histórico de ações dos administradores sobre esta conta
$stmt = db()->prepare(
    'SELECT e.acao, e.motivo, e.data, a.nome AS admin_nome
     FROM estado_contas e LEFT JOIN utilizadores a ON a.id = e.admin_id
     WHERE e.utilizador_id = ? ORDER BY e.data DESC, e.id DESC'
);
$stmt->execute([$id]);
$historico = $stmt->fetchAll();

$ACOES = [
    'inativar'    => ['Conta inativada', 'bi-person-x-fill', 'text-danger'],
    'reativar'    => ['Conta reativada', 'bi-person-check-fill', 'text-success'],
    'promover'    => ['Promovido a administrador', 'bi-shield-plus', 'text-warning'],
    'despromover' => ['Deixou de ser administrador', 'bi-shield-minus', 'text-secondary'],
];

$titulo = 'Utilizador: ' . $alvo['nome'];
$pagina = 'admin';
require 'includes/header.php';
?>
        <a href="admin.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Voltar à lista</a>

        <div class="card card-custom mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div class="min-w-0">
                        <h2 class="mb-1">
                            <?= e($alvo['nome']) ?>
                            <?php if ($alvo['papel'] === 'admin'): ?><span class="badge text-bg-warning fs-6 align-middle">Admin</span><?php endif; ?>
                            <span class="badge fs-6 align-middle <?= $alvo['ativo'] ? 'text-bg-success' : 'text-bg-danger' ?>"><?= $alvo['ativo'] ? 'Ativa' : 'Inativa' ?></span>
                        </h2>
                        <div class="text-muted text-break"><i class="bi bi-envelope"></i> <?= e($alvo['email']) ?></div>
                    </div>
                    <?php $voltarPara = 'ficha'; require 'includes/acoes_conta.php'; ?>
                </div>

                <hr>
                <div class="row g-3 small">
                    <div class="col-6 col-md-3"><div class="text-muted">Registado a</div><strong><?= e(data_curta($alvo['criado_em'])) ?> <?= date('Y', strtotime($alvo['criado_em'])) ?></strong></div>
                    <div class="col-6 col-md-3"><div class="text-muted">Objetivo</div><strong><?= e(OBJETIVOS[$alvo['objetivo']] ?? '—') ?></strong></div>
                    <div class="col-6 col-md-3"><div class="text-muted">Metas semanais</div><strong><?= numero($alvo['meta_km_semana']) ?> km • <?= (int) $alvo['meta_treinos_semana'] ?> treinos</strong></div>
                    <div class="col-6 col-md-3"><div class="text-muted">Intensidade habitual</div><strong><?= e(INTENSIDADES[$alvo['intensidade_padrao']] ?? '—') ?></strong></div>
                    <div class="col-12">
                        <div class="text-muted">Planos escolhidos</div>
                        <strong><?= e(implode(' • ', array_filter([$alvo['plano_corrida'], $alvo['plano_musculacao'], $alvo['plano_alimentacao']])) ?: '—') ?></strong>
                    </div>
                </div>
                <p class="small text-muted mt-3 mb-0"><i class="bi bi-lock"></i> Dados privados (data de nascimento, sexo, peso, % de gordura e pesagens) não são mostrados aos administradores.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card card-custom mb-4">
                    <div class="card-body">
                        <h4 class="mb-3"><i class="bi bi-activity"></i> Atividades <small class="text-muted fs-6">(<?= $totalAtividades ?>)</small></h4>

                        <?php if ($resumo): ?>
                            <div class="row g-2 mb-3">
                                <?php foreach ($resumo as $r): [$nome, $icone, $cor] = tipo_info($r['tipo']); ?>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded-3 p-2 h-100 small">
                                            <div class="fw-bold"><i class="bi <?= e($icone) ?> <?= e($cor) ?>"></i> <?= e($nome) ?></div>
                                            <div><?= (int) $r['sessoes'] ?> treinos • <?= e(formatar_duracao((int) round($r['segundos'] / 60))) ?></div>
                                            <?php if ($r['km'] > 0): ?><div><?= numero($r['km']) ?> km</div><?php endif; ?>
                                            <div class="text-muted">última: <?= e(data_curta($r['ultima'])) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$atividades): ?>
                            <p class="text-muted mb-0">Este utilizador ainda não registou atividades.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($atividades as $a): [$nome, $icone, $cor] = tipo_info($a['tipo']);
                                    $detalhes = [formatar_tempo((int) $a['duracao_seg'])];
                                    if ($a['distancia_km'] !== null) {
                                        $detalhes[] = numero($a['distancia_km'], 2) . ' km';
                                    }
                                    $detalhes[] = INTENSIDADES[$a['intensidade']];
                                    if ($a['n_fotos']) {
                                        $detalhes[] = $a['n_fotos'] . ' foto' . ($a['n_fotos'] > 1 ? 's' : '');
                                    }
                                    if ($a['ficheiro_fit']) {
                                        $detalhes[] = '.FIT';
                                    } ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between gap-2">
                                            <span class="fw-bold"><i class="bi <?= e($icone) ?> <?= e($cor) ?>"></i> <?= e($nome) ?></span>
                                            <small class="text-muted text-nowrap"><?= e(data_curta($a['data'])) ?> <?= date('Y', strtotime($a['data'])) ?>, <?= e(substr($a['hora'], 0, 5)) ?></small>
                                        </div>
                                        <div class="small"><?= e(implode(' • ', $detalhes)) ?></div>
                                        <?php if ($a['notas']): ?><div class="small text-muted"><?= e($a['notas']) ?></div><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($paginas > 1): ?>
                                <nav aria-label="Páginas" class="mt-3">
                                    <ul class="pagination pagination-sm justify-content-center mb-0">
                                        <?php for ($i = 1; $i <= $paginas; $i++): ?>
                                            <li class="page-item <?= $i === $paginaLista ? 'active' : '' ?>"><a class="page-link" href="admin_utilizador.php?id=<?= (int) $id ?>&amp;p=<?= $i ?>"><?= $i ?></a></li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-custom mb-4">
                    <div class="card-body">
                        <h4 class="mb-3"><i class="bi bi-journal-text"></i> Histórico da conta</h4>
                        <?php if (!$historico): ?>
                            <p class="text-muted mb-0">Sem alterações feitas por administradores.</p>
                        <?php else: ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($historico as $h): [$texto, $icone, $cor] = $ACOES[$h['acao']]; ?>
                                    <li class="mb-3">
                                        <div class="fw-bold"><i class="bi <?= $icone ?> <?= $cor ?>"></i> <?= $texto ?></div>
                                        <div class="small text-muted"><?= e(data_curta($h['data'])) ?> <?= date('Y, H:i', strtotime($h['data'])) ?> • por <?= e($h['admin_nome'] ?? 'conta apagada') ?></div>
                                        <?php if ($h['motivo']): ?><div class="small mt-1">“<?= e($h['motivo']) ?>”</div><?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

<?php require 'includes/modal_estado_conta.php'; ?>
<?php require 'includes/footer.php'; ?>
