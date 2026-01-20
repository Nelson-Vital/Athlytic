<?php
date_default_timezone_set('Europe/Lisbon');
$hoje_num = date('w'); // 0=Domingo, 1=Segunda... 6=Sábado
$hoje_nome = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'][$hoje_num];

// Planos de corrida (podes escolher qual queres mostrar mudando a variável $nivel)
$nivel = 'avancado'; // opções: iniciante | intermediario | avancado

$planos = [
    'iniciante' => [
        'nome' => 'Plano Iniciante – 3x por semana',
        'descricao' => 'Ideal para quem está a começar ou voltou há pouco tempo.',
        'semana' => [
            1 => ['dia' => 'Segunda',   'treino' => 'Corrida leve',           'detalhes' => '20-25 min ritmo conversacional (podes falar sem ofegar)'],
            2 => ['dia' => 'Terça',     'treino' => 'Descanso ou caminhada',  'detalhes' => 'Recuperação ativa'],
            3 => ['dia' => 'Quarta',    'treino' => 'Corrida leve',           'detalhes' => '25-30 min ritmo confortável'],
            4 => ['dia' => 'Quinta',    'treino' => 'Descanso',               'detalhes' => 'Recuperação total'],
            5 => ['dia' => 'Sexta',     'treino' => 'Descanso ou caminhada',  'detalhes' => 'Opcional'],
            6 => ['dia' => 'Sábado',    'treino' => 'Corrida longa',          'detalhes' => '30-35 min ritmo muito leve'],
            0 => ['dia' => 'Domingo',   'treino' => 'DESCANSO TOTAL',         'detalhes' => 'Recupera e prepara a próxima semana!']
        ]
    ],
    'intermediario' => [
        'nome' => 'Plano Intermédio – 4x por semana',
        'descricao' => 'Para quem já corre regularmente e quer evoluir.',
        'semana' => [
            1 => ['dia' => 'Segunda',   'treino' => 'Descanso ou corrida leve', 'detalhes' => '20-30 min recuperação ativa'],
            2 => ['dia' => 'Terça',     'treino' => 'Intervalado',             'detalhes' => 'Aquecimento 10 min + 6x (1 min rápido / 2 min trote) + Desaquecimento 10 min'],
            3 => ['dia' => 'Quarta',    'treino' => 'Corrida ritmo médio',     'detalhes' => '35-45 min a ritmo constante (podes falar frases curtas)'],
            4 => ['dia' => 'Quinta',    'treino' => 'Descanso ou crosstraining','detalhes' => 'Bicicleta, natação ou musculação leve'],
            5 => ['dia' => 'Sexta',     'treino' => 'Corrida leve',            'detalhes' => '30 min recuperação'],
            6 => ['dia' => 'Sábado',    'treino' => 'Corrida longa',           'detalhes' => '50-70 min ritmo muito confortável'],
            0 => ['dia' => 'Domingo',   'treino' => 'DESCANSO TOTAL',          'detalhes' => 'Recupera bem!']
        ]
    ],
    'avancado' => [
        'nome' => 'Plano Avançado – 5-6x por semana',
        'descricao' => 'Para corredores experientes ou que se preparam para provas.',
        'semana' => [
            1 => ['dia' => 'Segunda',   'treino' => 'Recuperação ativa',      'detalhes' => '30-40 min muito leve + alongamentos'],
            2 => ['dia' => 'Terça',     'treino' => 'Séries de velocidade',   'detalhes' => 'Aquec. 15 min + 8-10x 400m rápido (90s recuperação) + Desaq. 10 min'],
            3 => ['dia' => 'Quarta',    'treino' => 'Ritmo médio',            'detalhes' => '50 min incluindo 20 min a ritmo de prova 10K'],
            4 => ['dia' => 'Quinta',    'treino' => 'Tiro curto / Técnica',   'detalhes' => '40 min com 10x 100m sprint + exercícios de passada'],
            5 => ['dia' => 'Sexta',     'treino' => 'Descanso ou leve',       'detalhes' => '30 min trote leve'],
            6 => ['dia' => 'Sábado',    'treino' => 'Longo',                  'detalhes' => '90-120 min ritmo muito baixo (70-75% esforço)'],
            0 => ['dia' => 'Domingo',   'treino' => 'Descanso ativo',         'detalhes' => 'Caminhada, yoga ou off total']
        ]
    ]
];

$plano_atual = $planos[$nivel];
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plano de Corrida | ATHLYTIC</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="icon" href="IMGS/Athlytic_logo_sem_texto.jpg" type="image/jpeg">
    <link rel="apple-touch-icon" href="IMGS/Athlytic_logo_sem_texto.jpg">

    <style>
        body { background-color: #f5f7fa; }
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #111827;
            color: white;
            position: fixed;
            padding: 20px 0;
        }
        .sidebar h2 { font-size: 24px; text-align: center; margin-bottom: 40px; font-weight: bold; }
        .sidebar a {
            display: block;
            padding: 14px 25px;
            color: #d1d5db;
            text-decoration: none;
            font-size: 16px;
        }
        .sidebar a:hover, .sidebar a.active {
            background: #1f2937;
            color: white;
        }
        .content { margin-left: 250px; padding: 40px; }
        .dia-card {
            transition: all 0.3s;
            border-left: 5px solid #ddd;
        }
        .dia-card.hoje {
            border-left-color: #28a745;
            background: #f8fff9;
            transform: scale(1.02);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .dia-card h4 { margin: 0; color: #1f2937; }
        .treino-titulo { font-size: 1.4rem; font-weight: bold; color: #dc3545; }
    </style>
</head>
<body>

    <!-- Sidebar -->
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

    <!-- Conteúdo principal -->
    <div class="content">
        <h1 class="mb-4"><i class="bi bi-stopwatch text-danger"></i> Plano de Corrida</h1>
        
        <div class="alert alert-success">
            <strong>Hoje é <?php echo $hoje_nome; ?>, <?php echo date('d \d\e F \d\e Y'); ?></strong>
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h3 class="card-title"><?php echo $plano_atual['nome']; ?></h3>
                <p class="text-muted"><?php echo $plano_atual['descricao']; ?></p>
            </div>
        </div>

        <div class="row">
            <?php 
            $ordem = [1,2,3,4,5,6,0]; // Segunda até Domingo
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
                            <div class="treino-titulo mt-2"><?php echo $dia['treino']; ?></div>
                            <p class="mt-3 mb-0"><?php echo $dia['detalhes']; ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-5 text-center">
            <button class="btn btn-lg btn-outline-primary" onclick="alert('Funcionalidade em desenvolvimento!')">
                <i class="bi bi-check2-circle"></i> Marcar treino de hoje como concluído
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>