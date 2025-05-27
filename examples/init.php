<?php
require_once '../src/RouterOSClient.php';

use MikroTikLite\RouterOSClient;

$ip = $_POST['ip'];
$user = $_POST['user'];
$pass = $_POST['pass'];

// Inisialisasi client RouterOS
$client = new RouterOSClient($ip, $user, $pass);
$client->connect();