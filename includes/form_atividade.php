<?php
/**
 * Formulário de atividade (usado em Inserir Atividade e Editar Atividade).
 * Variáveis: $d (valores), $erros, $textoBotao, e no modo de edição $fotos e $fit (ficheiros já guardados).
 */
?>
<?php if ($erros): ?>
    <div class="alert alert-danger">
        <?php foreach ($erros as $erro): ?><div><?= e($erro) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" id="formAtividade">
    <?= csrf_campo() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="tipo" class="form-label fw-bold">Tipo de Atividade</label>
            <select class="form-select" id="tipo" name="tipo" required>
                <option value="">Seleciona...</option>
                <?php foreach (TIPOS_ATIVIDADE as $valor => [$nome]): ?>
                    <option value="<?= e($valor) ?>" <?= $d['tipo'] === $valor ? 'selected' : '' ?>><?= e($nome) ?></option>
                <?php endforeach; ?>
                <?php if (isset(TIPOS_ANTIGOS[$d['tipo']])): ?>
                    <option value="" selected disabled><?= e(TIPOS_ANTIGOS[$d['tipo']][0]) ?> (já não disponível, escolhe outro)</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label for="intensidade" class="form-label fw-bold">Intensidade</label>
            <select class="form-select" id="intensidade" name="intensidade" required>
                <?php foreach (INTENSIDADES as $valor => $nome): ?>
                    <option value="<?= e($valor) ?>" <?= $d['intensidade'] === $valor ? 'selected' : '' ?>><?= e($nome) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label for="data" class="form-label fw-bold">Data</label>
            <input type="date" class="form-control" id="data" name="data" value="<?= e($d['data']) ?>" max="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="col-md-6">
            <label for="hora" class="form-label fw-bold">Hora</label>
            <input type="time" class="form-control" id="hora" name="hora" value="<?= e(substr($d['hora'], 0, 5)) ?>" required>
        </div>

        <div class="col-md-6">
            <label for="dur_min" class="form-label fw-bold">Duração</label>
            <div class="input-group">
                <input type="number" class="form-control" id="dur_h" name="dur_h" value="<?= e($d['dur_h']) ?>" placeholder="0" min="0" max="24" inputmode="numeric" aria-label="Horas">
                <span class="input-group-text">h</span>
                <input type="number" class="form-control" id="dur_min" name="dur_min" value="<?= e($d['dur_min']) ?>" placeholder="36" min="0" max="1440" inputmode="numeric" aria-label="Minutos">
                <span class="input-group-text">min</span>
                <input type="number" class="form-control" id="dur_seg" name="dur_seg" value="<?= e($d['dur_seg']) ?>" placeholder="00" min="0" max="59" inputmode="numeric" aria-label="Segundos">
                <span class="input-group-text">seg</span>
            </div>
        </div>

        <div class="col-md-6">
            <label for="distancia_km" class="form-label fw-bold">Distância (km) <small class="text-muted">(opcional)</small></label>
            <input type="number" step="0.01" min="0" class="form-control" id="distancia_km" name="distancia_km" value="<?= e($d['distancia_km']) ?>" placeholder="ex: 8.5">
        </div>

        <div class="col-12" id="campoFit" style="<?= $d['tipo'] === 'corrida' ? '' : 'display:none;' ?>">
            <label for="fit" class="form-label fw-bold">Ficheiro .FIT (Strava/Garmin) <small class="text-muted">(opcional)</small></label>
            <?php if (!empty($fit)): ?>
                <div class="small mb-1"><i class="bi bi-file-earmark-check text-success"></i> Já tem um ficheiro .FIT. Envia outro para o substituir.</div>
            <?php endif; ?>
            <input type="file" class="form-control" id="fit" name="fit" accept=".fit">
        </div>

        <div class="col-12">
            <label for="notas" class="form-label fw-bold">Observações / Notas</label>
            <textarea class="form-control" id="notas" name="notas" rows="3" placeholder="Como te sentiste? Algum PR?"><?= e($d['notas']) ?></textarea>
        </div>

        <?php if (!empty($fotos)): ?>
            <div class="col-12">
                <label class="form-label fw-bold">Fotos guardadas <small class="text-muted">(marca as que queres remover)</small></label>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($fotos as $foto): ?>
                        <label class="text-center small">
                            <img src="<?= e($foto['caminho']) ?>" alt="Foto da atividade" class="rounded d-block mb-1" style="width: 80px; height: 80px; object-fit: cover;">
                            <input type="checkbox" class="form-check-input" name="remover_fotos[]" value="<?= (int) $foto['id'] ?>"> Remover
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="col-12">
            <label for="fotos" class="form-label fw-bold">Fotos / Prints <small class="text-muted">(opcional, máx. 5 MB cada)</small></label>
            <input type="file" class="form-control" id="fotos" name="fotos[]" multiple accept="image/*">
        </div>

        <div class="col-12 mt-4 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-gold btn-lg px-5">
                <i class="bi bi-check2-circle"></i> <?= e($textoBotao) ?>
            </button>
            <?php if (!empty($fotos) || !empty($fit) || isset($editarId)): ?>
                <a href="<?= !empty($voltarHistorico) ? 'historico.php' : 'inserir_atividade.php' ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
            <?php endif; ?>
        </div>
    </div>
</form>
