<?php
/**
 * Cabeçalho comum das páginas internas.
 * Antes de incluir, definir: $titulo (título da página) e $pagina (entrada ativa do menu:
 * dashboard, atividades, plano_treino, plano_alimentar, definicoes ou admin).
 * Opcional: $estilos (HTML extra no <head>) e $scripts (HTML extra antes de </body>).
 *
 * As variáveis deste ficheiro começam por "nav" para não colidirem com as das páginas.
 */
requer_login();
$u = utilizador_atual();
$navEscuro = $u['tema'] === 'escuro';

// [ligação, ícone, texto]
$navMenu = [
    'dashboard'       => ['dashboard.php',       'bi-house',      'Início'],
    'atividades'      => ['atividades.php',      'bi-activity',   'Atividades'],
    'plano_treino'    => ['plano_treino.php',    'bi-barbell',    'Plano de Treino'],
    'plano_alimentar' => ['plano_alimentar.php', 'bi-talheres', 'Plano Alimentar'],
    'definicoes'      => ['definicoes.php',      'bi-sliders',    'Configurações'],
];
// A área de administração só aparece no menu de quem é administrador
if (e_admin($u)) {
    $navMenu['admin'] = ['admin.php', 'bi-shield', 'Administração'];
}

// Barra inferior do telemóvel: Início · Atividades · + · Treino · Alimentação
$navBarra = [
    'dashboard'       => ['dashboard.php',       'bi-house',      'Início'],
    'atividades'      => ['atividades.php',      'bi-activity',   'Atividades'],
    'mais'            => null,
    'plano_treino'    => ['plano_treino.php',    'bi-barbell',    'Treino'],
    'plano_alimentar' => ['plano_alimentar.php', 'bi-talheres', 'Alimentação'],
];
?>
<!DOCTYPE html>
<html lang="pt" data-theme="<?= $navEscuro ? 'dark' : 'light' ?>" data-bs-theme="<?= $navEscuro ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | Athlytic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/tokens.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <?php if (!empty($estilos)) echo $estilos; ?>

    <link rel="icon" href="IMGS/Athlytic_logo_sem_texto.png" type="image/png">
    <link rel="apple-touch-icon" href="IMGS/Athlytic_logo_sem_texto.png">
</head>
<body>
    <a href="#conteudo" class="visually-hidden-focusable">Saltar para o conteúdo</a>

    <!-- Menu lateral (computador e tablet) -->
    <aside class="sidebar" aria-label="Menu principal">
        <a href="dashboard.php" class="marca">
            <img src="IMGS/Athlytic_logo_sem_texto.png" alt="" width="36" height="36">
            <span>Athlytic</span>
        </a>

        <nav class="menu">
            <?php foreach ($navMenu as $navChave => [$navLink, $navIcone, $navTexto]): ?>
                <a href="<?= e($navLink) ?>"<?= $pagina === $navChave ? ' class="active" aria-current="page"' : '' ?>>
                    <i class="bi <?= e($navIcone) ?>" aria-hidden="true"></i> <?= e($navTexto) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="menu-rodape">
            <?php $temaBotaoTexto = true; require __DIR__ . '/tema_botao.php'; ?>
            <a href="definicoes.php" class="cartao-utilizador">
                <span class="avatar avatar-ciano"><?= e(iniciais($u['nome'])) ?></span>
                <span class="min-w-0">
                    <strong class="d-block text-truncate"><?= e($u['nome']) ?></strong>
                    <small class="text-truncate"><?= e($u['email']) ?></small>
                </span>
            </a>
            <a href="logout.php" class="sair"><i class="bi bi-box-arrow-left" aria-hidden="true"></i> Terminar sessão</a>
        </div>
    </aside>

    <!-- Barra inferior (telemóvel). O + abre o registo de atividade -->
    <nav class="bottom-nav" aria-label="Navegação principal">
        <?php foreach ($navBarra as $navChave => $navItem): ?>
            <?php if ($navItem === null): ?>
                <a href="atividades.php?registar=1" class="mais" data-abrir-registo aria-label="Registar atividade">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                </a>
            <?php else: [$navLink, $navIcone, $navTexto] = $navItem; ?>
                <a href="<?= e($navLink) ?>"<?= $pagina === $navChave ? ' class="active" aria-current="page"' : '' ?>>
                    <i class="bi <?= e($navIcone) ?>" aria-hidden="true"></i><span><?= e($navTexto) ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Conteúdo principal -->
    <main class="content" id="conteudo">
        <?php mostrar_flash(); ?>
