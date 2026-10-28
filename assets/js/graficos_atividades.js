// Gráficos da página Atividades (Chart.js):
//  - "Distância por treino": uma barra por treino, com a cor da modalidade (DADOS_TREINOS);
//  - "Evolução semanal por modalidade": km por semana (barras) e ritmo/velocidade (linha) (DADOS_HISTORICO).
// As cores vêm das variáveis de tokens.css, por isso mudam com o modo escuro.
(function () {
    if (typeof Chart === 'undefined') {
        return;
    }

    function cor(variavel) {
        return getComputedStyle(document.documentElement).getPropertyValue(variavel).trim();
    }

    function cores() {
        return { texto: cor('--muted'), grelha: cor('--border'), forte: cor('--text') };
    }

    // 5.5 -> "5:30"
    function mmss(minutos) {
        var seg = Math.round(minutos * 60);
        return Math.floor(seg / 60) + ':' + String(seg % 60).padStart(2, '0');
    }

    function km(valor) {
        return valor.toLocaleString('pt-PT', { maximumFractionDigits: 2 });
    }

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

    var graficos = [];

    // ---------- Distância por treino ----------
    var canvasTreinos = document.getElementById('graficoTreinos');
    if (canvasTreinos) {
        var c = cores();
        var grafico = new Chart(canvasTreinos, {
            type: 'bar',
            data: {
                labels: DADOS_TREINOS.rotulos,
                datasets: [{
                    label: 'Distância (km)',
                    data: DADOS_TREINOS.km,
                    backgroundColor: DADOS_TREINOS.tipos.map(function (t) { return cor(DADOS_TREINOS.cores[t]); }),
                    borderRadius: 6,
                    maxBarThickness: 44
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (item) {
                                var tipo = DADOS_TREINOS.tipos[item.dataIndex];
                                var nome = { corrida: 'Corrida', ciclismo: 'Ciclismo', natacao: 'Natação' }[tipo];
                                return nome + ': ' + km(item.raw) + ' km';
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: c.texto }, grid: { display: false } },
                    y: { beginAtZero: true, ticks: { color: c.texto }, grid: { color: c.grelha }, border: { display: false } }
                }
            }
        });
        grafico.recolorir = function () {
            this.data.datasets[0].backgroundColor = DADOS_TREINOS.tipos.map(function (t) { return cor(DADOS_TREINOS.cores[t]); });
        };
        graficos.push(grafico);
    }

    // ---------- Evolução semanal por modalidade ----------
    Object.keys(DADOS_HISTORICO.modalidades || {}).forEach(function (tipo) {
        var canvas = document.getElementById('grafico_' + tipo);
        if (!canvas) {
            return;
        }
        var m = DADOS_HISTORICO.modalidades[tipo];
        var c = cores();

        var g = new Chart(canvas, {
            data: {
                labels: DADOS_HISTORICO.semanas,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Distância (km)',
                        data: m.km,
                        backgroundColor: cor(m.cor),
                        borderRadius: 4,
                        order: 2,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: m.rotuloRitmo,
                        data: m.ritmo,
                        borderColor: c.forte,
                        backgroundColor: c.forte,
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
                    legend: { labels: { color: c.texto, boxWidth: 12 } },
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
                                return item.dataset.label + ': ' + km(item.raw);
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: c.texto }, grid: { display: false } },
                    y: { beginAtZero: true, position: 'left', title: { display: true, text: 'km', color: c.texto }, ticks: { color: c.texto }, grid: { color: c.grelha }, border: { display: false } },
                    y2: {
                        position: 'right',
                        // No ritmo, quanto mais baixo melhor: inverte o eixo para "subir" quando melhoras
                        reverse: m.mmss,
                        title: { display: true, text: m.rotuloRitmo, color: c.texto },
                        ticks: { color: c.texto, callback: function (v) { return m.mmss ? mmss(v) : v; } },
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });
        g.recolorir = function () {
            this.data.datasets[0].backgroundColor = cor(m.cor);
            this.data.datasets[1].borderColor = cores().forte;
            this.data.datasets[1].backgroundColor = cores().forte;
        };
        graficos.push(g);
    });

    // Quando se muda o tema, os gráficos voltam a ler as cores
    new MutationObserver(function () {
        var c = cores();
        graficos.forEach(function (g) {
            g.recolorir();
            if (g.options.plugins.legend.labels) {
                g.options.plugins.legend.labels.color = c.texto;
            }
            Object.keys(g.options.scales).forEach(function (eixo) {
                var escala = g.options.scales[eixo];
                escala.ticks.color = c.texto;
                if (escala.title) {
                    escala.title.color = c.texto;
                }
                if (escala.grid && escala.grid.color) {
                    escala.grid.color = c.grelha;
                }
            });
            g.update();
        });
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
})();
