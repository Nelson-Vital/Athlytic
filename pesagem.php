<?php
/** Regista ou apaga uma pesagem do histórico de peso (formulário do dashboard). */
require_once 'includes/funcoes.php';
requer_login();
$u = utilizador_atual();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('dashboard.php');
}
csrf_verificar();

if (isset($_POST['apagar'])) {
    $stmt = db()->prepare('DELETE FROM pesagens WHERE id = ? AND utilizador_id = ?');
    $stmt->execute([(int) $_POST['apagar'], $u['id']]);
    if ($stmt->rowCount()) {
        sincronizar_peso_atual($u['id']);
        flash('success', 'Pesagem apagada.');
    }
    redirecionar('dashboard.php#peso');
}

$data = $_POST['data'] ?? '';
$peso = str_replace(',', '.', trim($_POST['peso'] ?? ''));
$gordura = str_replace(',', '.', trim($_POST['massa_gorda'] ?? ''));

$dataObj = DateTime::createFromFormat('Y-m-d', $data);
if (!$dataObj || $dataObj->format('Y-m-d') !== $data || $data > date('Y-m-d')) {
    flash('danger', 'A data da pesagem não é válida.');
} elseif (!is_numeric($peso) || $peso < 30 || $peso > 300) {
    flash('danger', 'O peso tem de estar entre 30 e 300 kg.');
} elseif ($gordura !== '' && (!is_numeric($gordura) || $gordura < 3 || $gordura > 60)) {
    flash('danger', 'A % de massa gorda tem de estar entre 3 e 60.');
} else {
    registar_pesagem($u['id'], $data, (float) $peso, $gordura === '' ? null : (float) $gordura);
    flash('success', 'Pesagem registada! ⚖️');
}
redirecionar('dashboard.php#peso');
