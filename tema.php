<?php
/**
 * Alterna entre modo claro e escuro e guarda a escolha na base de dados.
 * Chamado pelo botão do menu (por fetch, sem recarregar a página) ou como formulário normal.
 */
require_once 'includes/funcoes.php';
requer_login();
$u = utilizador_atual();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('dashboard.php');
}
csrf_verificar();

$tema = ($_POST['tema'] ?? '') === 'escuro' ? 'escuro' : 'claro';
db()->prepare('UPDATE utilizadores SET tema = ? WHERE id = ?')->execute([$tema, $u['id']]);
guardar_tema_cookie($tema);

// Pedido feito por JavaScript: responde só com o novo tema
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
    header('Content-Type: application/json');
    echo json_encode(['tema' => $tema]);
    exit;
}

// Sem JavaScript: volta à página onde estava (só caminhos deste site)
$voltar = $_POST['voltar'] ?? '';
if (!preg_match('#^/[^/\\\\]#', $voltar)) {
    $voltar = 'dashboard.php';
}
redirecionar($voltar);
