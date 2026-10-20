// Alterna o modo claro/escuro sem recarregar a página.
// A escolha é guardada na base de dados (tema.php), por isso fica igual em todas as páginas.
document.querySelectorAll('.form-tema').forEach(function (form) {
    form.addEventListener('submit', function (evento) {
        evento.preventDefault();

        var escuro = document.body.classList.toggle('dark-mode');
        atualizarBotoes(escuro);

        var dados = new FormData(form);
        dados.set('tema', escuro ? 'escuro' : 'claro');
        fetch('tema.php', { method: 'POST', body: dados, headers: { 'X-Requested-With': 'fetch' } })
            .catch(function () {
                // Se falhar a gravação, volta ao tema anterior
                document.body.classList.toggle('dark-mode');
                atualizarBotoes(!escuro);
            });
    });
});

function atualizarBotoes(escuro) {
    document.querySelectorAll('.form-tema').forEach(function (form) {
        form.querySelector('input[name="tema"]').value = escuro ? 'claro' : 'escuro';
        var icone = form.querySelector('.tema-icone');
        icone.classList.toggle('bi-sun', escuro);
        icone.classList.toggle('bi-moon-stars', !escuro);
        var texto = form.querySelector('.tema-texto');
        if (texto) {
            texto.textContent = escuro ? 'Modo Claro' : 'Modo Escuro';
        }
    });
    // Botões da página Configurações
    document.querySelectorAll('button[name="tema"][value="claro"], button[name="tema"][value="escuro"]').forEach(function (b) {
        var ativo = (b.value === 'escuro') === escuro;
        b.classList.toggle('btn-secondary', ativo);
        b.classList.toggle('btn-outline-secondary', !ativo);
    });
}
