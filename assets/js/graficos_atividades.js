// Gráficos da página Histórico (Chart.js): km por semana (barras) e ritmo/velocidade (linha).
// Os dados vêm do PHP na variável DADOS_HISTORICO.
(function () {
    if (typeof Chart === 'undefined') {
        return;
    }

    function cores() {
        var escuro = document.body.classList.contains('dark-mode');
        return { texto: escuro ? '#9ca3af' : '#6b7280', grelha: escuro ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)' };
    }

    // 5.5 -> "5:30"
    function mmss(minutos) {
        var seg = Math.round(minutos * 60);
        return Math.floor(seg / 60) + ':' + String(seg % 60).padStart(2, '0');
    }

    var graficos = [];

    Object.keys(DADOS_HISTORICO.modalidades).forEach(function (tipo) {
        var canvas = document.getElementById('grafico_' + tipo);
        if (!canvas) {
            return;
        }
        var m = DADOS_HISTORICO.modalidades[tipo];
        var c = cores();

        graficos.push(new Chart(canvas, {
            data: {
                labels: DADOS_HISTORICO.semanas,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Distância (km)',
                        data: m.km,
                        backgroundColor: m.cor + '99',
                        borderRadius: 4,
                        order: 2,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: m.rotuloRitmo,
                        data: m.ritmo,
                        borderColor: '#fd7e14',
                        backgroundColor: '#fd7e14',
                        tension: 0.3,
                        spanGaps: true,
                        pointRadius: 3,
                        order: 1,   // linha desenhada por cima das barras
                        yAxisID: 'y2'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { labels: { color: c.texto } },
                    tooltip: {
                        callbacks: {
                            title: function (itens) { return 'Semana de ' + itens[0].label; },
                            label: function (item) {
                                if (item.raw === null) {
                                    return null;
                                }
                                if (item.dataset.yAxisID === 'y2' && m.mmss) {
                                    return item.dataset.label + ': ' + mmss(item.raw);
                                }
                                return item.dataset.label + ': ' + item.raw.toLocaleString('pt-PT');
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: c.texto }, grid: { color: c.grelha } },
                    y: { beginAtZero: true, position: 'left', title: { display: true, text: 'km', color: c.texto }, ticks: { color: c.texto }, grid: { color: c.grelha } },
                    y2: {
                        position: 'right',
                        // No ritmo, quanto mais baixo melhor: inverte o eixo para "subir" quando melhoras
                        reverse: m.mmss,
                        title: { display: true, text: m.rotuloRitmo, color: c.texto },
                        ticks: { color: c.texto, callback: function (v) { return m.mmss ? mmss(v) : v; } },
                        grid: { display: false }
                    }
                }
            }
        }));
    });

    // Quando se muda o tema, atualiza as cores dos gráficos
    new MutationObserver(function () {
        var c = cores();
        graficos.forEach(function (g) {
            g.options.plugins.legend.labels.color = c.texto;
            ['x', 'y', 'y2'].forEach(function (eixo) {
                g.options.scales[eixo].ticks.color = c.texto;
                if (g.options.scales[eixo].title) {
                    g.options.scales[eixo].title.color = c.texto;
                }
                if (eixo !== 'y2') {
                    g.options.scales[eixo].grid.color = c.grelha;
                }
            });
            g.update();
        });
    }).observe(document.body, { attributes: true, attributeFilter: ['class'] });
})();
