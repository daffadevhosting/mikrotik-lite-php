<?php
require_once '../src/RouterOSClient.php';

$ip = $_POST['ip'];
$user = $_POST['user'];
$pass = $_POST['pass'];

$api = new RouterOSClient($ip, $user, $pass);
$api->connect();
