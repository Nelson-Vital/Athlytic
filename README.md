# ATHLYTIC (versão dinâmica: PHP + MySQL)

## Como pôr a funcionar (XAMPP)
1. Copia a pasta `athleticz-php` para `C:\xampp\htdocs\` (no Mac: `/Applications/XAMPP/htdocs/`).
2. No painel do XAMPP liga o **Apache** e o **MySQL**.
3. Não é preciso importar nada: na primeira visita o site cria sozinho a base de dados `athlytic`
   (tabelas, 6 planos e um utilizador de teste). Quando a estrutura muda, o site aplica as atualizações
   automaticamente, sem apagar dados (ver `includes/migracoes.php`). `instalar.php` mostra o estado da base de dados.
4. Abre `http://localhost/athleticz-php/`.
5. Entra com **demo@athlytic.pt** / **demo1234**, ou cria uma conta nova em "Regista-te".

Se o teu MySQL tiver palavra-passe ou outro utilizador, altera `config.php`.

## Versões
| Versão | O que é |
|---|---|
| v1.0 | Primeira versão em PHP + Bootstrap 5, sem base de dados (dados em arrays) |
| v2.0 | Versão estática (HTML + CSS + JavaScript), publicada no Netlify |
| v3.0 | Aplicação dinâmica em PHP + MySQL: contas, planos, atividades, histórico com gráficos, pesagens, modo escuro e administração. Publicada no Railway |

Cada versão tem uma tag no GitHub (separador "Tags" / "Releases").

## Publicação no Railway
- O `Dockerfile` cria uma imagem com PHP 8.3 e a extensão `pdo_mysql` e arranca o servidor do PHP com `publicar/router.php`, que só deixa passar as páginas do site e os ficheiros estáticos.
- A base de dados é um serviço MySQL do Railway. O `config.php` lê `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS` das variáveis de ambiente; no XAMPP, sem essas variáveis, usa os valores por omissão (localhost, root, sem palavra-passe).
- As fotos e ficheiros .FIT ficam num volume montado em `/app/uploads`, para não se perderem a cada nova publicação.

## Estrutura
| Ficheiro | Função |
|---|---|
| `config.php` | Dados de ligação à base de dados |
| `includes/db.php` | Ligação PDO |
| `includes/migracoes.php` | Cria e atualiza a base de dados automaticamente (tabela `versao_bd`) |
| `includes/funcoes.php` | Sessão, login, CSRF, datas em português, funções comuns |
| `includes/header.php`, `footer.php` | Menu lateral e layout comum a todas as páginas |
| `includes/form_atividade.php`, `form_concluir.php`, `tema_botao.php` | Formulários reutilizados em várias páginas |
| `assets/js/` | Modo claro/escuro, gráficos (Chart.js), campo .FIT |
| `index.php`, `registo.php`, `logout.php` | Autenticação |
| `dashboard.php` | Resumo: objetivos, treinos e km da semana, treino de hoje, gráfico e registo do peso |
| `pesagem.php` | Regista ou apaga uma pesagem |
| `inserir_atividade.php` | Registar atividade (com .FIT e fotos), histórico, apagar |
| `historico.php` | Histórico: gráficos semanais de corrida, natação e ciclismo e lista de todos os treinos com filtro |
| `editar_atividade.php?id=` | Editar uma atividade (dados, fotos, .FIT) |
| `tema.php` | Alterna modo claro/escuro (botão no menu de todas as páginas) |
| `plano.php?tipo=corrida \| musculacao \| alimentacao` | Planos semanais, escolher plano, marcar dia como concluído |
| `concluir.php` | Grava "dia concluído"; na corrida e musculação regista também a atividade |
| `definicoes.php` | Dados pessoais, metas, notificações, tema, palavra-passe, limpar histórico, apagar conta |
| `exportar.php` | Exporta as atividades em CSV |
| `instalar.php` | Mostra o estado da base de dados e atualiza-a se for preciso (só em localhost) |
| `assets/css/style.css` | CSS comum (inclui modo escuro e menu para telemóvel) |
| `database/athlytic.sql` | Criação da base de dados e dados iniciais |

## Base de dados
- `utilizadores`: conta, dados pessoais, metas, preferências e plano escolhido de cada tipo.
- `planos` e `plano_dias`: cada plano tem 7 dias (1 = Segunda ... 7 = Domingo).
- `atividades` e `atividade_fotos`: treinos registados e respetivas fotos.
- `plano_concluidos`: dias do plano que o utilizador marcou como feitos (ligados à atividade criada).
- `pesagens`: histórico do peso e % de massa gorda (o peso atual é sempre a pesagem mais recente).

## Segurança
- Palavras-passe guardadas com `password_hash` (bcrypt).
- Todas as consultas usam prepared statements (PDO), o que evita SQL injection.
- Todo o texto mostrado passa por `htmlspecialchars`, o que evita XSS.
- Todos os formulários têm token CSRF.
- Uploads: só extensões permitidas, limite de tamanho, nomes aleatórios, e a pasta `uploads` bloqueia a execução de PHP.
