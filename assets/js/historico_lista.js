// Histórico: o filtro por tipo e as páginas da lista de treinos mudam sem recarregar a página.
// Vai buscar a mesma página ao servidor e troca apenas o cartão #listaTreinos.
(function () {
    const cartao = document.getElementById('listaTreinos');
    if (!cartao || !window.fetch || !window.DOMParser) {
        return;
    }

    async function carregar(url, guardarNoHistorico) {
        cartao.style.opacity = '0.5';
        try {
            const resposta = await fetch(url, { credentials: 'same-origin' });
            if (!resposta.ok || resposta.redirected) {
                throw new Error('resposta inválida');
            }
            const doc = new DOMParser().parseFromString(await resposta.text(), 'text/html');
            const novo = doc.getElementById('listaTreinos');
            if (!novo) {
                throw new Error('lista não encontrada');
            }
            cartao.innerHTML = novo.innerHTML;
            if (guardarNoHistorico) {
                history.pushState({ lista: true }, '', url);
            }
        } catch (e) {
            window.location.href = url;   // se algo falhar, abre a página normalmente
        } finally {
            cartao.style.opacity = '';
        }
    }

    cartao.addEventListener('click', function (ev) {
        const link = ev.target.closest('a[href^="historico.php"]');
        if (!link || ev.ctrlKey || ev.metaKey || ev.shiftKey) {
            return;
        }
        ev.preventDefault();
        carregar(link.href, true);
    });

    // Botões "voltar" e "avançar" do navegador
    window.addEventListener('popstate', function () {
        carregar(window.location.href, false);
    });
})();
