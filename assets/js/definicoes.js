// Configurações: interruptores, barra "Tens alterações por guardar" e cartões de tema.
(function () {
    var form = document.getElementById('formConfig');
    var barra = document.getElementById('barraAlteracoes');

    // Interruptores (checkbox com role="switch"): aria-checked acompanha o estado
    form.querySelectorAll('[role="switch"]').forEach(function (s) {
        s.addEventListener('change', function () {
            s.setAttribute('aria-checked', s.checked ? 'true' : 'false');
        });
    });

    // A barra só aparece quando o formulário é diferente do que veio do servidor
    function estado() {
        return Array.from(form.elements).map(function (el) {
            if (!el.name || el.type === 'hidden') {
                return '';
            }
            return (el.type === 'checkbox' || el.type === 'radio') ? el.checked : el.value;
        }).join('|');
    }
    var inicial = estado();
    var comErros = document.querySelector('.alert-danger') !== null;
    function atualizar() {
        barra.hidden = !comErros && estado() === inicial;
    }
    form.addEventListener('input', atualizar);
    form.addEventListener('change', atualizar);
    form.addEventListener('reset', function () {
        // O reset só muda os valores depois deste evento
        setTimeout(function () {
            form.querySelectorAll('[role="switch"]').forEach(function (s) {
                s.setAttribute('aria-checked', s.checked ? 'true' : 'false');
            });
            comErros = false;
            atualizar();
        });
    });
    atualizar();

    // Cartões Claro/Escuro: mudam o tema logo, sem recarregar (tema.js)
    var formTema = document.querySelector('.form-tema-opcoes');
    if (formTema && window.aplicarTema) {
        formTema.addEventListener('click', function (ev) {
            var botao = ev.target.closest('.tema-opcao');
            if (!botao) {
                return;
            }
            ev.preventDefault();
            var escuro = botao.dataset.tema === 'escuro';
            aplicarTema(escuro);
            guardarTema(escuro, formTema);
        });
    }
})();
