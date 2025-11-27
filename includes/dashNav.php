<?php
session_start();

include __DIR__ . "/../db/connection.php";


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT first_name from users WHERE id=:user_id");
$stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!-- Dashboard Navbar -->
<nav class="navbar no-print navbar-expand-lg border-body fixed-top px-4 py-3 dashNav">
    <div class="container-fluid">
        <!-- Left Section: Sidebar Toggle + Logo -->
        <div class="d-flex align-items-center gap-3">
            <a class="btn sidebarToggle-btn" data-bs-toggle="offcanvas" href="#offcanvasExample" role="button" aria-controls="offcanvasExample">
                <i class="bi bi-list"></i>
            </a>
            <a class="navbar-brand" href="dashboard.php">Moto<span>Care</span></a>
        </div>

        <!-- Right Section: Profile + Logout -->
        <div class="d-flex align-items-center gap-3 ms-auto">
            <div class="dashNavProfile-section">
                <div class="profile-icon">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="profile-text">
                    <span class="profile-greeting">Welcome</span>
                    <span class="profile-name"><?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?></span>
                </div>
            </div>

            <div class="nav-divider"></div>

            <a class="logout-btn py-3" href="logout.php">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
        </div>
    </div>
</nav>