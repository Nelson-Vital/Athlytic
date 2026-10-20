<?php
/**
 * Mostra o estado da base de dados e cria/atualiza-a se for preciso.
 * Não é obrigatório: o site já faz isto sozinho em qualquer página (ver includes/migracoes.php).
 *  - se a base de dados não existir, cria-a com database/athlytic.sql (inclui dados de exemplo);
 *  - se existir numa versão antiga, aplica as atualizações em falta sem apagar dados;
 *  - se já estiver atualizada, não faz nada.
 * Só funciona a partir do próprio computador (localhost).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/migracoes.php';

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Só disponível em localhost.');
}

$passos = [];
$erro = null;

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $passos = preparar_base_dados($pdo);
} catch (PDOException $e) {
    $erro = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar base de dados | ATHLYTIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5" style="max-width: 640px;">
        <h1 class="h3 mb-4">Base de dados ATHLYTIC</h1>
        <?php if ($erro): ?>
            <div class="alert alert-danger">
                <strong>Não foi possível atualizar.</strong><br>
                Confirma que o MySQL está ligado no XAMPP.<br>
                <small class="text-muted"><?= htmlspecialchars($erro) ?></small>
            </div>
        <?php elseif ($passos): ?>
            <div class="alert alert-success">
                <strong>Base de dados atualizada!</strong>
                <ul class="mb-0 mt-2"><?php foreach ($passos as $p): ?><li><?= htmlspecialchars($p) ?></li><?php endforeach; ?></ul>
            </div>
        <?php else: ?>
            <div class="alert alert-info">A base de dados já está atualizada. Não foi preciso fazer nada.</div>
        <?php endif; ?>
        <a href="index.php" class="btn btn-warning fw-bold">Ir para o ATHLYTIC</a>
    </div>
</body>
</html>
