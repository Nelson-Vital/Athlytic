// Página de registo: calcula a idade, o IMC, a classificação da % de gordura e o FFMI
// enquanto a pessoa escreve (tudo no navegador, sem enviar nada ao servidor).
(function () {
    const $ = (id) => document.getElementById(id);
    const peso = $('peso'), altura = $('altura'), gordura = $('gordura'), nascimento = $('data_nascimento');
    const sexos = document.querySelectorAll('input[name="sexo"]');

    // % de gordura: tabela do American Council on Exercise (ACE), diferente para cada sexo.
    // Cada linha: [limite superior, texto, cor]
    const TABELA_GORDURA = {
        M: [[6, 'Muito baixa', 'imc-aviso'], [14, 'Atleta', 'imc-ok'], [18, 'Em forma', 'imc-ok'], [25, 'Média', 'imc-aviso'], [Infinity, 'Elevada', 'imc-alerta']],
        F: [[14, 'Muito baixa', 'imc-aviso'], [21, 'Atleta', 'imc-ok'], [25, 'Em forma', 'imc-ok'], [32, 'Média', 'imc-aviso'], [Infinity, 'Elevada', 'imc-alerta']],
    };
    // FFMI normalizado (Kouri et al., 1995): também com valores de referência por sexo
    const TABELA_FFMI = {
        M: [[17, 'Pouca massa muscular', 'imc-aviso'], [19, 'Massa muscular média', 'imc-ok'], [21, 'Acima da média', 'imc-ok'], [23, 'Musculado', 'imc-ok'], [Infinity, 'Muito musculado', 'imc-ok']],
        F: [[14, 'Pouca massa muscular', 'imc-aviso'], [16, 'Massa muscular média', 'imc-ok'], [17, 'Acima da média', 'imc-ok'], [19, 'Musculada', 'imc-ok'], [Infinity, 'Muito musculada', 'imc-ok']],
    };

    const procurar = (tabela, valor) => tabela.find((linha) => valor < linha[0]);
    const formatar = (n) => n.toFixed(1).replace('.', ',');

    function mostrar(caixaId, valorId, classeId, texto, classe, cor) {
        const caixa = $(caixaId);
        caixa.classList.remove('d-none', 'imc-ok', 'imc-aviso', 'imc-alerta');
        if (cor) caixa.classList.add(cor);
        $(valorId).textContent = texto;
        $(classeId).textContent = classe;
    }

    function atualizar() {
        const kg = parseFloat(String(peso.value).replace(',', '.'));
        const metros = parseFloat(altura.value) / 100;
        const pct = parseFloat(String(gordura.value).replace(',', '.'));
        const sexo = (document.querySelector('input[name="sexo"]:checked') || {}).value;
        const temBase = kg >= 30 && kg <= 300 && metros >= 1 && metros <= 2.5;
        const temGordura = temBase && pct >= 3 && pct <= 60 && TABELA_GORDURA[sexo];

        // 1) IMC = peso / altura²  (classes da OMS)
        if (!temBase) {
            mostrar('caixaImc', 'valorImc', 'classeImc', '—', 'Indica o peso e a altura', null);
        } else {
            const imc = kg / (metros * metros);
            let [texto, cor] = imc < 18.5 ? ['Abaixo do peso', 'imc-aviso'] : imc < 25 ? ['Peso normal', 'imc-ok'] : imc < 30 ? ['Excesso de peso', 'imc-aviso'] : ['Obesidade', 'imc-alerta'];
            // 3) Nota: IMC alto mas gordura de atleta/em forma => o peso extra é músculo
            if (imc >= 25 && temGordura && procurar(TABELA_GORDURA[sexo], pct)[2] === 'imc-ok') {
                [texto, cor] = ['Elevado devido à massa muscular', 'imc-ok'];
            }
            mostrar('caixaImc', 'valorImc', 'classeImc', formatar(imc), texto, cor);
        }

        // 2) % de gordura classificada para o sexo e FFMI = massa magra / altura²
        ['caixaGordura', 'caixaFfmi', 'notaIndicadores'].forEach((id) => $(id).classList.toggle('d-none', !temGordura));
        if (temGordura) {
            const [, textoG, corG] = procurar(TABELA_GORDURA[sexo], pct);
            mostrar('caixaGordura', 'valorGordura', 'classeGordura', formatar(pct) + '%', textoG, corG);

            const massaMagra = kg * (1 - pct / 100);
            const ffmi = massaMagra / (metros * metros) + 6.1 * (1.8 - metros); // normalizado para 1,80 m
            const [, textoF, corF] = procurar(TABELA_FFMI[sexo], ffmi);
            mostrar('caixaFfmi', 'valorFfmi', 'classeFfmi', formatar(ffmi), textoF + ' · ' + formatar(massaMagra) + ' kg magros', corF);
        }
    }

    function atualizarIdade() {
        const d = new Date(nascimento.value + 'T00:00:00');
        if (isNaN(d)) {
            $('idadeTexto').textContent = '';
            return;
        }
        const hoje = new Date();
        let anos = hoje.getFullYear() - d.getFullYear();
        if (hoje.getMonth() < d.getMonth() || (hoje.getMonth() === d.getMonth() && hoje.getDate() < d.getDate())) {
            anos--;
        }
        $('idadeTexto').textContent = anos >= 0 && anos < 130 ? '· ' + anos + ' anos' : '';
    }

    [peso, altura, gordura].forEach((campo) => campo.addEventListener('input', atualizar));
    sexos.forEach((r) => r.addEventListener('change', atualizar));
    nascimento.addEventListener('input', atualizarIdade);
    nascimento.addEventListener('change', atualizarIdade);
    atualizar();
    atualizarIdade();
})();
