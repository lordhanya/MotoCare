<?php
session_start();
include __DIR__ . "/../db/connection.php";

$pageTitle = "Dashboard | MotoCare";

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

$imgSrc = $user['profile_image'];

if (empty($imgSrc)) {
    $imgSrc = '../assets/images/default.jpg';
} else {
    $imgSrc = '../assets/images/' . $imgSrc; // e.g. p1.jpg
}

if ($user) {
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
}

// Fetch vehicles data
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$today = date('Y-m-d');
$upcoming_services = 0;
$total_health = 0;
$total_vehicles = count($vehicles);

// Calculate stats
foreach ($vehicles as $vehicle) {
    $current_km = (int)$vehicle['current_km'] ?? 0;
    $max_expected_km = 100000; // Or adjust as fits your fleet; can be made dynamic
    $health = max(0, 100 - ($current_km / $max_expected_km * 100));
    $health = round($health);
    $total_health += $health;

    if (!empty($vehicle['next_service_date']) && $vehicle['next_service_date'] <= $today) {
        $upcoming_services++;
    }
}
$average_health = $total_vehicles > 0 ? round($total_health / $total_vehicles) : 0;

include __DIR__ . "/header.php";
include __DIR__ . "/dashNav.php";
include __DIR__ . "/sidebar.php";
?>

<!-- Dashboard Content -->
<section class="dashboard-section">
    <div class="container">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="user-avatar">
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Profile picture" class="rounded-circle" width="120">
                    </div>
                </div>
                <div class="col">
                    <div class="welcome-content">
                        <h2>Welcome back,</h2>
                        <div class="user-name">
                            <?php
                            echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : '';
                            echo " ";
                            echo isset($_SESSION['last_name']) ? htmlspecialchars($_SESSION['last_name']) : '';
                            ?>
                        </div>
                        <div class="user-email">
                            <?= isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Guest'; ?>
                        </div>
                        <a class="profile-link" href="profile.php">
                            View/Edit Profile <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="add_vehicle.php" class="add-vehicle-btn">
                        <i class="bi bi-plus-circle-fill fs-4"></i>
                        Add New Vehicle
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-car-front-fill"></i>
                </div>
                <div class="stat-label">Total Vehicles</div>
                <div class="stat-value"><?= $total_vehicles ?></div>
                <div class="stat-footer">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Active fleet</span>
                    <a href="vehicles.php" class="stat-action">Manage <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-tools"></i>
                </div>
                <div class="stat-label">Service Due</div>
                <div class="stat-value"><?= $upcoming_services ?></div>
                <div class="stat-footer">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Requires attention</span>
                    <a href="schedule_list.php" class="stat-action">View <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-heart-pulse-fill"></i>
                </div>
                <div class="stat-label">Fleet Health</div>
                <div class="stat-value"><?= $average_health ?>%</div>
                <div class="stat-footer">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Average condition</span>
                    <a href="reports.php" class="stat-action">Details <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-clipboard-data-fill"></i>
                </div>
                <div class="stat-label">Reports</div>
                <div class="stat-value">
                    <i class="bi bi-file-earmark-text" style="font-size: 2rem;"></i>
                </div>
                <div class="stat-footer">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">View analytics</span>
                    <a href="reports.php" class="stat-action">Open <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Vehicle List -->
        <div class="section-header">
            <h3 class="section-title">Your Vehicles</h3>
            <?php if ($total_vehicles > 0): ?>
                <a href="vehicles.php" class="view-all-link">View All <i class="bi bi-arrow-right"></i></a>
            <?php endif; ?>
        </div>

        <?php if ($total_vehicles > 0): ?>
            <div class="vehicle-grid">
                <?php foreach ($vehicles as $vehicle):
                    // Calculate health
                    $current_km = (int)$vehicle['current_km'] ?? 0;
                    $max_expected_km = 100000; // Sync with above, or customize per vehicle type
                    $health = max(0, 100 - ($current_km / $max_expected_km * 100));
                    $health = round($health);

                    $health_class = $health > 70 ? 'excellent' : ($health > 40 ? 'good' : 'poor');
                    $health_label = $health > 70 ? 'Excellent' : ($health > 40 ? 'Good' : 'Poor');
                ?>
                    <div class="vehicle-card">
                        <div class="vehicle-header">
                            <div>
                                <div class="vehicle-name"><?= htmlspecialchars($vehicle['vehicle_name']); ?></div>
                                <div class="vehicle-model"><?= htmlspecialchars($vehicle['model']); ?></div>
                            </div>
                            <span class="health-badge health-<?= $health_class ?>">
                                <i class="bi bi-heart-fill"></i>
                                <?= $health_label ?>
                            </span>
                        </div>

                        <div class="vehicle-stats">
                            <div class="vehicle-stat">
                                <span class="vehicle-stat-label">Current KM</span>
                                <span class="vehicle-stat-value"><?= number_format($current_km) ?></span>
                            </div>
                            <div class="vehicle-stat">
                                <span class="vehicle-stat-label">Last Service</span>
                                <span class="vehicle-stat-value"><?= $vehicle['last_service_date'] ?: 'N/A' ?></span>
                            </div>
                            <div class="vehicle-stat">
                                <span class="vehicle-stat-label">Next Service</span>
                                <span class="vehicle-stat-value"><?= $vehicle['next_service_date'] ?: 'N/A' ?></span>
                            </div>
                            <div class="vehicle-stat">
                                <span class="vehicle-stat-label">Health Score</span>
                                <span class="vehicle-stat-value"><?= round($health) ?>%</span>
                            </div>
                        </div>

                        <div class="vehicle-actions">
                            <a href="vehicles.php?id=<?= $vehicle['id'] ?>" class="btn-action btn-primary-action">
                                View Details
                            </a>
                            <a href="add_maintenance.php?id=<?= $vehicle['id'] ?>" class="btn-action btn-secondary-action">
                                Add Service
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-car-front-fill"></i></div>
                <h3>No Vehicles Yet</h3>
                <p>Start by adding your first vehicle to track maintenance and health</p>
                <a href="add_vehicle.php" class="add-vehicle-btn">
                    <i class="bi bi-plus-circle-fill"></i>
                    Add Your First Vehicle
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . "/spinner.php"; ?>
<?php include __DIR__ . "/footer.php"; ?>