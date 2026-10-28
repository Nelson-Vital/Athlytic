// Alterna o modo claro/escuro sem recarregar a página.
// O tema é o atributo data-theme do <html> (as cores vêm de tokens.css) e fica guardado
// na base de dados (tema.php), por isso é igual em todas as páginas.
function temaEscuro() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
}

function aplicarTema(escuro) {
    var html = document.documentElement;
    html.setAttribute('data-theme', escuro ? 'dark' : 'light');
    html.setAttribute('data-bs-theme', escuro ? 'dark' : 'light');

    // Botão do menu lateral
    document.querySelectorAll('.form-tema').forEach(function (form) {
        form.querySelector('input[name="tema"]').value = escuro ? 'claro' : 'escuro';
        var icone = form.querySelector('.tema-icone');
        icone.classList.toggle('bi-sun', escuro);
        icone.classList.toggle('bi-moon', !escuro);
        form.querySelector('.tema-texto').textContent = escuro ? 'Modo claro' : 'Modo escuro';
    });
    // Cartões Claro/Escuro das Configurações
    document.querySelectorAll('.tema-opcao[data-tema]').forEach(function (b) {
        var ativo = (b.dataset.tema === 'escuro') === escuro;
        b.classList.toggle('ativo', ativo);
        b.setAttribute('aria-pressed', ativo ? 'true' : 'false');
    });
    // Os gráficos voltam a desenhar-se com as cores do tema
    document.dispatchEvent(new CustomEvent('tema-mudou', { detail: { escuro: escuro } }));
}

function guardarTema(escuro, form) {
    var dados = new FormData(form);
    dados.set('tema', escuro ? 'escuro' : 'claro');
    return fetch('tema.php', { method: 'POST', body: dados, headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { if (!r.ok) { throw new Error(); } })
        .catch(function () { aplicarTema(!escuro); }); // se falhar, volta ao tema anterior
}

document.querySelectorAll('.form-tema').forEach(function (form) {
    form.addEventListener('submit', function (evento) {
        evento.preventDefault();
        var escuro = !temaEscuro();
        aplicarTema(escuro);
        guardarTema(escuro, form);
    });
});
