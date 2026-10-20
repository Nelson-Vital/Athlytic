<?php
/**
 * Marca (ou desmarca) o dia de hoje de um plano como concluído.
 * Nos planos de corrida e musculação, concluir também regista o treino como atividade,
 * para contar nas estatísticas da semana. Desmarcar apaga essa atividade.
 */
require_once 'includes/funcoes.php';
requer_login();
$u = utilizador_atual();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('dashboard.php');
}
csrf_verificar();

$colunas = [
    'corrida'     => 'plano_corrida_id',
    'musculacao'  => 'plano_musculacao_id',
    'alimentacao' => 'plano_alimentacao_id',
];
$tipo = $_POST['tipo'] ?? '';
$voltar = in_array($_POST['voltar'] ?? '', ['inserir_atividade.php', 'plano.php'], true) ? $_POST['voltar'] : 'dashboard.php';
if ($voltar === 'plano.php') {
    $voltar .= '?tipo=' . urlencode($tipo);
}

if (!isset($colunas[$tipo]) || !$u[$colunas[$tipo]]) {
    flash('warning', 'Escolhe primeiro um plano.');
    redirecionar($voltar);
}
$planoId = (int) $u[$colunas[$tipo]];

$stmt = db()->prepare('SELECT * FROM plano_concluidos WHERE utilizador_id = ? AND plano_id = ? AND data = CURDATE()');
$stmt->execute([$u['id'], $planoId]);
$marcacao = $stmt->fetch();

// ----- Desmarcar -----
if (isset($_POST['desmarcar'])) {
    if ($marcacao) {
        if ($marcacao['atividade_id']) {
            // Apaga a atividade criada ao concluir (a marcação é apagada em cascata)
            apagar_ficheiros(ficheiros_de_atividades($u['id'], (int) $marcacao['atividade_id']));
            db()->prepare('DELETE FROM atividades WHERE id = ? AND utilizador_id = ?')->execute([$marcacao['atividade_id'], $u['id']]);
        }
        db()->prepare('DELETE FROM plano_concluidos WHERE id = ?')->execute([$marcacao['id']]);
        flash('info', $marcacao['atividade_id'] ? 'Marcação removida e atividade apagada do histórico.' : 'Marcação removida.');
    }
    redirecionar($voltar);
}

if ($marcacao) {
    flash('info', 'Hoje já estava marcado como concluído.');
    redirecionar($voltar);
}

// ----- Concluir -----
$atividadeId = null;
if ($tipo !== 'alimentacao') {
    $dia = dia_do_plano($planoId, (int) date('N'));
    $erros = [];
    $d = validar_atividade([
        'tipo'         => $tipo,
        'intensidade'  => $_POST['intensidade'] ?? $u['intensidade_padrao'],
        'data'         => date('Y-m-d'),
        'hora'         => date('H:i'),
        'dur_h'        => $_POST['dur_h'] ?? '',
        'dur_min'      => $_POST['dur_min'] ?? '',
        'dur_seg'      => $_POST['dur_seg'] ?? '',
        'distancia_km' => $_POST['distancia_km'] ?? '',
        'notas'        => $dia ? 'Plano: ' . $dia['titulo'] : '',
    ], $erros);
    if ($erros) {
        foreach ($erros as $erro) {
            flash('danger', $erro);
        }
        redirecionar($voltar);
    }

    $stmt = db()->prepare(
        'INSERT INTO atividades (utilizador_id, tipo, intensidade, data, hora, duracao_seg, distancia_km, notas)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute(array_merge([$u['id']], valores_atividade($d)));
    $atividadeId = (int) db()->lastInsertId();
}

db()->prepare('INSERT INTO plano_concluidos (utilizador_id, plano_id, data, atividade_id) VALUES (?, ?, CURDATE(), ?)')
    ->execute([$u['id'], $planoId, $atividadeId]);

$mensagens = [
    'corrida'     => 'Treino de corrida concluído e registado nas atividades! 🏃',
    'musculacao'  => 'Treino concluído e registado nas atividades! 💪',
    'alimentacao' => 'Refeições registadas! Continua assim! 🍽️',
];
flash('success', $mensagens[$tipo]);
redirecionar($voltar);
