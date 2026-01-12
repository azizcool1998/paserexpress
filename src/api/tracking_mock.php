<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$resi = trim((string)($_GET['resi'] ?? ''));
$courier = trim((string)($_GET['courier'] ?? ''));

if ($resi === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'resi_required']);
  exit;
}

echo json_encode([
  'ok' => true,
  'resi' => $resi,
  'courier' => $courier ?: 'auto',
  'status' => 'ON_PROCESS',
  'history' => [
    ['time'=>date('Y-m-d H:i:s', time()-3600*24), 'desc'=>'Order dibuat'],
    ['time'=>date('Y-m-d H:i:s', time()-3600*8),  'desc'=>'Dikirim ke gudang'],
    ['time'=>date('Y-m-d H:i:s'),                 'desc'=>'Dalam perjalanan'],
  ]
]);
