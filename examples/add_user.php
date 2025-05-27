<?php
require_once 'init.php'; // otomatis sudah konek dan $client siap pakai

// Data user yang ingin ditambahkan
$newUsername = 'testuser';
$newPassword = '123456';
$profile = 'default'; // nama profile hotspot

try {
    $response = $client->comm('/ip/hotspot/user/add', [
        'name' => $newUsername,
        'password' => $newPassword,
        'profile' => $profile
    ]);

    echo "User berhasil ditambahkan!";
    $client->debugPrint($response);

} catch (Exception $e) {
    echo "Gagal menambahkan user: " . $e->getMessage();
}