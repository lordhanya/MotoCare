<?php
// Security secret key
$secret = "8078LrQxJPeqtMpsaRPcNvmoS2AxEJQh2ugLdRdJUcwHSuj4drwxlNjDSj1I";

if (!isset($_GET['secret']) || $_GET['secret'] !== $secret) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

echo "Cron trigger accepted.\n";

require __DIR__ . '/send_reminders.php';

echo "Cron completed.\n";
