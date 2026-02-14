<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  http_response_code(204);
  exit;
}

function out(array $payload, int $status = 200): void {
  http_response_code($status);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

$loteamento = trim((string)($_GET["loteamento"] ?? "ACAPULCO"));
$status     = trim((string)($_GET["status"] ?? "")); // opcional
$debug      = (string)($_GET["debug"] ?? "") === "1";

if ($loteamento === "" || mb_strlen($loteamento) > 120) {
  out(["ok" => false, "error" => "Parâmetro loteamento inválido."], 400);
}
if ($status !== "" && mb_strlen($status) > 40) {
  out(["ok" => false, "error" => "Parâmetro status inválido."], 400);
}

$DB_HOST = getenv("DB_HOST") ?: "bd_pg-hist";
$DB_PORT = getenv("DB_PORT") ?: "5432";
$DB_NAME = getenv("DB_NAME") ?: "bd";
$DB_USER = getenv("DB_USER") ?: "solotes_ro";
$DB_PASS = getenv("DB_PASS") ?: "";

if ($DB_PASS === "") {
  out([
    "ok" => false,
    "error" => "DB_PASS vazio. Configure a variável de ambiente DB_PASS no EasyPanel."
  ], 500);
}

$dsn = "pgsql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};";

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  // IMPORTANTE: "productName" tem N maiúsculo -> precisa de aspas duplas no Postgres
  $where = 'WHERE "productName" ILIKE \'%\' || :loteamento || \'%\'';
  $params = [":loteamento" => $loteamento];

  if ($status !== "") {
    $where .= " AND descricao_status = :status";
    $params[":status"] = $status;
  }

  $sql = "
    SELECT
      descricao_status,
      identificador_unidade,
      c4_unid,
      quantidade_unidade,
      valor,
      espelho_left,
      espelho_top,
      \"productName\" AS productName
    FROM estoque_lotes_uniao
    $where
    ORDER BY identificador_unidade
  ";

  $st = $pdo->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll();

  out([
    "ok" => true,
    "loteamento" => $loteamento,
    "status" => $status !== "" ? $status : null,
    "count" => count($rows),
    "data" => $rows,
  ]);

} catch (Throwable $e) {
  $payload = [
    "ok" => false,
    "error" => "Falha ao consultar banco.",
  ];

  if ($debug) {
    $payload["detail"] = $e->getMessage();
    $payload["db"] = [
      "host" => $DB_HOST,
      "port" => $DB_PORT,
      "name" => $DB_NAME,
      "user" => $DB_USER
    ];
  }

  out($payload, 500);
}
