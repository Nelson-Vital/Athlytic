<?php
date_default_timezone_set('Europe/Lisbon');
$hoje_num = date('w'); // 0=Domingo ... 6=Sábado
$hoje_nome = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'][$hoje_num];

// Escolhe o nível aqui (iniciante | intermediario | avancado)
$nivel = 'intermediario';

$planos = [
    'iniciante' => [
        'nome' => 'Musculação Iniciante – Full Body 3x/semana',
        'descricao' => 'Perfeito para quem está a começar. Treino de corpo inteiro em dias alternados.',
        'semana' => [
            1 => ['dia' => 'Segunda',     'treino' => 'Full Body A',    'exercicios' => '<ul><li>Agachamento livre 3×10-12</li><li>Supino reto barra 3×10-12</li><li>Remada curvada 3×10-12</li><li>Desenvolvimento ombros halteres 3×12</li><li>Panturrilha em pé 3×15-20</li></ul>'],
            2 => ['dia' => 'Terça',       'treino' => 'Descanso',       'exercicios' => '<p>Recuperação ou caminhada leve</p>'],
            3 => ['dia' => 'Quarta',      'treino' => 'Full Body B',    'exercicios' => '<ul><li>Leg press / Afundo 3×12</li><li>Supino inclinado halteres 3×10-12</li><li>Pull-down / Barra fixa assistida 3×10-12</li><li>Elevação lateral 3×12-15</li><li>Abdominais prancha 3×30-45s</li></ul>'],
            4 => ['dia' => 'Quinta',      'treino' => 'Descanso',       'exercicios' => '<p>Recuperação</p>'],
            5 => ['dia' => 'Sexta',       'treino' => 'Full Body A',    'exercicios' => '<ul><li>Agachamento livre 3×10-12</li><li>Supino reto barra 3×10-12</li><li>Remada curvada 3×10-12</li><li>Desenvolvimento ombros 3×12</li><li>Panturrilha 3×15-20</li></ul>'],
            6 => ['dia' => 'Sábado',      'treino' => 'Descanso ativo', 'exercicios' => '<p>Caminhada, mobilidade ou yoga</p>'],
            0 => ['dia' => 'Domingo',     'treino' => 'DESCANSO TOTAL','exercicios' => '<p>Recupera e prepara a próxima semana!</p>']
        ]
    ],

    'intermediario' => [
        'nome' => 'Musculação Intermédio – Push / Pull / Legs (5-6x/semana)',
        'descricao' => 'Split clássico muito eficaz para ganhos de força e hipertrofia.',
        'semana' => [
            1 => ['dia' => 'Segunda',     'treino' => 'Push (Peito, Ombros, Tríceps)', 
                  'exercicios' => '<ul><li>Supino reto barra 4×8-10</li><li>Supino inclinado halteres 3×10-12</li><li>Desenvolvimento militar 4×8-10</li><li>Elevação lateral 3×12-15</li><li>Tríceps pulley 4×12</li><li>Francês 3×10-12</li></ul>'],
            2 => ['dia' => 'Terça',       'treino' => 'Pull (Costas, Bíceps)', 
                  'exercicios' => '<ul><li>Barra fixa / Pulldown 4×8-12</li><li>Remada curvada barra 4×8-10</li><li>Remada unilateral 3×10-12</li><li>Face pull 3×15</li><li>Rosca direta EZ 4×10-12</li><li>Rosca martelo 3×12</li></ul>'],
            3 => ['dia' => 'Quarta',      'treino' => 'Legs (Pernas)', 
                  'exercicios' => '<ul><li>Agachamento livre 4×8-10</li><li>Leg press 4×10-12</li><li>Passada / Afundo 3×12 cada</li><li>Extensora 3×12-15</li><li>Flexora 3×12-15</li><li>Panturrilha em pé 4×15-20</li></ul>'],
            4 => ['dia' => 'Quinta',      'treino' => 'Push (variação)', 
                  'exercicios' => '<ul><li>Supino declinado 4×8-10</li><li>Crucifixo máquina 3×12-15</li><li>Desenvolvimento Arnold 3×10</li><li>Tríceps testa 4×10-12</li></ul>'],
            5 => ['dia' => 'Sexta',       'treino' => 'Pull (variação)', 
                  'exercicios' => '<ul><li>Remada baixa 4×10</li><li>Pull-over 3×12</li><li>Rosca concentrada 3×12-15</li></ul>'],
            6 => ['dia' => 'Sábado',      'treino' => 'Legs + Core', 
                  'exercicios' => '<ul><li>Stiff / Romanian DL 4×10</li><li>Hack squat 3×12</li><li>Abdominais + lombar</li></ul>'],
            0 => ['dia' => 'Domingo',     'treino' => 'DESCANSO TOTAL', 
                  'exercicios' => '<p>Dia livre – recupera e come bem!</p>']
        ]
    ],

    'avancado' => [
        'nome' => 'Musculação Avançado – Bro Split 6x/semana',
        'descricao' => 'Treino por grupo muscular. Ideal para quem já tem boa recuperação.',
        'semana' => [
            1 => ['dia' => 'Segunda',   'treino' => 'Peito + Tríceps', 'exercicios' => '<ul><li>Supino reto 4×6-8</li><li>Supino inclinado 4×8-10</li><li>Crucifixo 3×12</li><li>Tríceps pulley 4×10-12</li><li>Tríceps testa 4×8-10</li><li>Kickback 3×15</li></ul>'],
            2 => ['dia' => 'Terça',     'treino' => 'Costas + Bíceps', 'exercicios' => '<ul><li>Barra fixa 4×máx</li><li>Remada curvada 4×6-8</li><li>Pulldown 3×10</li><li>Rosca direta 4×8-10</li><li>Rosca scott 3×12</li></ul>'],
            3 => ['dia' => 'Quarta',    'treino' => 'Pernas', 'exercicios' => '<ul><li>Agachamento 5×6-8</li><li>Leg press 4×10-12</li><li>Extensora + Flexora superset 4×12-15</li><li>Panturrilha 5×15-20</li></ul>'],
            4 => ['dia' => 'Quinta',    'treino' => 'Ombros + Trapézio', 'exercicios' => '<ul><li>Desenvolvimento militar 4×6-8</li><li>Elevação lateral 4×12-15</li><li>Elevação frontal + reverso 3×12</li><li>Encolhimento 4×12-15</li></ul>'],
            5 => ['dia' => 'Sexta',     'treino' => 'Braços (Bíceps + Tríceps)', 'exercicios' => '<ul><li>Superseries de bíceps/tríceps 4×10-12 cada</li></ul>'],
            6 => ['dia' => 'Sábado',    'treino' => 'Full Upper ou Fraquezas', 'exercicios' => '<p>Treina grupos que precisem de mais volume</p>'],
            0 => ['dia' => 'Domingo',   'treino' => 'DESCANSO TOTAL', 'exercicios' => '<p>Recuperação total</p>']
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
    <title>Musculação | ATHLYTIC</title>

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
        .dia-card.hoje { border-left-color: #dc3545; background: #fff5f5; transform: scale(1.02); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .treino-titulo { font-size: 1.4rem; font-weight: bold; color: #dc3545; }
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
        <h1 class="mb-4"><i class="bi bi-trophy text-danger"></i> Plano de Musculação</h1>
        
        <div class="alert alert-danger">
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
            $ordem = [1,2,3,4,5,6,0];
            foreach ($ordem as $d): 
                $dia = $plano_atual['semana'][$d];
                $eh_hoje = ($d == $hoje_num) ? 'hoje' : '';
            ?>
                <div class="col-md-6 mb-4">
                    <div class="card dia-card <?php echo $eh_hoje; ?>">
                        <div class="card-body">
                            <?php if ($eh_hoje): ?>
                                <span class="badge bg-danger float-end">HOJE</span>
                            <?php endif; ?>
                            <h4><?php echo $dia['dia']; ?></h4>
                            <div class="treino-titulo mt-2"><?php echo $dia['treino']; ?></div>
                            <div class="mt-3"><?php echo $dia['exercicios']; ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-5 text-center">
            <button class="btn btn-lg btn-danger" onclick="alert('Treino registado com sucesso! 💪')">
                <i class="bi bi-check2-circle"></i> Marcar treino de hoje como concluído
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>