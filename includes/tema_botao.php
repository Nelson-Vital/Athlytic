<?php
/**
 * Botão para alternar o modo claro/escuro (usado no menu lateral e na barra do telemóvel).
 * Variáveis opcionais: $temaBotaoClasse (classes do botão), $temaBotaoTexto (mostrar texto ao lado do ícone).
 */
$escuro = $u['tema'] === 'escuro';
?>
<form method="post" action="tema.php" class="form-tema m-0">
    <?= csrf_campo() ?>
    <input type="hidden" name="tema" value="<?= $escuro ? 'claro' : 'escuro' ?>">
    <input type="hidden" name="voltar" value="<?= e($_SERVER['REQUEST_URI']) ?>">
    <button type="submit" class="<?= e($temaBotaoClasse ?? '') ?>" title="Mudar entre modo claro e escuro" aria-label="Mudar entre modo claro e escuro">
        <i class="bi <?= $escuro ? 'bi-sun' : 'bi-moon-stars' ?> tema-icone"></i>
        <?php if (!empty($temaBotaoTexto)): ?>
            <span class="ms-2 tema-texto"><?= $escuro ? 'Modo Claro' : 'Modo Escuro' ?></span>
        <?php endif; ?>
    </button>
</form>
<?php $temaBotaoTexto = false; ?>
