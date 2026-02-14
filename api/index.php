<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

echo json_encode([
  "ok" => true,
  "service" => "API Lotes - Solotes",
  "endpoints" => [
    "/api/lotes.php?loteamento=ACAPULCO",
    "/api/lotes.php?loteamento=ACAPULCO&status=Disponível",
    "/api/lotes.php?loteamento=ACAPULCO&debug=1"
  ],
  "env_required" => ["DB_HOST", "DB_PORT", "DB_NAME", "DB_USER", "DB_PASS"]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
