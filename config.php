<?php
// Configuração da ligação à base de dados.
// No PC (XAMPP) usa os valores por omissão: utilizador "root" sem palavra-passe.
// No Railway os valores vêm das variáveis de ambiente do serviço.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'athlytic');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Pasta onde ficam as fotos e ficheiros .FIT enviados
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('UPLOAD_URL', 'uploads');

date_default_timezone_set('Europe/Lisbon');
