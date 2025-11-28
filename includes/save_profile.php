<?php
session_start();
include __DIR__ . "/../db/connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$finalPath = null;

// 1) If user uploaded a custom image
if (!empty($_FILES['profile_image']['name'])) {
    $fileName = basename($_FILES['profile_image']['name']);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($fileType, $allowTypes)) {
        $newName = 'user_' . $user_id . '_' . time() . '.' . $fileType;
        $targetDir  = 'uploads/';
        $targetFile = $targetDir . $newName;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
            $finalPath = $targetFile;
        }
    }
}

// 2) If no upload, but user picked a default avatar
if (!$finalPath && !empty($_POST['default_avatar'])) {
    $finalPath = $_POST['default_avatar'];
}

// 3) Update user row
if ($finalPath) {
    $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
    $success = $stmt->execute([$finalPath, $user_id]);

}
header("Location: profile.php");
exit;
