<?php
require_once 'includes/funcoes.php';
requer_login();
$u = utilizador_atual();

$editarId = (int) ($_GET['id'] ?? 0);
$voltarHistorico = ($_GET['voltar'] ?? '') === 'historico';
$stmt = db()->prepare('SELECT * FROM atividades WHERE id = ? AND utilizador_id = ?');
$stmt->execute([$editarId, $u['id']]);
$atividade = $stmt->fetch();

if (!$atividade) {
    flash('warning', 'Atividade não encontrada.');
    redirecionar('inserir_atividade.php');
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
        redirecionar($voltarHistorico ? 'historico.php' : 'inserir_atividade.php');
    }
}

$stmt = db()->prepare('SELECT id, caminho FROM atividade_fotos WHERE atividade_id = ?');
$stmt->execute([$editarId]);
$fotos = $stmt->fetchAll();
$fit = $atividade['ficheiro_fit'];

$titulo = 'Editar Atividade';
$pagina = 'atividade';
require 'includes/header.php';
?>
        <h1 class="mb-4"><i class="bi bi-pencil-square text-primary"></i> Editar Atividade</h1>

        <div class="row">
            <div class="col-lg-8">
                <div class="card card-custom mb-4">
                    <div class="card-body">
                        <?php $textoBotao = 'Guardar Alterações'; require 'includes/form_atividade.php'; ?>
                    </div>
                </div>
            </div>
        </div>
<?php
$scripts = '<script src="assets/js/atividade.js"></script>';
require 'includes/footer.php';
