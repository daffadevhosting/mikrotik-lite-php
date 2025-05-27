require_once __DIR__ . '/../src/RouterOSClient.php';
use MikroTikLite\RouterOSClient;

// Ambil data dari Firebase (pakai REST API Firebase)
$firebaseUrl = 'https://your-project-id.firebaseio.com/mikrotik_logins/router1.json';
$data = json_decode(file_get_contents($firebaseUrl), true);

if (!$data || !isset($data['ip'], $data['username'], $data['password'])) {
    die(json_encode(['success' => false, 'message' => 'Data tidak lengkap']));
}

try {
    $api = new RouterOSClient($data['ip'], $data['username'], $data['password']);
    $api->connect();

    $identity = $api->comm('/system/identity/print');
    echo json_encode(['success' => true, 'identity' => $identity[0]['name'] ?? 'Tidak tersedia']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}