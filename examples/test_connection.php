<?php // test_connection.php
require_once __DIR__ . '/../src/RouterOSClient.php';

use MikroTikLite\RouterOSClient;

header('Content-Type: application/json');

$ip = $_POST['ip'] ?? '';
$user = $_POST['user'] ?? '';
$pass = $_POST['pass'] ?? '';

if (!$ip || !$user) {
    echo json_encode(['success' => false, 'message' => 'IP dan Username wajib diisi.']);
    exit;
}

try {
    $api = new RouterOSClient($ip, $user, $pass);
    $api->connect();
	

    $identity = $api->comm('/system/identity/print');
    $resource = $api->comm('/system/resource/print');

    echo json_encode([
        'success' => true,
        'identity' => $identity[0]['name'] ?? 'Tidak tersedia',
        'version' => $resource[0]['version'] ?? 'Tidak tersedia',
        'board_name' => $resource[0]['board-name'] ?? 'Tidak tersedia',
        'architecture' => $resource[0]['architecture-name'] ?? 'Tidak tersedia',
        'uptime' => $resource[0]['uptime'] ?? 'Tidak tersedia',
    ]);
	
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal koneksi: ' . $e->getMessage()
    ]);
}
