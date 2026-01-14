<?php
// Load environment variables
require __DIR__ . '/../vendor/autoload.php';
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Security secret key from environment
$secret = $_ENV['CRON_SECRET_KEY'] ?? null;

if (!$secret || !isset($_GET['secret']) || $_GET['secret'] !== $secret) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

echo "Cron trigger accepted.\n";

require __DIR__ . '/send_reminders.php';

echo "Cron completed.\n";
