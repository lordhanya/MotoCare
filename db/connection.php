<?php

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

$dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
$dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
$dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER');
$dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
$dbPort = $_ENV['DB_PORT'] ?? getenv('DB_PORT');

$db_SSL_CA = $_ENV['DB_SSL_CA_PATH'] ?? '/etc/secrets/ca.pem';

$dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";

$conn = new PDO($dsn, $dbUser, $dbPass, [
    PDO::MYSQL_ATTR_SSL_CA => $db_SSL_CA,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
]);

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);