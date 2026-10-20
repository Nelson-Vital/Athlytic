<?php
/**
 * Formulário "Marcar treino de hoje como concluído" (corrida e musculação).
 * Ao concluir, o treino é também registado como atividade.
 * Variáveis: $tipoPlano ('corrida' | 'musculacao'), $voltarPara ('plano.php' | 'inserir_atividade.php'), $u.
 */
$duracaoSugerida = $tipoPlano === 'corrida' ? 40 : 60;
?>
<form method="post" action="concluir.php" class="text-start">
    <?= csrf_campo() ?>
    <input type="hidden" name="tipo" value="<?= e($tipoPlano) ?>">
    <input type="hidden" name="voltar" value="<?= e($voltarPara) ?>">
    <p class="small text-muted mb-3">O treino fica registado no teu histórico de atividades.</p>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label fw-bold" for="c_duracao_<?= e($tipoPlano) ?>">Duração</label>
            <div class="input-group">
                <input type="number" class="form-control" name="dur_h" placeholder="0" min="0" max="24" inputmode="numeric" aria-label="Horas">
                <span class="input-group-text">h</span>
                <input type="number" class="form-control" id="c_duracao_<?= e($tipoPlano) ?>" name="dur_min" value="<?= $duracaoSugerida ?>" min="0" max="1440" inputmode="numeric" aria-label="Minutos">
                <span class="input-group-text">m</span>
                <input type="number" class="form-control" name="dur_seg" placeholder="00" min="0" max="59" inputmode="numeric" aria-label="Segundos">
                <span class="input-group-text">s</span>
            </div>
        </div>
        <?php if ($tipoPlano === 'corrida'): ?>
            <div class="col-sm-6">
                <label class="form-label fw-bold" for="c_distancia">Distância (km)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="c_distancia" name="distancia_km" placeholder="ex: 6.5">
            </div>
        <?php endif; ?>
        <div class="col-sm-6">
            <label class="form-label fw-bold" for="c_intensidade_<?= e($tipoPlano) ?>">Intensidade</label>
            <select class="form-select" id="c_intensidade_<?= e($tipoPlano) ?>" name="intensidade">
                <?php foreach (INTENSIDADES as $valor => $nome): ?>
                    <option value="<?= e($valor) ?>" <?= $u['intensidade_padrao'] === $valor ? 'selected' : '' ?>><?= e($nome) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="text-end mt-4">
        <button type="submit" class="btn btn-success btn-lg">
            <i class="bi bi-check2-all"></i> Marcar como Concluído
        </button>
    </div>
</form>
