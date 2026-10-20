<?php
// Router do servidor embutido do PHP, usado só quando o site é publicado pelo túnel
// (publicar.bat). Deixa passar apenas as páginas do site e os ficheiros estáticos;
// tudo o resto (config.php, includes/, database/, instalar.php, esta pasta...) dá 404.

$caminho = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

$paginas = [
    'index.php', 'registo.php', 'logout.php', 'dashboard.php', 'pesagem.php',
    'inserir_atividade.php', 'editar_atividade.php', 'concluir.php', 'plano.php',
    'historico.php', 'definicoes.php', 'exportar.php', 'tema.php', 'meus_planos.php', 'admin.php', 'admin_utilizador.php',
];

if ($caminho === '/') {
    return false; // index.php
}
if (preg_match('#^/([a-z_]+\.php)$#', $caminho, $m) && in_array($m[1], $paginas, true)) {
    return false;
}
if (strpos($caminho, '..') === false
    && preg_match('#^/(assets|IMGS|uploads)/[A-Za-z0-9_\-/.]+\.(css|js|png|jpe?g|gif|webp|svg|ico|fit)$#', $caminho)) {
    return false;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Página não encontrada.';
