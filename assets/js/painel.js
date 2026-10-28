// Painel lateral (computador, a partir de 1200 px) ou folha que sobe de baixo (ecrãs mais pequenos).
// Marcação: <section class="painel" id="..." role="dialog" aria-labelledby="..."> e <div class="painel-fundo">.
// Abre com um elemento data-abrir-painel="id"; fecha com data-fechar-painel, a tecla Esc ou um clique no fundo.
(function () {
    var fundo = document.querySelector('.painel-fundo');
    var lateral = window.matchMedia('(min-width: 1200px)');
    var aberto = null;
    var origem = null;

    function focaveis(painel) {
        return Array.prototype.filter.call(
            painel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'),
            function (el) { return el.offsetParent !== null; }
        );
    }

    function ajustarModo() {
        if (!aberto) {
            return;
        }
        // No computador o painel fica ao lado do conteúdo; nos ecrãs pequenos tapa a página
        aberto.setAttribute('aria-modal', lateral.matches ? 'false' : 'true');
        document.body.classList.toggle('painel-aberto-folha', !lateral.matches);
    }

    window.abrirPainel = function (id, quem, focar) {
        var painel = document.getElementById(id);
        if (!painel) {
            return;
        }
        if (aberto && aberto !== painel) {
            window.fecharPainel(false);
        }
        aberto = painel;
        origem = quem || document.activeElement;
        painel.classList.add('aberto');
        painel.removeAttribute('aria-hidden');
        if (fundo) {
            fundo.classList.add('aberto');
        }
        document.body.classList.add('painel-aberto');
        ajustarModo();
        painel.dispatchEvent(new CustomEvent('painel-aberto'));
        if (focar !== false) {
            setTimeout(function () {
                var alvo = painel.querySelector('[data-foco-inicial]') || focaveis(painel)[0];
                if (alvo) {
                    alvo.focus();
                }
            }, 60);
        }
    };

    window.fecharPainel = function (devolverFoco) {
        if (!aberto) {
            return;
        }
        aberto.classList.remove('aberto');
        aberto.setAttribute('aria-hidden', 'true');
        if (fundo) {
            fundo.classList.remove('aberto');
        }
        document.body.classList.remove('painel-aberto', 'painel-aberto-folha');
        aberto.dispatchEvent(new CustomEvent('painel-fechado'));
        aberto = null;
        if (devolverFoco !== false && origem && document.contains(origem)) {
            origem.focus();
        }
        origem = null;
    };

    document.addEventListener('click', function (ev) {
        // Botão + da barra inferior: na página Atividades abre o painel em vez de mudar de página
        var registo = ev.target.closest('[data-abrir-registo]');
        if (registo && document.getElementById('painelRegisto')) {
            ev.preventDefault();
            window.abrirPainel('painelRegisto', registo);
            return;
        }
        var abrir = ev.target.closest('[data-abrir-painel]');
        if (abrir) {
            ev.preventDefault();
            window.abrirPainel(abrir.getAttribute('data-abrir-painel'), abrir);
            return;
        }
        if (ev.target.closest('[data-fechar-painel]') || ev.target === fundo) {
            ev.preventDefault();
            window.fecharPainel();
        }
    });

    document.addEventListener('keydown', function (ev) {
        if (!aberto) {
            return;
        }
        if (ev.key === 'Escape') {
            window.fecharPainel();
        } else if (ev.key === 'Tab' && !lateral.matches) {
            // Folha (modal): o foco não sai do painel
            var lista = focaveis(aberto);
            if (!lista.length) {
                return;
            }
            var primeiro = lista[0];
            var ultimo = lista[lista.length - 1];
            if (ev.shiftKey && document.activeElement === primeiro) {
                ev.preventDefault();
                ultimo.focus();
            } else if (!ev.shiftKey && document.activeElement === ultimo) {
                ev.preventDefault();
                primeiro.focus();
            }
        }
    });

    lateral.addEventListener('change', ajustarModo);

    // Painel aberto pelo servidor (ex.: erros no formulário ou ?registar=1)
    var inicial = document.querySelector('.painel.aberto');
    if (inicial) {
        inicial.classList.remove('aberto');
        window.abrirPainel(inicial.id, null, true);
    }
})();
