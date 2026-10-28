<?php
/**
 * Botão "Modo escuro" / "Modo claro" do menu lateral.
 * Funciona sem JavaScript (formulário normal); com JavaScript muda o tema sem recarregar (tema.js).
 */
$escuro = $u['tema'] === 'escuro';
?>
<form method="post" action="tema.php" class="form-tema m-0">
    <?= csrf_campo() ?>
    <input type="hidden" name="tema" value="<?= $escuro ? 'claro' : 'escuro' ?>">
    <input type="hidden" name="voltar" value="<?= e($_SERVER['REQUEST_URI']) ?>">
    <button type="submit">
        <i class="bi <?= $escuro ? 'bi-sun' : 'bi-moon' ?> tema-icone" aria-hidden="true"></i>
        <span class="tema-texto"><?= $escuro ? 'Modo claro' : 'Modo escuro' ?></span>
    </button>
</form>
