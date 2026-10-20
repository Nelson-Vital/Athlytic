<?php
/** Início do layout das páginas de autenticação (login e registo). Definir $titulo antes. */
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
    <div class="login-container">
        <div class="login-card">
            <h1 class="logo">ATHLYTIC</h1>
            <p class="subtitle">A tua jornada fitness começa aqui</p>
