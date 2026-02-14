<?php declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

echo json_encode([
  "ok" => true,
  "service" => "API Lotes - Solotes",
  "endpoints" => [
    "/lotes.php?loteamento=ACAPULCO",
    "/lotes.php?loteamento=JARDIM%20DO%20PORTO",
  ],
  "note" => "Se der erro 500, confira as ENV no EasyPanel: DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS"
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
