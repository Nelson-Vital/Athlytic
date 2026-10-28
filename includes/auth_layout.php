<?php
/**
 * Início do layout das páginas de autenticação (login e registo).
 * Definir antes: $titulo, $authBoasVindas (título no telemóvel), $authSubtitulo (texto por baixo do título).
 * À esquerda (computador) ou em cima (telemóvel) fica o painel escuro; à direita/em baixo, o formulário.
 */
?>
<!DOCTYPE html>
<?php $authEscuro = ($_COOKIE['tema'] ?? '') === 'escuro'; ?>
<html lang="pt" data-theme="<?= $authEscuro ? 'dark' : 'light' ?>" data-bs-theme="<?= $authEscuro ? 'dark' : 'light' ?>">
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
    <link href="assets/css/auth.css" rel="stylesheet">
    <link rel="icon" href="IMGS/Athlytic_logo_sem_texto.png" type="image/png">
</head>
<body class="auth">
    <!-- Alternar modo claro/escuro (guardado num cookie, já que ainda não há sessão) -->
    <button type="button" class="btn-tema-auth" id="btnTemaAuth" aria-label="<?= $authEscuro ? 'Mudar para o modo claro' : 'Mudar para o modo escuro' ?>">
        <i class="bi <?= $authEscuro ? 'bi-sun' : 'bi-moon' ?>" aria-hidden="true"></i>
    </button>
    <script>
        document.getElementById('btnTemaAuth').addEventListener('click', function () {
            var html = document.documentElement;
            var escuro = html.getAttribute('data-theme') !== 'dark';
            html.setAttribute('data-theme', escuro ? 'dark' : 'light');
            html.setAttribute('data-bs-theme', escuro ? 'dark' : 'light');
            document.cookie = 'tema=' + (escuro ? 'escuro' : 'claro') + '; path=/; max-age=31536000; samesite=lax';
            this.querySelector('i').className = 'bi ' + (escuro ? 'bi-sun' : 'bi-moon');
            this.setAttribute('aria-label', escuro ? 'Mudar para o modo claro' : 'Mudar para o modo escuro');
        });
    </script>
    <aside class="auth-painel">
        <div class="auth-marca">
            <img src="IMGS/Athlytic_logo_sem_texto.png" alt="" width="48" height="48">
            <span>Athlytic</span>
        </div>
        <!-- Telemóvel: boas-vindas da página -->
        <div class="d-lg-none">
            <h1 class="auth-titulo"><?= e($authBoasVindas ?? $titulo) ?></h1>
            <p class="auth-texto"><?= e($authSubtitulo ?? '') ?></p>
        </div>
        <!-- Computador: apresentação da app -->
        <div class="d-none d-lg-block auth-apresentacao">
            <h1 class="auth-titulo">Treino e nutrição <span class="destaque">ao teu ritmo</span>.</h1>
            <p class="auth-texto">Os teus planos de treino, corrida e alimentação num só sítio, com o registo das atividades e a evolução do peso.</p>
        </div>
        <div class="d-none d-lg-flex auth-destaques">
            <span><i class="bi bi-barbell" aria-hidden="true"></i> Planos de treino e corrida</span>
            <span><i class="bi bi-talheres" aria-hidden="true"></i> Planos alimentares semanais</span>
        </div>
    </aside>

    <div class="auth-form">
        <div class="auth-caixa">
            <div class="d-none d-lg-block mb-4">
                <h2 class="auth-form-titulo"><?= e($titulo) ?></h2>
                <p class="auth-form-texto"><?= e($authSubtitulo ?? '') ?></p>
            </div>
