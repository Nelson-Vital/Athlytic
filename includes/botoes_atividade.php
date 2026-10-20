<?php
/**
 * Botões "Editar" e "Apagar" de uma atividade (iguais em todas as páginas).
 * Variáveis: $a (a atividade), $voltarPara ('historico' para regressar ao histórico, '' para inserir_atividade.php).
 */
$voltarPara = $voltarPara ?? '';
?>
<div class="d-flex flex-shrink-0">
    <a href="editar_atividade.php?id=<?= (int) $a['id'] ?><?= $voltarPara === 'historico' ? '&amp;voltar=historico' : '' ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar" aria-label="Editar">
        <i class="bi bi-pencil"></i>
    </a>
    <form method="post" action="inserir_atividade.php" class="m-0" onsubmit="return confirm('Apagar esta atividade?');">
        <?= csrf_campo() ?>
        <?php if ($voltarPara === 'historico'): ?><input type="hidden" name="voltar" value="historico"><?php endif; ?>
        <button type="submit" name="apagar" value="<?= (int) $a['id'] ?>" class="btn btn-sm btn-outline-danger border-0" title="Apagar" aria-label="Apagar">
            <i class="bi bi-trash"></i>
        </button>
    </form>
</div>
