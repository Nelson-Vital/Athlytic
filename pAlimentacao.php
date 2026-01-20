<?php
date_default_timezone_set('Europe/Lisbon');
$hoje_num = date('w'); // 0=Domingo ... 6=Sábado
$hoje_nome = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'][$hoje_num];

// Escolhe o objetivo aqui (perda | manutencao | ganho)
$objetivo = 'manutencao';

$planos = [
    'perda' => [
        'nome' => 'Plano Alimentar – Perda de Peso (Défice Calórico)',
        'descricao' => 'Foco em alimentos ricos em volume e baixos em calorias. ~500 kcal de défice diário.',
        'macros' => 'Proteína alta, hidratos moderados, gorduras controladas',
        'semana' => [
            1 => ['dia' => 'Segunda',     'refeicoes' => '
                <ul>
                    <li><strong>Café da manhã:</strong> 3 claras + 1 ovo inteiro, 40g aveia, 1 maçã</li>
                    <li><strong>Lanche manhã:</strong> Iogurte natural 0% 150g + 10g whey (opcional)</li>
                    <li><strong>Almoço:</strong> 150g frango grelhado, 200g brócolos/cozidos, 80g arroz integral (pesado cru)</li>
                    <li><strong>Lanche tarde:</strong> 1 lata de atum em água + 1 cenoura</li>
                    <li><strong>Jantar:</strong> 150g pescada grelhada, salada à vontade + 50g batata-doce</li>
                    <li><strong>Ceia:</strong> 100g queijo quark 0% ou 20g caseína</li>
                </ul>'],
            2 => ['dia' => 'Terça',       'refeicoes' => '
                <ul>
                    <li><strong>Café da manhã:</strong> Omelete de 5 claras + espinafres, torrada integral</li>
                    <li><strong>Lanche manhã:</strong> 1 banana + 10g manteiga de amendoim natural</li>
                    <li><strong>Almoço:</strong> 150g peru grelhado, 200g legumes variados, 70g quinoa</li>
                    <li><strong>Lanche tarde:</strong> Shake whey + água</li>
                    <li><strong>Jantar:</strong> 150g salmão grelhado (1x/semana), salada grande + azeite 5ml</li>
                    <li><strong>Ceia:</strong> 150g iogurte grego 0%</li>
                </ul>'],
            3 => ['dia' => 'Quarta',      'refeicoes' => 'Mesma estrutura da Segunda-feira (facilita a preparação)' ],
            4 => ['dia' => 'Quinta',      'refeicoes' => 'Mesma estrutura da Terça-feira' ],
            5 => ['dia' => 'Sexta',       'refeicoes' => '
                <ul>
                    <li><strong>Refeição livre controlada:</strong> Pode trocar o jantar por uma opção mais saborosa (ex: hambúrguer caseiro sem pão, pizza low-carb, etc.) mantendo proteína alta</li>
                </ul>'],
            6 => ['dia' => 'Sábado',      'refeicoes' => '<strong>Refeed dia:</strong> +50-70g de hidratos (arroz, batata, massa integral) nas refeições principais. Ideal para recuperação hormonal e adesão.' ],
            0 => ['dia' => 'Domingo',     'refeicoes' => '<strong>Dia mais flexível:</strong> Mantém proteína alta, mas permite mais liberdade nas escolhas. Ideal para refeições em família.' ]
        ]
    ],

    'manutencao' => [
        'nome' => 'Plano Alimentar – Manutenção / Recomposição',
        'descricao' => 'Calorias de manutenção. Equilíbrio perfeito entre nutrientes.',
        'macros' => 'Proteína 2g/kg | Hidratos e gorduras equilibrados',
        'semana' => [
            1 => ['dia' => 'Segunda',     'refeicoes' => '
                <ul>
                    <li><strong>Café da manhã:</strong> 3 ovos inteiros + 50g aveia + 1 banana</li>
                    <li><strong>Lanche manhã:</strong> 150g iogurte grego + 15g frutos secos</li>
                    <li><strong>Almoço:</strong> 180g frango, 100g arroz integral, legumes à vontade + 10ml azeite</li>
                    <li><strong>Lanche tarde:</strong> Sandes integral + 100g fiambre peru + 30g queijo magro</li>
                    <li><strong>Jantar:</strong> 180g dourada grelhada, 200g batata-doce, salada</li>
                    <li><strong>Ceia:</strong> 30g caseína ou 200g quark + 10g chocolate 85%</li>
                </ul>'],
            2 => ['dia' => 'Terça',       'refeicoes' => '
                <ul>
                    <li><strong>Café da manhã:</strong> Panquecas de aveia + claras + whey + frutas</li>
                    <li><strong>Lanche manhã:</strong> 1 maçã + 20g manteiga amendoim</li>
                    <li><strong>Almoço:</strong> 180g carne magra, 120g massa integral, molho tomate natural</li>
                    <li><strong>Lanche tarde:</strong> 2 fatias pão proteína + 80g atum natural</li>
                    <li><strong>Jantar:</strong> Omelete mista (4 ovos) + vegetais + 80g arroz</li>
                </ul>'],
            3 => ['dia' => 'Quarta',      'refeicoes' => 'Repete Segunda-feira' ],
            4 => ['dia' => 'Quinta',      'refeicoes' => 'Repete Terça-feira' ],
            5 => ['dia' => 'Sexta',       'refeicoes' => 'Almoço e jantar podem ser mais sociais (mantendo proteína alta)' ],
            6 => ['dia' => 'Sábado',      'refeicoes' => '<strong>Refeição livre:</strong> Pode incluir sobremesa ou prato favorito (1 refeição livre por semana)' ],
            0 => ['dia' => 'Domingo',     'refeicoes' => '<strong>Meal prep day:</strong> Prepara as refeições da semana e aproveita para uma refeição em família.' ]
        ]
    ],

    'ganho' => [
        'nome' => 'Plano Alimentar – Ganho de Massa (Superavit)',
        'descricao' => 'Calorias acima da manutenção. Foco em hipertrofia e recuperação.',
        'macros' => 'Proteína 1.8-2.2g/kg | Hidratos altos | Gorduras moderadas-altas',
        'semana' => [
            1 => ['dia' => 'Segunda (Treino Pesado)', 'refeicoes' => '
                <ul>
                    <li><strong>Café da manhã:</strong> 4 ovos + 80g aveia + 1 banana + 20g manteiga amendoim</li>
                    <li><strong>Lanche 1:</strong> Shake 40g whey + 300ml leite magro + 50g aveia</li>
                    <li><strong>Almoço:</strong> 220g frango, 150g arroz, legumes + 15ml azeite</li>
                    <li><strong>Lanche 2 (pós-treino):</strong> Shake whey + maltodextrina/dextrose + fruta</li>
                    <li><strong>Jantar:</strong> 200g salmão, 250g batata-doce, salada</li>
                    <li><strong>Ceia:</strong> 250g quark + 30g frutos secos + mel</li>
                </ul>'],
            2 => ['dia' => 'Terça',       'refeicoes' => 'Mesma estrutura da Segunda, trocando fontes de proteína (carne vermelha magra, atum, etc.)' ],
            3 => ['dia' => 'Quarta',      'refeicoes' => 'Repete Segunda' ],
            4 => ['dia' => 'Quinta',      'refeicoes' => 'Repete Terça' ],
            5 => ['dia' => 'Sexta',       'refeicoes' => 'Dia de treino intenso → +1 shake ou refeição rica em hidratos' ],
            6 => ['dia' => 'Sábado',      'refeicoes' => '<strong>Dia alto de hidratos:</strong> +100-150g de hidratos extras (arroz, massa, batata)' ],
            0 => ['dia' => 'Domingo',     'refeicoes' => '<strong>Dia de descanso:</strong> Mantém calorias altas, mas pode ser mais flexível (pizza caseira, hambúrguer, etc.)' ]
        ]
    ]
];

$plano_atual = $planos[$objetivo];
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plano Alimentar | ATHLYTIC</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="icon" href="IMGS/Athlytic_logo_sem_texto.jpg" type="image/jpeg">
    <link rel="apple-touch-icon" href="IMGS/Athlytic_logo_sem_texto.jpg">

    <style>
        body { background-color: #f5f7fa; }
        .sidebar { width: 250px; height: 100vh; background: #111827; color: white; position: fixed; padding: 20px 0; }
        .sidebar h2 { font-size: 24px; text-align: center; margin-bottom: 40px; font-weight: bold; }
        .sidebar a { display: block; padding: 14px 25px; color: #d1d5db; text-decoration: none; font-size: 16px; }
        .sidebar a:hover, .sidebar a.active { background: #1f2937; color: white; }
        .content { margin-left: 250px; padding: 40px; }
        .dia-card { transition: all 0.3s; border-left: 5px solid #ddd; }
        .dia-card.hoje { border-left-color: #198754; background: #f8fff9; transform: scale(1.02); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .dia-titulo { font-size: 1.4rem; font-weight: bold; color: #198754; }
    </style>
</head>
<body>

<div class="sidebar">
        <!-- Logo clicável + Título -->
        <div class="text-center mb-4">
            <a href="dashboard.php" class="navbar-brand d-block">
                <img src="IMGS/Athlytic_logo.jpg" alt="Athlytic Logo" class="img-fluid" style="max-width: 160px; height: auto; transition: transform 0.3s;">
            </a>
            <!-- Opcional: texto ATHLYTIC por baixo do logo (podes remover se quiseres só a imagem) -->
            <!-- <h2 class="mt-2 mb-0" style="font-size: 1.4rem; letter-spacing: 1px;">ATHLYTIC</h2> -->
        </div>

        <!-- Menu com ícones -->
        <a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="bi bi-house-door-fill me-2"></i> Início
        </a>
        <a href="inserir_atividade.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'inserir_atividade.php') ? 'active' : ''; ?>">
            <i class="bi bi-plus-circle-dotted me-2"></i> Inserir Atividade
        </a>
        <a href="pCorrida.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'pCorrida.php') ? 'active' : ''; ?>">
            <i class="bi bi-geo-alt-fill me-2"></i> Plano de Corrida
        </a>
        <a href="pMusculacao.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'pMusculacao.php') ? 'active' : ''; ?>">
            <i class="bi bi-trophy-fill me-2"></i> Musculação
        </a>
        <a href="pAlimentacao.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'pAlimentacao.php') ? 'active' : ''; ?>">
            <i class="bi bi-egg-fried me-2"></i> Plano Alimentar
        </a>
        <a href="def.php">
            <i class="bi bi-gear-fill me-2"></i> Configurações
        </a>
    </div>

    <div class="content">
        <h1 class="mb-4"><i class="bi bi-egg-fried text-success"></i> Plano Alimentar Semanal</h1>
        
        <div class="alert alert-success">
            <strong>Hoje é <?php echo $hoje_nome; ?>, <?php echo date('d \d\e F \d\e Y'); ?></strong>
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h3 class="card-title"><?php echo $plano_atual['nome']; ?></h3>
                <p class="text-muted"><?php echo $plano_atual['descricao']; ?></p>
                <small class="text-success"><strong>Macros base:</strong> <?php echo $plano_atual['macros']; ?></small>
            </div>
        </div>

        <div class="row">
            <?php 
            $ordem = [1,2,3,4,5,6,0];
            foreach ($ordem as $d): 
                $dia = $plano_atual['semana'][$d];
                $eh_hoje = ($d == $hoje_num) ? 'hoje' : '';
            ?>
                <div class="col-md-6 mb-4">
                    <div class="card dia-card <?php echo $eh_hoje; ?>">
                        <div class="card-body">
                            <?php if ($eh_hoje): ?>
                                <span class="badge bg-success float-end">HOJE</span>
                            <?php endif; ?>
                            <h4><?php echo $dia['dia']; ?></h4>
                            <div class="dia-titulo mt-2">Refeições do dia</div>
                            <div class="mt-3"><?php echo $dia['refeicoes']; ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-5 text-center">
            <button class="btn btn-lg btn-success" onclick="alert('Refeições registadas! Continue assim! 🍽️')">
                <i class="bi bi-check2-circle"></i> Marcar refeições de hoje como concluídas
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>