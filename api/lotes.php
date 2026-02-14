<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  http_response_code(204);
  exit;
}

$loteamento = trim((string)($_GET["loteamento"] ?? "ACAPULCO"));
if ($loteamento === "" || mb_strlen($loteamento) > 80) {
  http_response_code(400);
  echo json_encode(["error" => "Parâmetro loteamento inválido."], JSON_UNESCAPED_UNICODE);
  exit;
}

$DB_HOST = getenv("DB_HOST") ?: "bd_pg-hist";   // Host interno do EasyPanel
$DB_PORT = getenv("DB_PORT") ?: "5432";
$DB_NAME = getenv("DB_NAME") ?: "bd";
$DB_USER = getenv("DB_USER") ?: "solotes_ro";
$DB_PASS = getenv("DB_PASS") ?: "";            // >>> NUNCA fixe senha aqui

$dsn = "pgsql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};";

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
      productName
    FROM estoque_lotes_uniao
    WHERE productName ILIKE '%' || :loteamento || '%'
    ORDER BY identificador_unidade
  ";

  $st = $pdo->prepare($sql);
  $st->execute([":loteamento" => $loteamento]);

  echo json_encode($st->fetchAll(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "error" => "Falha ao consultar banco.",
    "detail" => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
