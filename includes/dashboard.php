<?php
session_start();
include __DIR__ . "/../db/connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['login_success'])) {
    echo "<script>alert('Login Successful!');</script>";
    unset($_SESSION['login_success']);
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
}

include __DIR__ . "/header.php";
include __DIR__ . "/dashNav.php";
include __DIR__ . "/sidebar.php";
?>

<!-- Dashboard Content -->
<section class="dashboard-section">
    <div class="container pt-5">
        <div class="row">
            <div class="col">
                <div class="welcome-container d-flex bg-dark align-items-center gap-4">
                    <i class="bi bi-person-circle text-white user"></i>
                    <div class="d-grid">
                        <h2 class="text-white">Welcome,</h2>
                        <p class='user-name'><?php
                                                echo isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '';
                                                echo " ";
                                                echo isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '';
                                                ?> </p>
                        <p class="email">
                            <?= isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Guest'; ?>
                        </p>
                        <a class="viewEditProfile" href="profile.php">View | Edit Profile</a>
                    </div>
                    <div class="addNewCar-btn d-flex ms-auto">
                        <a href="add_vehicle.php" class="btn d-flex align-items-center justify-content-center gap-3"><i class="bi bi-plus-square-fill fs-3"></i>Add new car</a>
                    </div>
                </div>

            </div>
            <div class="row g-4">
                <div class="col">
                    <div class="card shadow-sm bg-dark">
                        <div class="card-body text-white">
                            <h5 class="card-title">Total Vehicles Added</h5>
                            <p class="card-text">1</p>
                            <div class="btn-container d-flex align-items-left gap-5">
                                <a href="add_vehicles.php" class="btn btn-success">Add More</a>
                                <a href="vehicles.php" class="btn btn-warning">Manage Vehicles</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card shadow-sm bg-dark">
                        <div class="card-body text-white">
                            <h5 class="card-title">Upcoming Services</h5>
                            <p class="card-text">NULL</p>
                            <a href="add_vehicle.php" class="btn btn-warning">Go</a>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card shadow-sm bg-dark">
                        <div class="card-body text-white">
                            <h5 class="card-title">Past Services</h5>
                            <p class="card-text">NULL</p>
                            <a href="settings.php" class="btn btn-secondary">Go</a>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card shadow-sm bg-dark">
                        <div class="card-body text-white">
                            <h5 class="card-title">Next Service Due</h5>
                            <p class="card-text">NULL</p>
                            <a href="settings.php" class="btn btn-secondary">Go</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <!-- vehicle lsit -->
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/footer.php"; ?>