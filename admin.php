<?php
/**
 * Administração: lista de utilizadores e ações sobre as contas.
 * Regras:
 *  - um administrador não "apaga" contas de outros: inativa-as, e tem de explicar o motivo;
 *  - tem de existir sempre pelo menos um administrador ativo;
 *  - as contas inativas não conseguem entrar (ver index.php e utilizador_atual()).
 * Todas as ações ficam registadas na tabela estado_contas.
 */
require_once 'includes/funcoes.php';
$admin = requer_admin();

// ---------------------------------------------------------------
// Ações (POST)
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = $_POST['acao'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    $voltar = ($_POST['voltar'] ?? '') === 'ficha' ? "admin_utilizador.php?id=$id" : 'admin.php';

    $stmt = db()->prepare('SELECT * FROM utilizadores WHERE id = ?');
    $stmt->execute([$id]);
    $alvo = $stmt->fetch();

    if (!$alvo) {
        flash('warning', 'Utilizador não encontrado.');
        redirecionar('admin.php');
    }

    switch ($acao) {
        case 'inativar':
            if ((int) $alvo['id'] === (int) $admin['id']) {
                flash('danger', 'Não podes inativar a tua própria conta. Para a apagar, usa as Configurações.');
            } elseif (!$alvo['ativo']) {
                flash('info', 'Esta conta já está inativa.');
            } elseif (mb_strlen($motivo) < MOTIVO_MIN) {
                flash('danger', 'Explica o motivo da inativação (pelo menos ' . MOTIVO_MIN . ' caracteres).');
            } elseif (mb_strlen($motivo) > 500) {
                flash('danger', 'O motivo é demasiado longo (máx. 500 caracteres).');
            } elseif (deixa_sem_admins($alvo)) {
                flash('danger', 'Não é possível: tem de existir sempre pelo menos um administrador ativo.');
            } else {
                db()->prepare('UPDATE utilizadores SET ativo = 0 WHERE id = ?')->execute([$id]);
                registar_estado_conta($id, $admin['id'], 'inativar', $motivo);
                flash('success', "A conta de {$alvo['nome']} foi inativada.");
            }
            break;

        case 'reativar':
            if ($alvo['ativo']) {
                flash('info', 'Esta conta já está ativa.');
            } else {
                db()->prepare('UPDATE utilizadores SET ativo = 1 WHERE id = ?')->execute([$id]);
                registar_estado_conta($id, $admin['id'], 'reativar', $motivo !== '' ? mb_substr($motivo, 0, 500) : null);
                flash('success', "A conta de {$alvo['nome']} foi reativada.");
            }
            break;

        case 'promover':
            if ($alvo['papel'] === 'admin') {
                flash('info', 'Este utilizador já é administrador.');
            } elseif (!$alvo['ativo']) {
                flash('danger', 'Reativa a conta antes de a promover a administrador.');
            } else {
                db()->prepare("UPDATE utilizadores SET papel = 'admin' WHERE id = ?")->execute([$id]);
                registar_estado_conta($id, $admin['id'], 'promover', null);
                flash('success', "{$alvo['nome']} passou a ser administrador.");
            }
            break;

        case 'despromover':
            if ($alvo['papel'] !== 'admin') {
                flash('info', 'Este utilizador não é administrador.');
            } elseif (deixa_sem_admins($alvo)) {
                flash('danger', 'Não é possível: tem de existir sempre pelo menos um administrador ativo.');
            } else {
                db()->prepare("UPDATE utilizadores SET papel = 'utilizador' WHERE id = ?")->execute([$id]);
                registar_estado_conta($id, $admin['id'], 'despromover', null);
                flash('success', "{$alvo['nome']} deixou de ser administrador.");
                if ((int) $alvo['id'] === (int) $admin['id']) {
                    redirecionar('dashboard.php');   // já não tem acesso a esta área
                }
            }
            break;
    }
    redirecionar($voltar);
}

// ---------------------------------------------------------------
// Lista de utilizadores (filtros feitos no SQL)
// ---------------------------------------------------------------
$pesquisa = trim($_GET['q'] ?? '');
$estado = $_GET['estado'] ?? '';
$where = [];
$params = [];
if ($pesquisa !== '') {
    $where[] = '(u.nome LIKE ? OR u.email LIKE ?)';
    $params[] = "%$pesquisa%";
    $params[] = "%$pesquisa%";
}
if ($estado === 'ativos' || $estado === 'inativos') {
    $where[] = 'u.ativo = ' . ($estado === 'ativos' ? 1 : 0);
} elseif ($estado === 'admins') {
    $where[] = "u.papel = 'admin'";
} else {
    $estado = '';
}

$stmt = db()->prepare(
    "SELECT u.id, u.nome, u.email, u.papel, u.ativo, u.criado_em,
            (SELECT COUNT(*) FROM atividades a WHERE a.utilizador_id = u.id) AS n_atividades,
            (SELECT MAX(a.data) FROM atividades a WHERE a.utilizador_id = u.id) AS ultima_atividade,
            (SELECT e.motivo FROM estado_contas e WHERE e.utilizador_id = u.id AND e.acao = 'inativar' ORDER BY e.data DESC, e.id DESC LIMIT 1) AS motivo
     FROM utilizadores u " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . '
     ORDER BY u.ativo DESC, u.nome'
);
$stmt->execute($params);
$utilizadores = $stmt->fetchAll();

$totais = db()->query(
    "SELECT COUNT(*) AS total, SUM(ativo = 1) AS ativos, SUM(ativo = 0) AS inativos, SUM(papel = 'admin' AND ativo = 1) AS admins FROM utilizadores"
)->fetch();

$titulo = 'Administração';
$pagina = 'admin';
require 'includes/header.php';
?>
        <h1 class="mb-1"><i class="bi bi-shield-lock-fill text-primary"></i> Administração</h1>
        <p class="text-muted mb-4">Gestão das contas de utilizador. Cada inativação fica registada com o motivo e o administrador que a fez.</p>

        <div class="row g-3 mb-4">
            <?php foreach ([['Utilizadores', $totais['total'], 'bi-people-fill', 'text-primary'], ['Ativos', $totais['ativos'], 'bi-person-check-fill', 'text-success'],
                            ['Inativos', $totais['inativos'], 'bi-person-x-fill', 'text-danger'], ['Admins ativos', $totais['admins'], 'bi-shield-check', 'text-warning']] as [$rotulo, $valor, $icone, $cor]): ?>
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center">
                        <i class="bi <?= $icone ?> <?= $cor ?> fs-3"></i>
                        <h3 class="mb-0"><?= (int) $valor ?></h3>
                        <small class="text-muted"><?= $rotulo ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card card-custom mb-4">
            <div class="card-body">
                <form method="get" class="row g-2 mb-3">
                    <div class="col-md-6">
                        <input type="search" name="q" class="form-control" placeholder="Pesquisar por nome ou email" value="<?= e($pesquisa) ?>">
                    </div>
                    <div class="col-8 col-md-4">
                        <select name="estado" class="form-select">
                            <option value="">Todas as contas</option>
                            <option value="ativos" <?= $estado === 'ativos' ? 'selected' : '' ?>>Só ativas</option>
                            <option value="inativos" <?= $estado === 'inativos' ? 'selected' : '' ?>>Só inativas</option>
                            <option value="admins" <?= $estado === 'admins' ? 'selected' : '' ?>>Administradores</option>
                        </select>
                    </div>
                    <div class="col-4 col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
                    </div>
                </form>

                <?php if (!$utilizadores): ?>
                    <p class="text-muted mb-0">Nenhum utilizador encontrado.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($utilizadores as $p): ?>
                            <div class="list-group-item px-0 d-flex flex-wrap align-items-center gap-2 <?= $p['ativo'] ? '' : 'conta-inativa' ?>">
                                <a href="admin_utilizador.php?id=<?= (int) $p['id'] ?>" class="flex-grow-1 text-reset text-decoration-none min-w-0">
                                    <div class="fw-bold">
                                        <?= e($p['nome']) ?>
                                        <?php if ($p['papel'] === 'admin'): ?><span class="badge text-bg-warning ms-1">Admin</span><?php endif; ?>
                                        <?php if (!$p['ativo']): ?><span class="badge text-bg-danger ms-1">Inativa</span><?php endif; ?>
                                        <?php if ((int) $p['id'] === (int) $admin['id']): ?><span class="badge text-bg-secondary ms-1">Tu</span><?php endif; ?>
                                    </div>
                                    <div class="small text-muted text-break"><?= e($p['email']) ?></div>
                                    <div class="small text-muted">
                                        <?= (int) $p['n_atividades'] ?> atividades
                                        <?= $p['ultima_atividade'] ? '• última a ' . e(data_curta($p['ultima_atividade'])) : '' ?>
                                        • registado a <?= e(data_curta($p['criado_em'])) ?> <?= date('Y', strtotime($p['criado_em'])) ?>
                                    </div>
                                    <?php if (!$p['ativo'] && $p['motivo']): ?>
                                        <div class="small text-danger mt-1"><i class="bi bi-info-circle"></i> Motivo: <?= e($p['motivo']) ?></div>
                                    <?php endif; ?>
                                </a>
                                <?php $alvo = $p; $voltarPara = 'lista'; require 'includes/acoes_conta.php'; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

<?php require 'includes/modal_estado_conta.php'; ?>
<?php require 'includes/footer.php'; ?>
