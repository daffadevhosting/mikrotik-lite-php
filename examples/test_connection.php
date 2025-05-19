<?php
require_once '../src/RouterOSClient.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$ip = $_POST['ip'];
$user = $_POST['user'];
$pass = $_POST['pass'];

$api = new RouterOSClient($ip, $user, $pass);

if (!$ip || !$user || !$pass) {
    exit("<p style='color:red;'>❌ IP / User / Pass kosong</p>");
}

try {
    $api = new RouterOSClient($ip, $user, $pass);
    $api->connect();
    echo "<h3 style='color:green;'>✅ Koneksi Berhasil!</h3>";

    $identityRaw = $api->comm('/system/identity/print');
    $resourceRaw = $api->comm('/system/resource/print');

    echo "<pre style='background:#eee;padding:10px'>";
    echo "DEBUG - Identity Raw:\n";
    print_r($identityRaw);
    echo "DEBUG - Resource Raw:\n";
    print_r($resourceRaw);
    echo "</pre>";

    $identity = $identityRaw[0] ?? null;
    $resource = $resourceRaw[0] ?? null;

    if (!$identity || !$resource) {
        echo "<p style='color:red;'>⚠️ Tidak dapat mengambil info device (data kosong atau tidak sesuai).</p>";
    } else {
        echo "<ul>";
        echo "<li><strong>Identity:</strong> " . htmlspecialchars($identity['name']) . "</li>";
        echo "<li><strong>Version:</strong> " . htmlspecialchars($resource['version']) . "</li>";
        echo "<li><strong>Board Name:</strong> " . htmlspecialchars($resource['board-name']) . "</li>";
        echo "<li><strong>Architecture:</strong> " . htmlspecialchars($resource['architecture-name']) . "</li>";
        echo "<li><strong>Uptime:</strong> " . htmlspecialchars($resource['uptime']) . "</li>";
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "<h3 style='color:red;'>❌ Gagal konek: " . htmlspecialchars($e->getMessage()) . "</h3>";
}
