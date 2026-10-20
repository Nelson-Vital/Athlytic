<?php
/**
 * Botões de administração para uma conta (usados na lista e na ficha do utilizador).
 * Variáveis: $alvo (utilizador), $admin (administrador com sessão), $voltarPara ('lista' | 'ficha').
 */
$proprio = (int) $alvo['id'] === (int) $admin['id'];
$ultimoAdmin = deixa_sem_admins($alvo);
$voltarCampo = $voltarPara === 'ficha' ? 'ficha' : 'lista';
?>
<div class="d-flex flex-wrap gap-1 flex-shrink-0">
    <?php if (!$proprio): ?>
        <?php if ($alvo['ativo']): ?>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEstadoConta"
                    data-acao="inativar" data-id="<?= (int) $alvo['id'] ?>" data-nome="<?= e($alvo['nome']) ?>" data-voltar="<?= $voltarCampo ?>"
                    <?= $ultimoAdmin ? 'disabled title="Tem de existir sempre pelo menos um administrador ativo"' : '' ?>>
                <i class="bi bi-person-x"></i> Inativar
            </button>
        <?php else: ?>
            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalEstadoConta"
                    data-acao="reativar" data-id="<?= (int) $alvo['id'] ?>" data-nome="<?= e($alvo['nome']) ?>" data-voltar="<?= $voltarCampo ?>">
                <i class="bi bi-person-check"></i> Reativar
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
                <button type="submit" name="acao" value="despromover" class="btn btn-sm btn-outline-secondary"
                        <?= $ultimoAdmin ? 'disabled title="Tem de existir sempre pelo menos um administrador ativo"' : '' ?>>
                    <i class="bi bi-shield-minus"></i> Retirar admin
                </button>
            <?php else: ?>
                <button type="submit" name="acao" value="promover" class="btn btn-sm btn-outline-warning">
                    <i class="bi bi-shield-plus"></i> Tornar admin
                </button>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>
