<?php /** Janela para inativar (com motivo obrigatório) ou reativar uma conta. Os botões em acoes_conta.php preenchem-na. */ ?>
<div class="modal fade" id="modalEstadoConta" tabindex="-1" aria-labelledby="modalEstadoContaTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="admin.php" class="modal-content">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" id="estadoAcao">
            <input type="hidden" name="id" id="estadoId">
            <input type="hidden" name="voltar" id="estadoVoltar">
            <div class="modal-header" id="estadoCabecalho">
                <h5 class="modal-title" id="modalEstadoContaTitulo">Conta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p id="estadoTexto"></p>
                <label for="estadoMotivo" class="form-label fw-bold" id="estadoRotulo">Motivo</label>
                <textarea class="form-control" name="motivo" id="estadoMotivo" rows="3" maxlength="500"></textarea>
                <div class="form-text" id="estadoAjuda"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn" id="estadoConfirmar">Confirmar</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function () {
        const MIN = <?= MOTIVO_MIN ?>;
        const modal = document.getElementById('modalEstadoConta');
        const motivo = document.getElementById('estadoMotivo');
        const confirmar = document.getElementById('estadoConfirmar');
        const ajuda = document.getElementById('estadoAjuda');
        let obrigatorio = false;

        function validar() {
            const falta = MIN - motivo.value.trim().length;
            confirmar.disabled = obrigatorio && falta > 0;
            ajuda.textContent = obrigatorio
                ? (falta > 0 ? 'Faltam ' + falta + ' caracteres. A pessoa não vê este texto, mas fica no histórico.' : 'O motivo fica registado no histórico da conta.')
                : 'Opcional. Fica registado no histórico da conta.';
        }

        modal.addEventListener('show.bs.modal', function (ev) {
            const b = ev.relatedTarget;
            const inativar = b.dataset.acao === 'inativar';
            obrigatorio = inativar;
            document.getElementById('estadoAcao').value = b.dataset.acao;
            document.getElementById('estadoId').value = b.dataset.id;
            document.getElementById('estadoVoltar').value = b.dataset.voltar;
            document.getElementById('modalEstadoContaTitulo').textContent = (inativar ? 'Inativar conta de ' : 'Reativar conta de ') + b.dataset.nome;
            document.getElementById('estadoCabecalho').className = 'modal-header text-white ' + (inativar ? 'bg-danger' : 'bg-success');
            document.getElementById('estadoTexto').textContent = inativar
                ? 'A conta não é apagada: fica inativa e deixa de conseguir entrar. Os dados mantêm-se e a conta pode ser reativada mais tarde.'
                : 'A pessoa volta a conseguir entrar com o seu email e palavra-passe.';
            document.getElementById('estadoRotulo').textContent = inativar ? 'Motivo da inativação (obrigatório)' : 'Nota (opcional)';
            motivo.value = '';
            motivo.required = inativar;
            confirmar.className = 'btn ' + (inativar ? 'btn-danger' : 'btn-success');
            confirmar.textContent = inativar ? 'Inativar conta' : 'Reativar conta';
            validar();
        });
        modal.addEventListener('shown.bs.modal', () => motivo.focus());
        motivo.addEventListener('input', validar);
    })();
</script>
