<?php
/**
 * Campos do formulário de atividade (painel "Registar atividade" e página "Editar atividade").
 * O <form> e os botões ficam na página que inclui este ficheiro.
 * Variáveis: $d (valores), $erros, e no modo de edição $fotos e $fit (ficheiros já guardados).
 * Opcional: $notasAbertas (mostra logo as notas e fotos, sem o botão "Adicionar notas ou fotos").
 *
 * Os campos mudam com o tipo (assets/js/form_atividade.js): a musculação não tem distância,
 * a natação usa metros e min/100 m, o ciclismo mostra a velocidade em km/h.
 */
$tipoAtual = $d['tipo'];
$distancia = $d['distancia_km'];
if ($tipoAtual === 'natacao' && is_numeric($distancia)) {
    $distancia = numero($distancia * 1000, 0);   // a natação mostra-se em metros
    $distancia = str_replace(' ', '', $distancia);
} elseif (is_numeric($distancia)) {
    $distancia = rtrim(rtrim(number_format((float) $distancia, 3, '.', ''), '0'), '.');
}
?>
<?php if ($erros): ?>
    <div class="alert alert-danger" role="alert">
        <?php foreach ($erros as $erro): ?><div><?= e($erro) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="campos-atividade d-flex flex-column gap-3" data-form-atividade>
    <fieldset>
        <legend class="form-label">Tipo</legend>
        <div class="escolhas">
            <?php foreach (TIPOS_ATIVIDADE as $valor => [$nome]): ?>
                <input type="radio" class="btn-check" name="tipo" id="tipo_<?= e($valor) ?>" value="<?= e($valor) ?>" <?= $tipoAtual === $valor ? 'checked' : '' ?> required>
                <label for="tipo_<?= e($valor) ?>"><?= e($nome) ?></label>
            <?php endforeach; ?>
        </div>
        <?php if (isset(TIPOS_ANTIGOS[$tipoAtual])): ?>
            <p class="campo-ajuda">Esta atividade era do tipo "<?= e(TIPOS_ANTIGOS[$tipoAtual][0]) ?>", que já não existe. Escolhe outro.</p>
        <?php endif; ?>
    </fieldset>

    <div class="row g-3">
        <div class="col-6">
            <label for="data" class="form-label">Data</label>
            <input type="date" class="form-control" id="data" name="data" value="<?= e($d['data']) ?>" max="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-6">
            <label for="hora" class="form-label">Hora</label>
            <input type="time" class="form-control" id="hora" name="hora" value="<?= e(substr($d['hora'], 0, 5)) ?>" required>
        </div>
    </div>

    <fieldset>
        <legend class="form-label">Duração</legend>
        <div class="row g-2">
            <div class="col-4 campo-unidade">
                <input type="number" class="form-control" id="dur_h" name="dur_h" value="<?= e($d['dur_h']) ?>" placeholder="0" min="0" max="24" inputmode="numeric" aria-label="Horas">
                <span class="unidade" aria-hidden="true">h</span>
            </div>
            <div class="col-4 campo-unidade">
                <input type="number" class="form-control" id="dur_min" name="dur_min" value="<?= e($d['dur_min']) ?>" placeholder="0" min="0" max="1440" inputmode="numeric" aria-label="Minutos">
                <span class="unidade" aria-hidden="true">min</span>
            </div>
            <div class="col-4 campo-unidade">
                <input type="number" class="form-control" id="dur_seg" name="dur_seg" value="<?= e($d['dur_seg']) ?>" placeholder="0" min="0" max="59" inputmode="numeric" aria-label="Segundos">
                <span class="unidade" aria-hidden="true">s</span>
            </div>
        </div>
    </fieldset>

    <div id="campoDistancia" <?= $tipoAtual === 'musculacao' ? 'hidden' : '' ?>>
        <label for="distancia" class="form-label">Distância <span class="text-muted fw-normal">(opcional)</span></label>
        <div class="campo-unidade">
            <input type="text" class="form-control" id="distancia" name="distancia" value="<?= e($distancia) ?>"
                   inputmode="decimal" placeholder="<?= $tipoAtual === 'natacao' ? '1500' : '6,3' ?>" <?= $tipoAtual === 'musculacao' ? 'disabled' : '' ?>>
            <span class="unidade" id="unidadeDistancia"><?= $tipoAtual === 'natacao' ? 'm' : 'km' ?></span>
        </div>
    </div>

    <div class="ritmo-calculado" id="ritmoCalculado" aria-live="polite" <?= $tipoAtual === 'musculacao' ? 'hidden' : '' ?>>
        <span id="ritmoRotulo"><?= $tipoAtual === 'ciclismo' ? 'Velocidade média' : 'Ritmo calculado' ?></span>
        <strong id="ritmoValor">—</strong>
    </div>

    <fieldset>
        <legend class="form-label">Intensidade</legend>
        <div class="segmentado">
            <?php foreach (INTENSIDADES as $valor => $nome): ?>
                <input type="radio" class="btn-check" name="intensidade" id="intensidade_<?= e($valor) ?>" value="<?= e($valor) ?>" <?= $d['intensidade'] === $valor ? 'checked' : '' ?>>
                <label for="intensidade_<?= e($valor) ?>"><?= e($nome) ?></label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <?php $temNotas = !empty($notasAbertas) || $d['notas'] !== '' || !empty($fotos); ?>
    <button type="button" class="link-acao botao-mais-notas" aria-expanded="<?= $temNotas ? 'true' : 'false' ?>" aria-controls="maisNotas" <?= $temNotas ? 'hidden' : '' ?>>
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar notas ou fotos
    </button>

    <div class="mais-notas<?= $temNotas ? ' aberto' : '' ?>" id="maisNotas">
        <div>
            <label for="notas" class="form-label">Notas <span class="text-muted fw-normal">(opcional)</span></label>
            <textarea class="form-control" id="notas" name="notas" rows="2" placeholder="Como te sentiste? Algum recorde?"><?= e($d['notas']) ?></textarea>
        </div>

        <?php if (!empty($fotos)): ?>
            <fieldset>
                <legend class="form-label">Fotos guardadas <span class="text-muted fw-normal">(marca as que queres remover)</span></legend>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($fotos as $foto): ?>
                        <label class="text-center small">
                            <img src="<?= e($foto['caminho']) ?>" alt="Foto da atividade" class="foto-guardada">
                            <input type="checkbox" class="form-check-input" name="remover_fotos[]" value="<?= (int) $foto['id'] ?>"> Remover
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        <?php endif; ?>

        <div>
            <label for="fotos" class="form-label">Fotos <span class="text-muted fw-normal">(opcional, máx. 5 MB cada)</span></label>
            <input type="file" class="form-control" id="fotos" name="fotos[]" multiple accept="image/*">
        </div>

        <div id="campoFit" <?= $tipoAtual === 'corrida' ? '' : 'hidden' ?>>
            <label for="fit" class="form-label">Ficheiro .FIT <span class="text-muted fw-normal">(opcional)</span></label>
            <?php if (!empty($fit)): ?>
                <p class="campo-ajuda mt-0 mb-1"><i class="bi bi-file-earmark-check" aria-hidden="true"></i> Já tem um ficheiro .FIT. Envia outro para o substituir.</p>
            <?php endif; ?>
            <input type="file" class="form-control" id="fit" name="fit" accept=".fit">
        </div>
    </div>
</div>
