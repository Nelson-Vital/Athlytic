// Formulário de atividade: os campos mudam com o tipo e o ritmo é calculado enquanto se escreve.
// Musculação: sem distância nem ritmo. Natação: metros e min/100 m. Ciclismo: velocidade em km/h.
(function () {
    var form = document.querySelector('[data-form-atividade]');
    if (!form) {
        return;
    }
    var campoDistancia = document.getElementById('campoDistancia');
    var distancia = document.getElementById('distancia');
    var unidade = document.getElementById('unidadeDistancia');
    var caixaRitmo = document.getElementById('ritmoCalculado');
    var rotuloRitmo = document.getElementById('ritmoRotulo');
    var valorRitmo = document.getElementById('ritmoValor');
    var campoFit = document.getElementById('campoFit');

    function tipoEscolhido() {
        var marcado = form.querySelector('input[name="tipo"]:checked');
        return marcado ? marcado.value : '';
    }

    function numero(id) {
        var v = parseFloat(String(document.getElementById(id).value).replace(',', '.'));
        return isNaN(v) ? 0 : v;
    }

    function mmss(segundos) {
        segundos = Math.round(segundos);
        return Math.floor(segundos / 60) + ':' + String(segundos % 60).padStart(2, '0');
    }

    function atualizarRitmo() {
        var tipo = tipoEscolhido();
        var segundos = numero('dur_h') * 3600 + numero('dur_min') * 60 + numero('dur_seg');
        var dist = parseFloat(String(distancia.value).replace(',', '.'));
        var texto = '—';
        if (segundos > 0 && dist > 0) {
            if (tipo === 'natacao') {
                texto = mmss(segundos / (dist / 100)) + '/100 m';
            } else if (tipo === 'ciclismo') {
                texto = (dist / (segundos / 3600)).toLocaleString('pt-PT', { maximumFractionDigits: 1 }) + ' km/h';
            } else {
                texto = mmss(segundos / dist) + '/km';
            }
        }
        valorRitmo.textContent = texto;
    }

    function atualizarTipo() {
        var tipo = tipoEscolhido();
        var semDistancia = tipo === 'musculacao';
        campoDistancia.hidden = semDistancia;
        distancia.disabled = semDistancia;
        caixaRitmo.hidden = semDistancia;
        unidade.textContent = tipo === 'natacao' ? 'm' : 'km';
        distancia.placeholder = tipo === 'natacao' ? '1500' : '6,3';
        rotuloRitmo.textContent = tipo === 'ciclismo' ? 'Velocidade média' : 'Ritmo calculado';
        if (campoFit) {
            campoFit.hidden = tipo !== 'corrida';
        }
        atualizarRitmo();
    }

    form.addEventListener('change', function (ev) {
        if (ev.target.name === 'tipo') {
            atualizarTipo();
        }
    });
    form.addEventListener('input', atualizarRitmo);

    // "Adicionar notas ou fotos" (telemóvel e tablet)
    var botaoNotas = form.querySelector('.botao-mais-notas');
    if (botaoNotas) {
        botaoNotas.addEventListener('click', function () {
            var notas = document.getElementById('maisNotas');
            notas.classList.add('aberto');
            botaoNotas.hidden = true;
            botaoNotas.setAttribute('aria-expanded', 'true');
            document.getElementById('notas').focus();
        });
    }

    atualizarTipo();
})();
