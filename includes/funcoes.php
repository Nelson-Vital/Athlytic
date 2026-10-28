<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------------
// Utilitários gerais
// ---------------------------------------------------------------

/** Escapa texto para HTML (evita XSS). */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function redirecionar(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Mensagem que aparece uma vez na página seguinte. */
function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function mostrar_flash(): void
{
    foreach ($_SESSION['flash'] ?? [] as $f) {
        echo '<div class="alert alert-' . e($f['tipo']) . ' alert-dismissible fade show" role="alert">'
            . e($f['mensagem'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>';
    }
    unset($_SESSION['flash']);
}

// ---------------------------------------------------------------
// Proteção CSRF para todos os formulários POST
// ---------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verificar(): void
{
    if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Pedido inválido. Volta atrás e tenta novamente.');
    }
}

// ---------------------------------------------------------------
// Autenticação
// ---------------------------------------------------------------

function requer_login(): void
{
    if (empty($_SESSION['utilizador_id'])) {
        redirecionar('index.php');
    }
}

/** Dados do utilizador com sessão iniciada. */
function utilizador_atual(): ?array
{
    static $u = null;
    if ($u === null && !empty($_SESSION['utilizador_id'])) {
        $stmt = db()->prepare('SELECT * FROM utilizadores WHERE id = ?');
        $stmt->execute([$_SESSION['utilizador_id']]);
        $u = $stmt->fetch() ?: null;
        if ($u === null || !$u['ativo']) {
            // A conta foi apagada ou inativada por um administrador: termina a sessão
            $inativa = $u !== null;
            $_SESSION = [];
            session_destroy();
            session_start();
            if ($inativa) {
                flash('warning', MENSAGEM_CONTA_INATIVA);
            }
            redirecionar('index.php');
        }
    }
    return $u;
}

// ---------------------------------------------------------------
// Administração
// ---------------------------------------------------------------

const MENSAGEM_CONTA_INATIVA = 'A tua conta está inativa. Contacta um dos administradores para a voltar a ativar.';
const MOTIVO_MIN = 10;   // o motivo de uma inativação tem de ter pelo menos 10 caracteres

function e_admin(?array $u): bool
{
    return $u !== null && $u['papel'] === 'admin' && $u['ativo'];
}

/** Só administradores ativos podem ver a página; os outros voltam ao início. */
function requer_admin(): array
{
    requer_login();
    $u = utilizador_atual();
    if (!e_admin($u)) {
        flash('danger', 'Esta área é só para administradores.');
        redirecionar('dashboard.php');
    }
    return $u;
}

/** Número de administradores com a conta ativa. */
function total_admins_ativos(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM utilizadores WHERE papel = 'admin' AND ativo = 1")->fetchColumn();
}

/**
 * Regra: tem de existir sempre pelo menos um administrador ativo.
 * Devolve true se o utilizador $alvo deixar de ser admin ativo e com isso não sobrar nenhum.
 */
function deixa_sem_admins(array $alvo): bool
{
    return $alvo['papel'] === 'admin' && $alvo['ativo'] && total_admins_ativos() <= 1;
}

/** Nome e email dos administradores ativos (para quem tem a conta inativa os poder contactar). */
function contactos_admins(): array
{
    return db()->query("SELECT nome, email FROM utilizadores WHERE papel = 'admin' AND ativo = 1 ORDER BY nome")->fetchAll();
}

/** Guarda no histórico uma ação de um administrador sobre uma conta. */
function registar_estado_conta(int $utilizadorId, ?int $adminId, string $acao, ?string $motivo): void
{
    db()->prepare('INSERT INTO estado_contas (utilizador_id, admin_id, acao, motivo) VALUES (?, ?, ?, ?)')
        ->execute([$utilizadorId, $adminId, $acao, $motivo]);
}

// ---------------------------------------------------------------
// Datas em português
// ---------------------------------------------------------------

const DIAS_SEMANA = [1 => 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado', 'Domingo'];
const MESES = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
const MESES_CURTOS = [1 => 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

/** Ex.: "Quinta-feira, 15 de Outubro de 2026" */
function data_extenso(?string $data = null): string
{
    $t = $data ? strtotime($data) : time();
    return DIAS_SEMANA[(int) date('N', $t)] . ', ' . date('j', $t) . ' de ' . MESES[(int) date('n', $t)] . ' de ' . date('Y', $t);
}

/** Iniciais do nome para o avatar: "Nelson Vital" -> "NV". */
function iniciais(string $nome): string
{
    $partes = preg_split('/\s+/u', trim($nome), -1, PREG_SPLIT_NO_EMPTY);
    if (!$partes) {
        return '?';
    }
    $primeira = mb_substr($partes[0], 0, 1);
    $ultima = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';
    return mb_strtoupper($primeira . $ultima);
}

/**
 * Cabeçalho de uma página interna: título (e subtítulo) à esquerda, ações à direita.
 * No telemóvel mostra também o avatar, que abre as Configurações.
 * $acoes é HTML (botões ou segmentados); $antes é uma linha pequena por cima do título (ex.: a data).
 */
function topo_pagina(string $titulo, string $sub = '', string $acoes = '', string $antes = ''): void
{
    $u = utilizador_atual();
    echo '<header class="pagina-topo"><div class="pagina-titulo">';
    if ($antes !== '') {
        echo '<p class="data">' . e($antes) . '</p>';
    }
    echo '<h1>' . e($titulo) . '</h1>';
    if ($sub !== '') {
        echo '<p class="sub d-none d-md-block">' . e($sub) . '</p>';
    }
    echo '</div>';
    echo '<a href="definicoes.php" class="avatar avatar-perfil d-md-none" aria-label="Configurações">'
        . e(iniciais($u['nome'])) . '<i class="bi bi-gear-fill" aria-hidden="true"></i></a>';
    if ($acoes !== '') {
        echo '<div class="pagina-acoes">' . $acoes . '</div>';
    }
    echo '</header>';
}

/** Ex.: "18 Nov" */
function data_curta(string $data): string
{
    $t = strtotime($data);
    return date('j', $t) . ' ' . MESES_CURTOS[(int) date('n', $t)];
}

/** Segunda-feira e domingo da semana atual (Y-m-d). */
function semana_atual(): array
{
    return [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))];
}

function formatar_duracao(int $minutos): string
{
    $h = intdiv($minutos, 60);
    $m = $minutos % 60;
    return $h > 0 ? "{$h}h " . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . 'min' : "{$m} min";
}

/** Duração de um treino em segundos: 2182 -> "36:22", 3930 -> "1:05:30". */
function formatar_tempo(int $segundos): string
{
    $h = intdiv($segundos, 3600);
    $m = intdiv($segundos % 3600, 60);
    $s = $segundos % 60;
    return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
}

/** Separa segundos nos campos do formulário (horas, minutos e segundos). */
function campos_duracao(int $segundos): array
{
    return [
        'dur_h'   => $segundos >= 3600 ? (string) intdiv($segundos, 3600) : '',
        'dur_min' => (string) intdiv($segundos % 3600, 60),
        'dur_seg' => sprintf('%02d', $segundos % 60),
    ];
}

/** Formata um número à portuguesa sem zeros desnecessários: 72.0 -> "72", 8.20 -> "8,2". */
function numero($valor, int $casas = 1): string
{
    $texto = number_format((float) $valor, $casas, ',', ' ');
    return $casas > 0 ? rtrim(rtrim($texto, '0'), ',') : $texto;
}

// ---------------------------------------------------------------
// Atividades e planos
// ---------------------------------------------------------------

// Tipos que se podem registar: [nome, ícone, cor]
// (os ícones ficam todos na cor do texto; as cores por modalidade são só para os gráficos)
const TIPOS_ATIVIDADE = [
    'corrida'    => ['Corrida',    'bi-run',     'icone-tipo'],
    'ciclismo'   => ['Ciclismo',   'bi-bicycle', 'icone-tipo'],
    'natacao'    => ['Natação',    'bi-water',   'icone-tipo'],
    'musculacao' => ['Musculação', 'bi-barbell', 'icone-tipo'],
];

// Tipos que já não se podem escolher, mas que podem existir em atividades antigas
const TIPOS_ANTIGOS = [
    'caminhada' => ['Caminhada',        'bi-person-walking',   'icone-tipo'],
    'funcional' => ['Treino Funcional', 'bi-lightning-charge', 'icone-tipo'],
    'outro'     => ['Outro',            'bi-activity',         'icone-tipo'],
];

/** [nome, ícone, cor] de um tipo de atividade (inclui os tipos antigos). */
function tipo_info(string $tipo): array
{
    return TIPOS_ATIVIDADE[$tipo] ?? TIPOS_ANTIGOS[$tipo] ?? ['Outro', 'bi-activity', 'icone-tipo'];
}

/** Ritmo ou velocidade de um treino (null se não houver distância): min/km, min/100 m ou km/h. */
function ritmo(string $tipo, float $minutos, float $km): ?float
{
    if ($km <= 0 || $minutos <= 0) {
        return null;
    }
    switch ($tipo) {
        case 'corrida':  return round($minutos / $km, 2);          // min/km
        case 'natacao':  return round($minutos / ($km * 10), 2);   // min/100 m
        default:         return round($km / ($minutos / 60), 1);   // km/h
    }
}

/** 5.5 -> "5:30" (minutos decimais em mm:ss) */
function mm_ss(?float $minutos): string
{
    if ($minutos === null) {
        return '—';
    }
    $seg = (int) round($minutos * 60);
    return sprintf('%d:%02d', intdiv($seg, 60), $seg % 60);
}

/** Ritmo de uma atividade pronto a mostrar ("5:00/km", "2:40/100 m", "9,2 km/h") ou null. */
function texto_ritmo(string $tipo, int $segundos, $km): ?string
{
    if (!in_array($tipo, ['corrida', 'natacao', 'ciclismo'], true)) {
        return null;
    }
    $r = ritmo($tipo, $segundos / 60, (float) $km);
    if ($r === null) {
        return null;
    }
    return $tipo === 'ciclismo' ? numero($r) . ' km/h' : mm_ss($r) . ($tipo === 'corrida' ? '/km' : '/100 m');
}

/** Distância pronta a mostrar: a natação em metros, o resto em km. */
function texto_distancia(string $tipo, $km): string
{
    if ($km === null || $km === '') {
        return '';
    }
    return $tipo === 'natacao' ? numero((float) $km * 1000, 0) . ' m' : numero($km, 2) . ' km';
}

const INTENSIDADES = ['leve' => 'Leve', 'moderada' => 'Moderada', 'alta' => 'Alta'];

const SEXOS = ['M' => 'Masculino', 'F' => 'Feminino'];

const OBJETIVOS = [
    'perda_peso'   => 'Perda de Peso',
    'recomposicao' => 'Recomposição Corporal',
    'ganho_massa'  => 'Ganho de Massa',
    'manutencao'   => 'Manutenção',
];

/** Totais de atividades do utilizador na semana atual. */
function estatisticas_semana(int $utilizadorId): array
{
    [$inicio, $fim] = semana_atual();
    $stmt = db()->prepare(
        "SELECT COUNT(*) AS atividades,
                COALESCE(SUM(duracao_seg), 0) DIV 60 AS minutos,
                COALESCE(SUM(distancia_km), 0) AS km,
                COALESCE(SUM(CASE WHEN tipo = 'corrida' THEN distancia_km END), 0) AS km_corrida
         FROM atividades
         WHERE utilizador_id = ? AND data BETWEEN ? AND ?"
    );
    $stmt->execute([$utilizadorId, $inicio, $fim]);
    return $stmt->fetch();
}

/** Dia do plano para um dia da semana (1 = Segunda). */
function dia_do_plano(?int $planoId, int $diaSemana): ?array
{
    if (!$planoId) {
        return null;
    }
    $stmt = db()->prepare(
        'SELECT d.*, p.nome AS plano_nome FROM plano_dias d JOIN planos p ON p.id = d.plano_id
         WHERE d.plano_id = ? AND d.dia_semana = ?'
    );
    $stmt->execute([$planoId, $diaSemana]);
    return $stmt->fetch() ?: null;
}

/** Converte o conteúdo do plano (uma linha por item) numa lista HTML. */
function lista_conteudo(string $conteudo): string
{
    $html = '<ul class="mb-0">';
    foreach (preg_split('/\R/', trim($conteudo)) as $linha) {
        $linha = trim($linha);
        if ($linha === '') {
            continue;
        }
        // "Almoço: 180 g frango" -> "<strong>Almoço:</strong> 180 g frango"
        if (preg_match('/^([^:]{2,25}):\s*(.+)$/u', $linha, $m)) {
            $html .= '<li><strong>' . e($m[1]) . ':</strong> ' . e($m[2]) . '</li>';
        } else {
            $html .= '<li>' . e($linha) . '</li>';
        }
    }
    return $html . '</ul>';
}

// ---------------------------------------------------------------
// Pesagens
// ---------------------------------------------------------------

/**
 * Guarda (ou substitui) a pesagem de um dia e atualiza o peso atual do utilizador
 * com a pesagem mais recente.
 */
function registar_pesagem(int $utilizadorId, string $data, float $peso, ?float $massaGorda): void
{
    $stmt = db()->prepare(
        'INSERT INTO pesagens (utilizador_id, data, peso, massa_gorda) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE peso = VALUES(peso), massa_gorda = VALUES(massa_gorda)'
    );
    $stmt->execute([$utilizadorId, $data, $peso, $massaGorda]);
    sincronizar_peso_atual($utilizadorId);
}

/** O peso atual (e % de massa gorda) é sempre o da pesagem mais recente. */
function sincronizar_peso_atual(int $utilizadorId): void
{
    $stmt = db()->prepare('SELECT peso, massa_gorda FROM pesagens WHERE utilizador_id = ? ORDER BY data DESC LIMIT 1');
    $stmt->execute([$utilizadorId]);
    $ultima = $stmt->fetch();
    if ($ultima) {
        db()->prepare('UPDATE utilizadores SET peso_atual = ?, massa_gorda = COALESCE(?, massa_gorda) WHERE id = ?')
            ->execute([$ultima['peso'], $ultima['massa_gorda'], $utilizadorId]);
    }
}

// ---------------------------------------------------------------
// Atividades: validação, uploads e gravação (usado ao inserir, editar e concluir)
// ---------------------------------------------------------------

// ---------------------------------------------------------------
// Planos pessoais (criados pelo utilizador para um dia concreto)
// ---------------------------------------------------------------

const DIAS_PLANEAR = 7;   // só se pode planear até 7 dias à frente
const DIAS_GUARDAR = 7;   // um plano é apagado quando faz 7 dias (7 dias depois do seu dia)

const CATEGORIAS_PLANO = [
    'treino'      => ['Treino', 'bi-lightning-charge-fill'],
    'alimentacao' => ['Alimentação', 'bi-egg-fried'],
];

// Refeição de um plano pessoal no Plano Alimentar
const REFEICOES = [
    'pequeno_almoco' => 'Pequeno-almoço',
    'almoco'         => 'Almoço',
    'lanche'         => 'Lanche',
    'jantar'         => 'Jantar',
    'dia'            => 'Dia inteiro',
];

// Planos de treino que existem por modalidade (coluna do utilizador com o plano escolhido)
const PLANOS_TREINO = [
    'corrida'    => 'plano_corrida_id',
    'musculacao' => 'plano_musculacao_id',
];

/**
 * Duração aproximada (minutos) de um dia de plano, somando os minutos do texto:
 * "Aquecimento 10 min / 6× (1 min / 2 min) / 10 min" -> 38. Sem minutos no texto: null.
 */
function duracao_estimada(string $conteudo): ?int
{
    $total = 0;
    foreach (preg_split('/\R/', $conteudo) as $linha) {
        $linha = preg_replace('/a cada \d+\s*min/u', '', $linha);
        if (preg_match('/(\d+)\s*[×x]\s*\((\d+)\s*min[^\/]*\/\s*(\d+)\s*min/u', $linha, $m)) {
            $total += (int) $m[1] * ((int) $m[2] + (int) $m[3]);
            continue;
        }
        // "35–40 min" conta como 35
        preg_match_all('/(\d+)(?:\s*[–-]\s*\d+)?\s*min/u', $linha, $mm);
        $total += array_sum(array_map('intval', $mm[1]));
    }
    return $total > 0 ? $total : null;
}

/**
 * Treino de hoje de uma modalidade (para "Registar este treino").
 * Um plano pessoal de hoje que substitui o plano (ou numa modalidade sem plano) tem prioridade.
 * Devolve null (sem treino ou dia de descanso) ou:
 * ['titulo', 'conteudo', 'tipo', 'duracao_min', 'plano_id' (do plano) ou 'pessoal_id' (plano pessoal), 'feito'].
 */
function treino_de_hoje(array $u, string $modalidade): ?array
{
    if (!isset(TIPOS_ATIVIDADE[$modalidade])) {
        return null;
    }
    $stmt = db()->prepare(
        "SELECT * FROM planos_pessoais WHERE utilizador_id = ? AND categoria = 'treino' AND modalidade = ? AND data = CURDATE()
         ORDER BY substitui_plano DESC, id LIMIT 1"
    );
    $stmt->execute([$u['id'], $modalidade]);
    $pessoal = $stmt->fetch() ?: null;

    $dia = null;
    $planoId = isset(PLANOS_TREINO[$modalidade]) ? (int) $u[PLANOS_TREINO[$modalidade]] : 0;
    if ($planoId) {
        $dia = dia_do_plano($planoId, (int) date('N'));
        if ($dia && $dia['descanso']) {
            $dia = null;
        }
    }

    if ($pessoal && ($pessoal['substitui_plano'] || !$dia)) {
        return [
            'titulo'      => $pessoal['titulo'],
            'conteudo'    => (string) $pessoal['descricao'],
            'tipo'        => $modalidade,
            'duracao_min' => duracao_estimada((string) $pessoal['descricao']),
            'pessoal_id'  => (int) $pessoal['id'],
            'feito'       => (bool) $pessoal['concluido'],
        ];
    }
    if (!$dia) {
        return null;
    }
    $stmt = db()->prepare('SELECT atividade_id FROM plano_concluidos WHERE utilizador_id = ? AND plano_id = ? AND data = CURDATE()');
    $stmt->execute([$u['id'], $planoId]);
    return [
        'titulo'      => $dia['titulo'],
        'conteudo'    => $dia['conteudo'],
        'tipo'        => $modalidade,
        'duracao_min' => duracao_estimada($dia['conteudo']),
        'plano_id'    => $planoId,
        'plano_nome'  => $dia['plano_nome'],
        'feito'       => $stmt->fetch() !== false,
    ];
}

/** Apaga os planos pessoais (de todos os utilizadores) que já têm DIAS_GUARDAR dias de idade. */
function limpar_planos_antigos(): void
{
    db()->prepare('DELETE FROM planos_pessoais WHERE data <= CURDATE() - INTERVAL ? DAY')->execute([DIAS_GUARDAR]);
}

/** Ex.: "Hoje", "Amanhã", "Sábado, 17 Out" */
function nome_dia(string $data): string
{
    $dif = (int) round((strtotime($data) - strtotime(date('Y-m-d'))) / 86400);
    $nomes = [0 => 'Hoje', 1 => 'Amanhã', -1 => 'Ontem'];
    return $nomes[$dif] ?? DIAS_SEMANA[(int) date('N', strtotime($data))] . ', ' . data_curta($data);
}

/** Campos do formulário de atividade, com os valores por omissão. */
function atividade_vazia(array $u): array
{
    return [
        'tipo'         => '',
        'intensidade'  => $u['intensidade_padrao'],
        'data'         => date('Y-m-d'),
        'hora'         => date('H:i'),
        'dur_h'        => '',
        'dur_min'      => '',
        'dur_seg'      => '',
        'distancia_km' => '',
        'notas'        => '',
    ];
}

/** Lê e valida os campos enviados. Devolve os dados limpos; os erros vão para $erros. */
function validar_atividade(array $post, array &$erros): array
{
    $d = [];
    foreach (['tipo', 'intensidade', 'data', 'hora', 'dur_h', 'dur_min', 'dur_seg', 'distancia_km', 'notas'] as $campo) {
        $d[$campo] = trim((string) ($post[$campo] ?? ''));
    }
    $d['hora'] = substr($d['hora'], 0, 5);

    if (!array_key_exists($d['tipo'], TIPOS_ATIVIDADE)) {
        $erros[] = 'Escolhe o tipo de atividade.';
    }
    if (!array_key_exists($d['intensidade'], INTENSIDADES)) {
        $erros[] = 'Escolhe a intensidade.';
    }
    $dataObj = DateTime::createFromFormat('Y-m-d', $d['data']);
    if (!$dataObj || $dataObj->format('Y-m-d') !== $d['data']) {
        $erros[] = 'A data não é válida.';
    } elseif ($d['data'] > date('Y-m-d')) {
        $erros[] = 'Não podes registar atividades no futuro.';
    }
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $d['hora'])) {
        $erros[] = 'A hora não é válida.';
    }
    // Duração: horas + minutos + segundos (campos vazios contam como 0)
    $d['duracao_seg'] = 0;
    $valido = fn($v, $max) => $v === '' || (ctype_digit($v) && $v <= $max);
    if (!$valido($d['dur_h'], 24) || !$valido($d['dur_min'], 1440) || !$valido($d['dur_seg'], 59)) {
        $erros[] = 'A duração não é válida (horas, minutos e segundos; segundos de 0 a 59).';
    } else {
        $d['duracao_seg'] = (int) $d['dur_h'] * 3600 + (int) $d['dur_min'] * 60 + (int) $d['dur_seg'];
        if ($d['duracao_seg'] < 1 || $d['duracao_seg'] > 86400) {
            $erros[] = 'A duração tem de ser entre 1 segundo e 24 horas.';
        }
    }
    // Formulário novo: campo "distancia", em metros na natação e em km nos outros tipos
    if (isset($post['distancia'])) {
        $valor = str_replace(',', '.', trim((string) $post['distancia']));
        $d['distancia_km'] = ($d['tipo'] === 'natacao' && is_numeric($valor)) ? (string) round($valor / 1000, 2) : $valor;
    }
    $d['distancia_km'] = str_replace(',', '.', $d['distancia_km']);
    if ($d['distancia_km'] !== '' && (!is_numeric($d['distancia_km']) || $d['distancia_km'] < 0 || $d['distancia_km'] > 1000)) {
        $erros[] = 'A distância não é válida.';
    }
    return $d;
}

/** Valores prontos para gravar na tabela atividades. */
function valores_atividade(array $d): array
{
    return [
        $d['tipo'], $d['intensidade'], $d['data'], $d['hora'], $d['duracao_seg'],
        $d['distancia_km'] === '' ? null : $d['distancia_km'],
        $d['notas'] === '' ? null : $d['notas'],
    ];
}

/**
 * Guarda um ficheiro enviado na pasta uploads com nome aleatório.
 * Devolve o caminho relativo ou null se não foi enviado nenhum ficheiro.
 */
function guardar_upload(array $f, array $extensoes, int $maxBytes, array &$erros): ?string
{
    if ($f['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $erros[] = 'Erro ao enviar "' . $f['name'] . '".';
        return null;
    }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $extensoes, true)) {
        $erros[] = '"' . $f['name'] . '" não é um tipo de ficheiro permitido (' . implode(', ', $extensoes) . ').';
        return null;
    }
    if ($f['size'] > $maxBytes) {
        $erros[] = '"' . $f['name'] . '" é demasiado grande (máx. ' . round($maxBytes / 1048576) . ' MB).';
        return null;
    }
    // As imagens têm de ser mesmo imagens
    if ($ext !== 'fit' && @getimagesize($f['tmp_name']) === false) {
        $erros[] = '"' . $f['name'] . '" não é uma imagem válida.';
        return null;
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $nome = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $nome)) {
        $erros[] = 'Não foi possível guardar "' . $f['name'] . '".';
        return null;
    }
    return UPLOAD_URL . '/' . $nome;
}

/** Guarda o .FIT (só corridas) e as fotos enviadas no formulário de uma atividade. */
function guardar_ficheiros_atividade(int $atividadeId, string $tipo, array &$erros): void
{
    if ($tipo === 'corrida' && isset($_FILES['fit'])) {
        if ($fit = guardar_upload($_FILES['fit'], ['fit'], 20 * 1048576, $erros)) {
            // Substitui o ficheiro anterior, se existir
            $stmt = db()->prepare('SELECT ficheiro_fit FROM atividades WHERE id = ?');
            $stmt->execute([$atividadeId]);
            apagar_ficheiros([$stmt->fetchColumn()]);
            db()->prepare('UPDATE atividades SET ficheiro_fit = ? WHERE id = ?')->execute([$fit, $atividadeId]);
        }
    }
    if (!empty($_FILES['fotos']['name'][0])) {
        $stmt = db()->prepare('INSERT INTO atividade_fotos (atividade_id, caminho) VALUES (?, ?)');
        foreach ($_FILES['fotos']['name'] as $i => $nome) {
            $f = [
                'name'     => $nome,
                'tmp_name' => $_FILES['fotos']['tmp_name'][$i],
                'error'    => $_FILES['fotos']['error'][$i],
                'size'     => $_FILES['fotos']['size'][$i],
            ];
            if ($caminho = guardar_upload($f, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5 * 1048576, $erros)) {
                $stmt->execute([$atividadeId, $caminho]);
            }
        }
    }
}

/** Apaga do disco ficheiros enviados (fotos / .FIT). */
function apagar_ficheiros(array $caminhos): void
{
    foreach ($caminhos as $c) {
        if ($c && strpos($c, UPLOAD_URL . '/') === 0) {
            @unlink(UPLOAD_DIR . '/' . basename($c));
        }
    }
}

/**
 * Ficheiros (fotos e .FIT) das atividades de um utilizador.
 * Se $atividadeId for indicado, só dessa atividade.
 */
function ficheiros_de_atividades(int $utilizadorId, ?int $atividadeId = null): array
{
    $filtro = $atividadeId ? ' AND a.id = ' . (int) $atividadeId : '';
    $stmt = db()->prepare(
        "SELECT f.caminho FROM atividade_fotos f JOIN atividades a ON a.id = f.atividade_id WHERE a.utilizador_id = ? $filtro
         UNION SELECT a.ficheiro_fit FROM atividades a WHERE a.utilizador_id = ? AND a.ficheiro_fit IS NOT NULL $filtro"
    );
    $stmt->execute([$utilizadorId, $utilizadorId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ---------------------------------------------------------------
// Tema (claro / escuro)
// ---------------------------------------------------------------

/**
 * Guarda o tema também num cookie, para o ecrã de login (sem sessão iniciada)
 * aparecer com o último tema usado.
 */
function guardar_tema_cookie(string $tema): void
{
    setcookie('tema', $tema === 'escuro' ? 'escuro' : 'claro', [
        'expires'  => time() + 365 * 24 * 3600,
        'path'     => '/',
        'samesite' => 'Lax',
    ]);
}
