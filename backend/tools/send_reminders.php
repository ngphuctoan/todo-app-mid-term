<?php
require __DIR__ . "/../../vendor/autoload.php";

use App\Utils\Database;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$pdo = Database::connect();

$getStmt = $pdo->prepare("
    select
        todos.id, todos.title, todos.description, todos.is_completed, todos.reminder,
        subs.endpoint, subs.public_key, subs.push_auth
    from todos
    join users on users.id = todos.user_id
    join push_subscriptions subs on subs.user_id = users.id
    where todos.reminder is not null
        and todos.reminder <= now()
        and todos.is_completed = 0
        and todos.reminder_sent = 0;
");
$getStmt->execute();

$rows = $getStmt->fetchAll();

error_log(print_r($rows, true));

$webPush = new WebPush([
    "VAPID" => [
        "subject" => "mailto:no-reply@todo-app",
        "publicKey" => $_ENV["VAPID_PUBLIC_KEY"],
        "privateKey" => $_ENV["VAPID_PRIVATE_KEY"]
    ]
]);

foreach ($rows as $row) {
    $subscription = Subscription::create([
        "endpoint" => $row["endpoint"],
        "publicKey" => $row["public_key"],
        "authToken" => $row["push_auth"]
    ]);

    $payload = json_encode([
        "title" => "Reminder: " . $row["title"],
        "body" => $row["description"]
    ]);

    $webPush->queueNotification($subscription, $payload);
}

$todoIds = array_column($rows, "id");
if ($todoIds) {
    $placeholders = implode(",", array_fill(0, count($todoIds), "?"));

    $reminderStmt = $pdo->prepare("
        update todos set reminder_sent = 1
        where id in ($placeholders)
    ");
    $reminderStmt->execute($todoIds);
}

foreach($webPush->flush() as $report) {
    $endpoint = $report->getRequest()->getUri()->__toString();

    if ($report->isSuccess()) {
        error_log("[+] Sent successfully to: {$endpoint}");
    } else {
        error_log("[+] Failed sending to {$endpoint}: " . $report->getReason());
    }
};
?>