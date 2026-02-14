<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// Debug opcional: .../api/lotes.php?loteamento=ACAPULCO&debug=1
$debug = (isset($_GET['debug']) && $_GET['debug'] === '1');
if ($debug) {
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
}

$loteamento = isset($_GET['loteamento']) ? trim((string)$_GET['loteamento']) : 'ACAPULCO';
if ($loteamento === '' || strlen($loteamento) > 120) {
  http_response_code(400);
  echo json_encode([
    'ok' => false,
    'error' => 'Parâmetro loteamento inválido.'
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

// >>> NUNCA fixe senha no código. Use variáveis de ambiente no EasyPanel.
$DB_HOST = getenv('DB_HOST') ?: 'bd_pg-hist';
$DB_PORT = getenv('DB_PORT') ?: '5432';
$DB_NAME = getenv('DB_NAME') ?: 'bd';
$DB_USER = getenv('DB_USER') ?: 'solotes_ro';
$DB_PASS = getenv('DB_PASS') ?: '';

if ($DB_PASS === '') {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'DB_PASS não definido. Configure em: EasyPanel > apilotes > Ambiente.'
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

$dsn = "pgsql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME}";

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  $sql = "
    SELECT
      descricao_status,
      identificador_unidade,
      c4_unid,
      quantidade_unidade,
      valor,
      espelho_left,
      espelho_top,
      productName,
      substring(identificador_unidade from 'QUADRA\\s*([0-9]+)') AS quadra,
      substring(identificador_unidade from 'LOTE\\s*([0-9]+)')  AS lote
    FROM estoque_lotes_uniao
    WHERE productName ILIKE '%' || :loteamento || '%'
    ORDER BY productName, identificador_unidade
  ";

  $st = $pdo->prepare($sql);
  $st->execute([':loteamento' => $loteamento]);
  $rows = $st->fetchAll();

  echo json_encode([
    'ok' => true,
    'loteamento' => $loteamento,
    'count' => count($rows),
    'rows' => $rows
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'Falha ao consultar banco.',
    'detail' => $debug ? $e->getMessage() : 'Ative debug=1 para ver detalhes.'
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
