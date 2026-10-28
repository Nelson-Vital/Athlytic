// Planos pessoais: "Adicionar" e "Editar" num dia abrem o painel "Personalizar" já preenchido,
// sem recarregar a página (sem JavaScript, as ligações abrem a página com o painel aberto).
(function () {
    var painel = document.getElementById('painelPessoal');
    if (!painel || !window.abrirPainel) {
        return;
    }
    var campos = {
        id: document.getElementById('pessoalId'),
        data: document.getElementById('pessoalData'),
        titulo: document.getElementById('pessoalTitulo'),
        descricao: document.getElementById('pessoalDescricao'),
        substitui: document.getElementById('pessoalSubstitui'),
        refeicao: document.getElementById('pessoalRefeicao')
    };
    var sugestao = document.getElementById('pessoalSugestao');
    var diaMarcado = null;

    function preencher(botao, plano) {
        campos.id.value = plano.id || 0;
        campos.data.value = plano.data;
        campos.titulo.value = plano.titulo || '';
        campos.descricao.value = plano.descricao || '';
        campos.substitui.checked = !!plano.substitui_plano;
        if (campos.refeicao) {
            campos.refeicao.value = plano.refeicao || 'dia';
        }
        document.getElementById('pessoalModo').textContent = plano.id ? 'Editar' : 'Personalizar';
        document.getElementById('tituloPessoal').textContent = botao.dataset.dia;
        var texto = botao.dataset.sugestao;
        sugestao.hidden = !texto;
        sugestao.querySelector('strong').textContent = texto;
        var erro = painel.querySelector('.alert-danger');
        if (erro) {
            erro.remove();
        }

        // O dia que está a ser personalizado fica destacado na grelha
        if (diaMarcado) {
            diaMarcado.classList.remove('a-personalizar');
        }
        diaMarcado = document.getElementById('dia-' + plano.data);
        if (diaMarcado) {
            diaMarcado.classList.add('a-personalizar');
        }
        window.abrirPainel('painelPessoal', botao);
    }

    document.addEventListener('click', function (ev) {
        var adicionar = ev.target.closest('[data-adicionar-pessoal]');
        var editar = ev.target.closest('[data-editar-pessoal]');
        if (adicionar) {
            ev.preventDefault();
            preencher(adicionar, { data: adicionar.dataset.data });
        } else if (editar) {
            ev.preventDefault();
            preencher(editar, JSON.parse(editar.dataset.plano));
        }
    });

    painel.addEventListener('painel-fechado', function () {
        if (diaMarcado) {
            diaMarcado.classList.remove('a-personalizar');
            diaMarcado = null;
        }
    });
})();
