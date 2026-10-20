<?php
/** Descarrega todas as atividades do utilizador em CSV (abre no Excel). */
require_once 'includes/funcoes.php';
requer_login();
$u = utilizador_atual();

$stmt = db()->prepare(
    'SELECT data, hora, tipo, intensidade, duracao_seg, distancia_km, notas
     FROM atividades WHERE utilizador_id = ? ORDER BY data DESC, hora DESC'
);
$stmt->execute([$u['id']]);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="athlytic_atividades_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM para o Excel reconhecer os acentos
fputcsv($out, ['Data', 'Hora', 'Tipo', 'Intensidade', 'Duração', 'Distância (km)', 'Notas'], ';');
foreach ($stmt as $a) {
    fputcsv($out, [
        $a['data'],
        substr($a['hora'], 0, 5),
        tipo_info($a['tipo'])[0],
        INTENSIDADES[$a['intensidade']],
        formatar_tempo((int) $a['duracao_seg']),
        $a['distancia_km'] === null ? '' : str_replace('.', ',', $a['distancia_km']),
        $a['notas'],
    ], ';');
}
fclose($out);
