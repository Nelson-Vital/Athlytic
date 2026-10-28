<?php
/**
 * Botões "Editar" e "Apagar" de uma atividade (lista da página Atividades).
 * Variável: $a (a atividade).
 */
?>
<div class="acoes-linha">
    <a href="editar_atividade.php?id=<?= (int) $a['id'] ?>" class="btn btn-icone" aria-label="Editar atividade de <?= e(data_curta($a['data'])) ?>">
        <i class="bi bi-pencil" aria-hidden="true"></i>
    </a>
    <form method="post" action="atividades.php" class="m-0" onsubmit="return confirm('Apagar esta atividade?');">
        <?= csrf_campo() ?>
        <button type="submit" name="apagar" value="<?= (int) $a['id'] ?>" class="btn btn-icone perigo" aria-label="Apagar atividade de <?= e(data_curta($a['data'])) ?>">
            <i class="bi bi-trash" aria-hidden="true"></i>
        </button>
    </form>
</div>
