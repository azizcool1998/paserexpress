<?php
/**
 * Secure API Collection Downloader for PaserExpress
 * Menghindari expose langsung file JSON di public folder.
 */

$collectionPath = __DIR__ . '/../api/PaserExpress.postman_collection.json';

// Jika file tidak ditemukan
if (!file_exists($collectionPath)) {
    http_response_code(404);
    echo "Collection file not found.";
    exit;
}

// Bisa ditambah autentikasi jika perlu:
// if (!isset($_GET['token']) || $_GET['token'] !== 'YOUR_SECRET_TOKEN') {
//     http_response_code(403);
//     echo "Forbidden";
//     exit;
// }

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="PaserExpress.postman_collection.json"');
header('Content-Length: ' . filesize($collectionPath));

readfile($collectionPath);
exit;
