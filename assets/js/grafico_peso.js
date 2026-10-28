// Gráfico da evolução do peso no dashboard (Chart.js).
// Os dados vêm do PHP na variável DADOS_PESO.
(function () {
    var canvas = document.getElementById('graficoPeso');
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    // As cores vêm das variáveis de tokens.css (mudam com o modo escuro)
    function cor(variavel) {
        return getComputedStyle(document.documentElement).getPropertyValue(variavel).trim();
    }
    function cores() {
        return { texto: cor('--muted'), grelha: cor('--border'), peso: cor('--chart-1'), objetivo: cor('--success'), gordura: cor('--chart-2') };
    }
    var c0 = cores();

    var series = [{
        label: 'Peso (kg)',
        data: DADOS_PESO.peso,
        borderColor: c0.peso,
        backgroundColor: 'transparent',
        fill: false,
        tension: 0.3,
        pointRadius: 4,
        yAxisID: 'y'
    }];

    if (DADOS_PESO.objetivo !== null) {
        series.push({
            label: 'Objetivo',
            data: DADOS_PESO.peso.map(function () { return DADOS_PESO.objetivo; }),
            borderColor: c0.objetivo,
            borderDash: [6, 6],
            pointRadius: 0,
            fill: false,
            yAxisID: 'y'
        });
    }

    if (DADOS_PESO.gordura.some(function (v) { return v !== null; })) {
        series.push({
            label: '% Massa Gorda',
            data: DADOS_PESO.gordura,
            borderColor: c0.gordura,
            tension: 0.3,
            pointRadius: 3,
            spanGaps: true,
            fill: false,
            yAxisID: 'y2'
        });
    }

    var c = cores();
    var grafico = new Chart(canvas, {
        type: 'line',
        data: { labels: DADOS_PESO.datas, datasets: series },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { labels: { color: c.texto } } },
            scales: {
                x: { ticks: { color: c.texto }, grid: { color: c.grelha } },
                y: { position: 'left', title: { display: true, text: 'kg', color: c.texto }, ticks: { color: c.texto }, grid: { color: c.grelha } },
                y2: { position: 'right', display: series.length > 2, title: { display: true, text: '%', color: c.texto }, ticks: { color: c.texto }, grid: { display: false } }
            }
        }
    });

    // Quando se muda o tema, atualiza as cores do gráfico
    new MutationObserver(function () {
        var c = cores();
        grafico.data.datasets.forEach(function (d) {
            d.borderColor = d.yAxisID === 'y2' ? c.gordura : (d.label === 'Objetivo' ? c.objetivo : c.peso);
        });
        grafico.options.plugins.legend.labels.color = c.texto;
        ['x', 'y', 'y2'].forEach(function (eixo) {
            grafico.options.scales[eixo].ticks.color = c.texto;
            if (grafico.options.scales[eixo].title) {
                grafico.options.scales[eixo].title.color = c.texto;
            }
            if (eixo !== 'y2') {
                grafico.options.scales[eixo].grid.color = c.grelha;
            }
        });
        grafico.update();
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
})();
