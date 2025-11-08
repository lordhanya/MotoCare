<?php
session_start();
include __DIR__ . "/../db/connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// $user_id = $_SESSION['user_id'];
// $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
// $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
// $stmt->execute();
// $user = $stmt->fetch(PDO::FETCH_ASSOC);

// if ($user) {
//     $_SESSION['first_name'] = $user['first_name'];
//     $_SESSION['last_name'] = $user['last_name'];
//     $_SESSION['email'] = $user['email'];
// }

include __DIR__ . "/header.php";
include __DIR__ . "/dashNav.php";
include __DIR__ . "/sidebar.php";
?>


<section>

</section>

<?php include __DIR__ . "/profile.php" ;?>