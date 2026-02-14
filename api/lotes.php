<?php declare(strict_types=1);

/**
 * API de lotes (JSON) - EasyPanel + Postgres
 * URL: /api/lotes.php?loteamento=ACAPULCO
 */

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "OPTIONS") {
  http_response_code(204);
  exit;
}

$loteamento = trim((string)($_GET["loteamento"] ?? "ACAPULCO"));

if ($loteamento === "" || mb_strlen($loteamento) > 120) {
  http_response_code(400);
  echo json_encode(["error" => "Parâmetro loteamento inválido."], JSON_UNESCAPED_UNICODE);
  exit;
}

// ENV (recomendado setar no EasyPanel > Ambiente)
$DB_HOST = getenv("DB_HOST") ?: "bd_pg-hist"; // host interno do Postgres no EasyPanel
$DB_PORT = getenv("DB_PORT") ?: "5432";
$DB_NAME = getenv("DB_NAME") ?: "bd";
$DB_USER = getenv("DB_USER") ?: "solotes_ro";
$DB_PASS = getenv("DB_PASS") ?: "";

// Monta DSN
$dsn = "pgsql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};";

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  /**
   * OBS:
   * - Use ILIKE com %...% pra aceitar:
   *   ACAPULCO, RESIDENCIAL ACAPULCO, etc.
   */
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

  $rows = $st->fetchAll();

  echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "error" => "Falha ao consultar banco.",
    "detail" => $e->getMessage(),
    "hint" => "Confira as ENV no EasyPanel (DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS)."
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
