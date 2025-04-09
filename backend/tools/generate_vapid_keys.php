<?php
require __DIR__ . "/../../vendor/autoload.php";

use Minishlink\WebPush\VAPID;

$vapid = VAPID::createVapidKeys();

echo "Public key: " . $vapid["publicKey"] . "\n";
echo "Private key: " . $vapid["privateKey"] . "\n";
?>