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

    <!-- Sidebar: fixa em ecrãs grandes, menu deslizante em ecrãs pequenos -->
    <div class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar">
        <div class="sidebar-topo">
            <a href="dashboard.php" class="navbar-brand">
                <img src="IMGS/Athlytic_logo.png" alt="Athlytic Logo" class="img-fluid sidebar-logo">
            </a>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Fechar menu"></button>
        </div>

        <nav class="sidebar-menu">
            <?php foreach ($menu as $chave => [$link, $icone, $texto]): ?>
                <a href="<?= e($link) ?>" class="<?= $pagina === $chave ? 'active' : '' ?>">
                    <i class="bi <?= e($icone) ?>"></i> <?= e($texto) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-tema">
            <?php $temaBotaoClasse = 'tema-link'; $temaBotaoTexto = true; require __DIR__ . '/tema_botao.php'; ?>
        </div>

        <div class="sidebar-user">
            <a href="definicoes.php" class="sidebar-perfil">
                <span class="avatar"><?= e(iniciais($u['nome'])) ?></span>
                <span class="min-w-0">
                    <strong class="d-block text-truncate"><?= e($u['nome']) ?></strong>
                    <small class="text-truncate d-block"><?= e($u['email']) ?></small>
                </span>
            </a>
            <a href="logout.php" class="logout-link"><i class="bi bi-box-arrow-right me-2"></i> Terminar Sessão</a>
        </div>
    </div>

    <!-- Barra de separadores (só em ecrãs pequenos). "Mais" abre o menu completo -->
    <?php
    $separadores = [
        ['dashboard.php',              'bi-house-door', 'Início',    ['dashboard']],
        ['inserir_atividade.php',      'bi-barbell',    'Treino',    ['atividade', 'musculacao', 'corrida']],
        ['plano.php?tipo=alimentacao', 'bi-egg-fried',  'Nutrição',  ['alimentacao']],
        ['historico.php',              'bi-clock-history', 'Histórico', ['historico']],
    ];
    $noMenuMais = !in_array($pagina, array_merge(...array_column($separadores, 3)), true);
    ?>
    <nav class="tabbar d-lg-none" aria-label="Navegação principal">
        <?php foreach ($separadores as [$link, $icone, $texto, $paginas]): ?>
            <a href="<?= e($link) ?>" class="<?= in_array($pagina, $paginas, true) ? 'active' : '' ?>"<?= in_array($pagina, $paginas, true) ? ' aria-current="page"' : '' ?>>
                <i class="bi <?= e($icone) ?>"></i><span><?= e($texto) ?></span>
            </a>
        <?php endforeach; ?>
        <button type="button" class="<?= $noMenuMais ? 'active' : '' ?>" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
            <i class="bi bi-grid"></i><span>Mais</span>
        </button>
    </nav>

    <!-- Conteúdo principal -->
    <main class="content">
        <?php mostrar_flash(); ?>
