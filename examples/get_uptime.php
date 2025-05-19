<?php
require_once 'init.php';

$uptime = $api->getUserUptime('budi123');
if ($uptime) {
    echo "User sedang online, uptime: $uptime";
} else {
    echo "User tidak aktif.";
}
