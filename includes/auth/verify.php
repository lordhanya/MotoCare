<?php
session_start();
require __DIR__ . '/../../db/connection.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid verification link.");
}

$token = $_GET['token'];

$stmt = $conn->prepare("SELECT id, is_verified FROM users WHERE verify_token = :token LIMIT 1");
$stmt->bindParam(':token', $token);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Invalid or expired token.");
}

if ($user['is_verified'] == 1) {
    echo "<script>alert('Your email is already verified.'); 
          window.location='../login.php';</script>";
    exit;
}

$update = $conn->prepare("UPDATE users SET is_verified = 1, verify_token = NULL WHERE id = :id");
$update->bindParam(':id', $user['id']);
$update->execute();

echo "<script>alert('Email verified successfully! You can now log in.'); 
      window.location='../login.php';</script>";
exit;
