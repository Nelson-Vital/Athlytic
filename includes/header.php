<?php
/**
 * Cabeçalho comum das páginas internas.
 * Antes de incluir, definir: $titulo (título da página) e $pagina (link ativo no menu).
 */
requer_login();
$u = utilizador_atual();

$menu = [
    'dashboard'   => ['dashboard.php',              'bi-house-door-fill',     'Início'],
    'atividade'   => ['inserir_atividade.php',      'bi-plus-circle-dotted',  'Inserir Atividade'],
    'historico'   => ['historico.php',              'bi-clock-history',       'Histórico'],
    'corrida'     => ['plano.php?tipo=corrida',     'bi-geo-alt-fill',        'Plano de Corrida'],
    'musculacao'  => ['plano.php?tipo=musculacao',  'bi-trophy-fill',         'Musculação'],
    'alimentacao' => ['plano.php?tipo=alimentacao', 'bi-egg-fried',           'Plano Alimentar'],
    'meus_planos' => ['meus_planos.php',            'bi-calendar2-week',      'Os Meus Planos'],
    'definicoes'  => ['definicoes.php',             'bi-gear-fill',           'Configurações'],
];
// A área de administração só aparece no menu de quem é administrador
if (e_admin($u)) {
    $menu['admin'] = ['admin.php', 'bi-shield-lock-fill', 'Administração'];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | ATHLYTIC</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">

    <link rel="icon" href="IMGS/Athlytic_logo_sem_texto.png" type="image/png">
    <link rel="apple-touch-icon" href="IMGS/Athlytic_logo_sem_texto.png">
</head>
<body class="<?= $u['tema'] === 'escuro' ? 'dark-mode' : '' ?>">

    <!-- Barra superior (só aparece em ecrãs pequenos) -->
    <nav class="topbar d-lg-none">
        <button class="btn btn-link text-white fs-3 p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Abrir menu">
            <i class="bi bi-list"></i>
        </button>
        <span class="fw-bold">ATHLYTIC</span>
        <?php $temaBotaoClasse = 'btn btn-link text-white fs-4 p-0'; require __DIR__ . '/tema_botao.php'; ?>
    </nav>

    <!-- Sidebar: fixa em ecrãs grandes, menu deslizante em ecrãs pequenos -->
    <div class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar">
        <div class="text-center mb-4">
            <a href="dashboard.php" class="navbar-brand d-block">
                <img src="IMGS/Athlytic_logo.png" alt="Athlytic Logo" class="img-fluid sidebar-logo">
            </a>
        </div>

        <?php foreach ($menu as $chave => [$link, $icone, $texto]): ?>
            <a href="<?= e($link) ?>" class="<?= $pagina === $chave ? 'active' : '' ?>">
                <i class="bi <?= e($icone) ?> me-2"></i> <?= e($texto) ?>
            </a>
        <?php endforeach; ?>

        <div class="sidebar-tema">
            <?php $temaBotaoClasse = 'tema-link'; $temaBotaoTexto = true; require __DIR__ . '/tema_botao.php'; ?>
        </div>

        <div class="sidebar-user">
            <small class="d-block text-secondary">Sessão iniciada como</small>
            <strong><?= e($u['nome']) ?></strong>
            <a href="logout.php" class="logout-link"><i class="bi bi-box-arrow-right me-2"></i> Terminar Sessão</a>
        </div>
    </div>

    <!-- Conteúdo principal -->
    <main class="content">
        <?php mostrar_flash(); ?>
