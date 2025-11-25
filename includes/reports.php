<?php
session_start();
include __DIR__ . "/../db/connection.php";

$pageTitle = "Reports | MotoCare";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM vehicles WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . "/header.php";
include __DIR__ . "/dashNav.php";
include __DIR__ . "/sidebar.php";

$today = date('Y-m-d');
$upcoming_services = 0;
$total_health = 0;
?>



<section class="report-section">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h2>Fleet Maintenance Report</h2>
            <p>Comprehensive analysis of your vehicle fleet performance and health metrics</p>
        </div>

        <?php
        $total_vehicles = count($vehicles);
        $total_compliance = 0;
        $compliance_count = 0;
        ?>

        <!-- Vehicle Details Table -->
        <div class="table-card">
            <div class="table-card-header">
                <h3>Vehicle Maintenance Details</h3>
            </div>

            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Model</th>
                            <th>Current KM</th>
                            <th>Last Service</th>
                            <th>Next Service</th>
                            <th>Health</th>
                            <th>Compliance</th>
                            <th>MTBF (KM)</th>
                            <th>MTTR (Hr)</th>
                            <th>Cost/KM</th>
                            <th>Breakdowns</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($vehicles && $total_vehicles > 0): ?>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <?php
                                // ----------- Health -----------
                                // $current_km = (int)($vehicle['current_km'] ?? 0);
                                // $last_service_km = (int)($vehicle['last_service_km'] ?? 0);
                                // $next_service_km = (int)($vehicle['next_service_km'] ?? 0);
                                // $health = 100;
                                // $km_gap = max(1, $next_service_km - $last_service_km);
                                // if ($next_service_km > 0 && $current_km >= $last_service_km) {
                                //     $distance_progress = (($current_km - $last_service_km) / $km_gap) * 100;
                                //     $health = max(0, 100 - $distance_progress);
                                // }
                                // if (!empty($vehicle['next_service_date']) && $vehicle['next_service_date'] < $today) {
                                //     $health -= 10;
                                // }
                                // $health = max(0, $health);
                                // $total_health += $health;

                                // if (!empty($vehicle['next_service_date']) && $vehicle['next_service_date'] <= $today) {
                                //     $upcoming_services++;
                                // }

                                // $health_class = $health > 70 ? 'excellent' : ($health > 40 ? 'good' : 'poor');


                                // --- Simple Odometer-based Health Calculation ---
                                $current_km = (int)($vehicle['current_km'] ?? 0);
                                $max_expected_km = 100000; // Adjust as appropriate for your vehicle category
                                $health = max(0, 100 - ($current_km / $max_expected_km * 100));
                                $health = round($health);
                                $total_health += $health;

                                // Optional: still highlight vehicles with overdue service dates
                                if (!empty($vehicle['next_service_date']) && $vehicle['next_service_date'] <= $today) {
                                    $upcoming_services++;
                                }

                                $health_class = $health > 70 ? 'excellent' : ($health > 40 ? 'good' : 'poor');

                                // ----------- Compliance -----------
                                $stmt1 = $conn->prepare("SELECT COUNT(*) as total, SUM(status = 'completed') as completed
                                FROM maintenance_schedule WHERE vehicle_id = :vid");
                                $stmt1->execute([':vid' => $vehicle['id']]);
                                $row = $stmt1->fetch(PDO::FETCH_ASSOC);
                                $compliance = $row['total'] > 0 ? round($row['completed'] / $row['total'] * 100) : null;
                                if ($compliance !== null) {
                                    $total_compliance += $compliance;
                                    $compliance_count++;
                                }
                                $compliance_display = $compliance !== null ? $compliance . '%' : 'N/A';
                                $compliance_class = $compliance > 80 ? 'success' : ($compliance > 50 ? 'warning' : 'danger');

                                // ----------- MTBF (KM) -----------
                                $stmt2 = $conn->prepare("SELECT service_km, service_date FROM maintenance
                                WHERE vehicle_id=:vid AND service_type='breakdown' ORDER BY service_date");
                                $stmt2->execute([':vid' => $vehicle['id']]);
                                $failures = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                                $mtbf_km = 'N/A';
                                if (count($failures) > 1) {
                                    $distance_sum = 0;
                                    for ($i = 1; $i < count($failures); $i++) {
                                        $distance_sum += $failures[$i]['service_km'] - $failures[$i - 1]['service_km'];
                                    }
                                    $mtbf_km = number_format(round($distance_sum / (count($failures) - 1)));
                                }

                                // ----------- MTTR (Hr) -----------
                                $stmt3 = $conn->prepare("SELECT TIMESTAMPDIFF(HOUR, repair_start, repair_end) as duration FROM repairs
                                    WHERE vehicle_id=:vid AND repair_start IS NOT NULL AND repair_end IS NOT NULL");
                                $stmt3->execute([':vid' => $vehicle['id']]);
                                $durations = $stmt3->fetchAll(PDO::FETCH_COLUMN);
                                $mttr = count($durations) > 0 ? round(array_sum($durations) / count($durations), 1) : 'N/A';

                                // ----------- Cost per KM -----------
                                $stmt4 = $conn->prepare("SELECT SUM(cost) FROM maintenance WHERE vehicle_id = :vid");
                                $stmt4->execute([':vid' => $vehicle['id']]);
                                $total_cost = (float)$stmt4->fetchColumn();
                                $cost_per_km = $vehicle['current_km'] > 0 ? number_format($total_cost / $vehicle['current_km'], 2) : 'N/A';

                                // ----------- Breakdown frequency (Yr) -----------
                                $stmt5 = $conn->prepare("SELECT COUNT(*) FROM maintenance WHERE vehicle_id=:vid
                                    AND service_type='breakdown'
                                    AND service_date BETWEEN DATE_SUB(NOW(), INTERVAL 1 YEAR) AND NOW()");
                                $stmt5->execute([':vid' => $vehicle['id']]);
                                $breakdowns = $stmt5->fetchColumn();
                                ?>
                                <tr>
                                    <td><span class="vehicle-name"><?= htmlspecialchars($vehicle['vehicle_name']); ?></span></td>
                                    <td><?= htmlspecialchars($vehicle['model']); ?></td>
                                    <td><?= number_format($vehicle['current_km']); ?></td>
                                    <td><?= htmlspecialchars($vehicle['last_service_date'] ?: 'N/A'); ?></td>
                                    <td><?= htmlspecialchars($vehicle['next_service_date'] ?: 'N/A'); ?></td>
                                    <td>
                                        <div class="health-indicator">
                                            <div class="health-bar">
                                                <div class="health-fill <?= $health_class ?>" style="width: <?= round($health) ?>%"></div>
                                            </div>
                                            <span class="health-percent"><?= round($health) ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($compliance !== null): ?>
                                            <span class="badge-metric badge-<?= $compliance_class ?>">
                                                <?= $compliance_display ?>
                                            </span>
                                        <?php else: ?>
                                            <?= $compliance_display ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $mtbf_km ?></td>
                                    <td><?= $mttr ?></td>
                                    <td>$<?= $cost_per_km ?></td>
                                    <td>
                                        <span class="badge-metric badge-<?= $breakdowns > 3 ? 'danger' : ($breakdowns > 1 ? 'warning' : 'info') ?>">
                                            <?= $breakdowns ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">🚗</div>
                                        <h4>No Vehicles Found</h4>
                                        <p>Add vehicles to your fleet to see maintenance reports</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php
        // Ensure health/compliance are calculated after looping!
        $average_health = $total_vehicles > 0 ? round($total_health / $total_vehicles) : 0;
        $avg_compliance = $compliance_count > 0 ? round($total_compliance / $compliance_count) : null;
        ?>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-car-front-fill"></i></div>
                <div class="stat-card-label">Total Vehicles</div>
                <div class="stat-card-value"><?= $total_vehicles ?></div>
                <div class="stat-card-trend">Active fleet</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-wrench-adjustable"></i></div>
                <div class="stat-card-label">Service Due</div>
                <div class="stat-card-value" id="service-due-value"><?= $upcoming_services ?></div>
                <div class="stat-card-trend">Requires attention</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-heart-pulse"></i></div>
                <div class="stat-card-label">Avg Health</div>
                <div class="stat-card-value"><?= $average_health ?>%</div>
                <div class="stat-card-trend">Fleet condition</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon">✓</div>
                <div class="stat-card-label">Compliance</div>
                <div class="stat-card-value" id="avg-compliance-value">
                    <?= $avg_compliance !== null ? $avg_compliance . '%' : '--' ?>
                </div>
                <div class="stat-card-trend">Maintenance schedule</div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/spinner.php"; ?>
<?php include "footer.php"; ?>
