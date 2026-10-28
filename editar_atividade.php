<?php
require_once 'includes/funcoes.php';
requer_login();
$u = utilizador_atual();

$editarId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM atividades WHERE id = ? AND utilizador_id = ?');
$stmt->execute([$editarId, $u['id']]);
$atividade = $stmt->fetch();

if (!$atividade) {
    flash('warning', 'Atividade não encontrada.');
    redirecionar('atividades.php');
}

$erros = [];
$d = $atividade + campos_duracao((int) $atividade['duracao_seg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $d = validar_atividade($_POST, $erros);

    if (!$erros) {
        $stmt = db()->prepare(
            'UPDATE atividades SET tipo = ?, intensidade = ?, data = ?, hora = ?, duracao_seg = ?, distancia_km = ?, notas = ?
             WHERE id = ? AND utilizador_id = ?'
        );
        $stmt->execute(array_merge(valores_atividade($d), [$editarId, $u['id']]));

        // Fotos marcadas para remover
        $remover = array_map('intval', $_POST['remover_fotos'] ?? []);
        if ($remover) {
            $marcadores = implode(',', array_fill(0, count($remover), '?'));
            $stmt = db()->prepare("SELECT caminho FROM atividade_fotos WHERE atividade_id = ? AND id IN ($marcadores)");
            $stmt->execute(array_merge([$editarId], $remover));
            apagar_ficheiros($stmt->fetchAll(PDO::FETCH_COLUMN));
            db()->prepare("DELETE FROM atividade_fotos WHERE atividade_id = ? AND id IN ($marcadores)")
                ->execute(array_merge([$editarId], $remover));
        }

        // Se deixou de ser corrida, o ficheiro .FIT deixa de fazer sentido
        if ($d['tipo'] !== 'corrida' && $atividade['ficheiro_fit']) {
            apagar_ficheiros([$atividade['ficheiro_fit']]);
            db()->prepare('UPDATE atividades SET ficheiro_fit = NULL WHERE id = ?')->execute([$editarId]);
        }

        $avisos = [];
        guardar_ficheiros_atividade($editarId, $d['tipo'], $avisos);

        flash('success', 'Atividade atualizada.');
        foreach ($avisos as $aviso) {
            flash('warning', $aviso);
        }
        redirecionar('atividades.php');
    }
}

$stmt = db()->prepare('SELECT id, caminho FROM atividade_fotos WHERE atividade_id = ?');
$stmt->execute([$editarId]);
$fotos = $stmt->fetchAll();
$fit = $atividade['ficheiro_fit'];

$titulo = 'Editar atividade';
$pagina = 'atividades';
require 'includes/header.php';
topo_pagina('Editar atividade', data_extenso($atividade['data']));
?>
        <div class="row">
            <div class="col-lg-8 col-xxl-6">
                <form method="post" enctype="multipart/form-data" class="cartao">
                    <?= csrf_campo() ?>
                    <?php $notasAbertas = true; require 'includes/form_atividade.php'; ?>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn btn-principal btn-grande"><i class="bi bi-check-lg" aria-hidden="true"></i> Guardar alterações</button>
                        <a href="atividades.php" class="btn btn-secundario btn-grande">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
<?php
$scripts = '<script src="assets/js/form_atividade.js"></script>';
require 'includes/footer.php';
