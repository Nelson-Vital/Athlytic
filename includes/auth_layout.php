<?php
/**
 * Início do layout das páginas de autenticação (login e registo).
 * Definir antes: $titulo, $authBoasVindas (título no telemóvel), $authSubtitulo (texto por baixo do título).
 * À esquerda (computador) ou em cima (telemóvel) fica o painel escuro; à direita/em baixo, o formulário.
 */
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | ATHLYTIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/auth.css" rel="stylesheet">
    <link rel="icon" href="IMGS/Athlytic_logo_sem_texto.png" type="image/png">
</head>
<body class="<?= ($_COOKIE['tema'] ?? '') === 'claro' ? 'claro' : '' ?>">
    <!-- Alternar modo claro/escuro (guardado num cookie, já que ainda não há sessão) -->
    <button type="button" class="btn-tema-auth" id="btnTemaAuth" title="Mudar entre modo claro e escuro" aria-label="Mudar entre modo claro e escuro">
        <i class="bi <?= ($_COOKIE['tema'] ?? '') === 'claro' ? 'bi-moon-stars' : 'bi-sun' ?>"></i>
    </button>
    <script>
        document.getElementById('btnTemaAuth').addEventListener('click', function () {
            var claro = document.body.classList.toggle('claro');
            document.cookie = 'tema=' + (claro ? 'claro' : 'escuro') + '; path=/; max-age=31536000; samesite=lax';
            this.querySelector('i').className = 'bi ' + (claro ? 'bi-moon-stars' : 'bi-sun');
        });
    </script>
    <aside class="auth-painel">
        <div class="auth-marca">
            <img src="IMGS/Athlytic_logo_sem_texto.png" alt="" width="44" height="44">
            <span>Athlytic</span>
        </div>
        <!-- Telemóvel: boas-vindas da página -->
        <div class="d-lg-none">
            <h1 class="auth-titulo"><?= e($authBoasVindas ?? $titulo) ?></h1>
            <p class="auth-texto"><?= e($authSubtitulo ?? '') ?></p>
        </div>
        <!-- Computador: apresentação da app -->
        <div class="d-none d-lg-block auth-apresentacao">
            <h1 class="auth-titulo">Treino e nutrição <span class="text-gold">ao teu ritmo</span>.</h1>
            <p class="auth-texto">Os teus planos de treino, corrida e alimentação num só sítio, com o registo das atividades e a evolução do peso.</p>
        </div>
        <div class="d-none d-lg-flex auth-destaques">
            <span><i class="bi bi-barbell"></i> Planos de treino e corrida</span>
            <span><i class="bi bi-graph-down"></i> Evolução do peso e da gordura</span>
        </div>
    </aside>

    <div class="auth-form">
        <div class="auth-caixa">
            <div class="d-none d-lg-block mb-4">
                <h2 class="auth-form-titulo"><?= e($titulo) ?></h2>
                <p class="auth-form-texto"><?= e($authSubtitulo ?? '') ?></p>
            </div>
