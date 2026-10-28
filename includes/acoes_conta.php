<?php
/**
 * Botões de administração para uma conta (usados na lista e na ficha do utilizador).
 * Variáveis: $alvo (utilizador), $admin (administrador com sessão), $voltarPara ('lista' | 'ficha').
 */
$proprio = (int) $alvo['id'] === (int) $admin['id'];
$ultimoAdmin = deixa_sem_admins($alvo);
$voltarCampo = $voltarPara === 'ficha' ? 'ficha' : 'lista';
?>
<div class="d-flex flex-wrap gap-2 flex-shrink-0">
    <?php if (!$proprio): ?>
        <?php if ($alvo['ativo']): ?>
            <button type="button" class="btn btn-sm btn-perigo" data-bs-toggle="modal" data-bs-target="#modalEstadoConta"
                    data-acao="inativar" data-id="<?= (int) $alvo['id'] ?>" data-nome="<?= e($alvo['nome']) ?>" data-voltar="<?= $voltarCampo ?>"
                    <?= $ultimoAdmin ? 'disabled title="Tem de existir sempre pelo menos um administrador ativo"' : '' ?>>
                <i class="bi bi-person-x" aria-hidden="true"></i> Inativar
            </button>
        <?php else: ?>
            <button type="button" class="btn btn-sm btn-secundario" data-bs-toggle="modal" data-bs-target="#modalEstadoConta"
                    data-acao="reativar" data-id="<?= (int) $alvo['id'] ?>" data-nome="<?= e($alvo['nome']) ?>" data-voltar="<?= $voltarCampo ?>">
                <i class="bi bi-person-check" aria-hidden="true"></i> Reativar
            </button>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($alvo['ativo']): ?>
        <form method="post" action="admin.php" class="m-0"
              onsubmit="return confirm('<?= $alvo['papel'] === 'admin' ? 'Retirar as permissões de administrador a ' : 'Tornar administrador: ' ?><?= e(addslashes($alvo['nome'])) ?>?');">
            <?= csrf_campo() ?>
            <input type="hidden" name="id" value="<?= (int) $alvo['id'] ?>">
            <input type="hidden" name="voltar" value="<?= $voltarCampo ?>">
            <?php if ($alvo['papel'] === 'admin'): ?>
                <?php // Ninguém retira as próprias permissões (e tem de ficar sempre um administrador ativo) ?>
                <button type="submit" name="acao" value="despromover" class="btn btn-sm btn-secundario"
                        <?= $proprio ? 'disabled title="Não podes retirar as tuas próprias permissões"' : ($ultimoAdmin ? 'disabled title="Tem de existir sempre pelo menos um administrador ativo"' : '') ?>>
                    <i class="bi bi-shield-minus" aria-hidden="true"></i> Retirar admin
                </button>
            <?php else: ?>
                <button type="submit" name="acao" value="promover" class="btn btn-sm btn-secundario">
                    <i class="bi bi-shield-plus" aria-hidden="true"></i> Tornar admin
                </button>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>
