<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM vehicles WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "header.php";
include "dashNav.php";
include "sidebar.php";

$today = date('Y-m-d');
$upcoming_services = 0;
$total_health = 0;
?>

<section class="report-section py-5 px-3 bg-dark min-vh-100">
    <div class="container text-white">
        <h2 class="mb-4 mt-5">Maintenance Report</h2>
        <div class="row">
            <div class="col-12">
                <table class="table table-striped table-dark align-middle">
                    <thead class="table-secondary text-dark">
                        <tr>
                            <th>Vehicle</th>
                            <th>Model</th>
                            <th>Current KM</th>
                            <th>Last Service</th>
                            <th>Next Service</th>
                            <th>Health</th>
                            <th>Compliance (%)</th>
                            <th>MTBF (KM)</th>
                            <th>MTTR (Hr)</th>
                            <th>Cost/KM</th>
                            <th>Breakdowns (Yr)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($vehicles && count($vehicles) > 0): ?>
                            <?php foreach ($vehicles as $vehicle):
                                // ----------- Health -----------
                                $current_km = (int)$vehicle['current_km'] ?? 0;
                                $last_service_km = (int)$vehicle['last_service_km'] ?? 0;
                                $next_service_km = (int)$vehicle['next_service_km'] ?? 0;
                                $health = 100;
                                $km_gap = max(1, $next_service_km - $last_service_km);
                                if ($next_service_km > 0 && $current_km >= $last_service_km) {
                                    $distance_progress = (($current_km - $last_service_km) / $km_gap) * 100;
                                    $health = max(0, 100 - $distance_progress);
                                }
                                if (!empty($vehicle['next_service_date']) && $vehicle['next_service_date'] < $today) {
                                    $health -= 10;
                                }
                                $health = max(0, $health);
                                $total_health += $health;

                                if (!empty($vehicle['next_service_date']) && $vehicle['next_service_date'] <= $today) {
                                    $upcoming_services++;
                                }

                                // ----------- Compliance -----------
                                $stmt1 = $conn->prepare("SELECT COUNT(*) as total, SUM(status = 'completed') as completed
                                FROM maintenance_schedule WHERE vehicle_id = :vid");
                                $stmt1->execute([':vid' => $vehicle['id']]);
                                $row = $stmt1->fetch(PDO::FETCH_ASSOC);
                                $compliance = $row['total'] > 0 ? round($row['completed'] / $row['total'] * 100) : 'N/A';

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
                                    $mtbf_km = round($distance_sum / (count($failures) - 1));
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
                                $cost_per_km = $vehicle['current_km'] > 0 ? round($total_cost / $vehicle['current_km'], 4) : 'N/A';

                                // ----------- Breakdown frequency (Yr) -----------
                                $stmt5 = $conn->prepare("SELECT COUNT(*) FROM maintenance WHERE vehicle_id=:vid
                                AND service_type='breakdown'
                                AND service_date BETWEEN DATE_SUB(NOW(), INTERVAL 1 YEAR) AND NOW()");
                                $stmt5->execute([':vid' => $vehicle['id']]);
                                $breakdowns = $stmt5->fetchColumn();
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($vehicle['vehicle_name']); ?></td>
                                    <td><?= htmlspecialchars($vehicle['model']); ?></td>
                                    <td><?= htmlspecialchars($vehicle['current_km']); ?></td>
                                    <td><?= htmlspecialchars($vehicle['last_service_date']); ?></td>
                                    <td><?= htmlspecialchars($vehicle['next_service_date']); ?></td>
                                    <td>
                                        <div class="progress" style="height: 10px; max-width: 110px;">
                                            <div class="progress-bar 
                                            <?= $health > 70 ? 'bg-success' : ($health > 40 ? 'bg-warning' : 'bg-danger'); ?>"
                                                role="progressbar"
                                                style="width: <?= round($health) ?>%;"
                                                aria-valuenow="<?= round($health) ?>" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small><?= round($health) ?>%</small>
                                    </td>
                                    <td><?= $compliance ?></td>
                                    <td><?= $mtbf_km ?></td>
                                    <td><?= $mttr ?></td>
                                    <td><?= $cost_per_km ?></td>
                                    <td><?= $breakdowns ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center">No vehicles found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary -->
        <div class="row mt-4">
            <div class="col-md-4 offset-md-8">
                <div class="summary p-3 bg-dark border rounded">
                    <h4 class="mb-3">Summary</h4>
                    <p><strong>Total Vehicles:</strong> <?= count($vehicles); ?></p>
                    <p><strong>Vehicles Due for Service:</strong> <?= $upcoming_services; ?></p>
                    <?php
                    $average_health = count($vehicles) > 0 ? round($total_health / count($vehicles)) : 0;
                    ?>
                    <p><strong>Average Vehicle Health:</strong></p>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar 
                            <?= $average_health > 70 ? 'bg-success' : ($average_health > 40 ? 'bg-warning' : 'bg-danger'); ?>"
                            role="progressbar"
                            style="width: <?= $average_health ?>%;"
                            aria-valuenow="<?= $average_health ?>"
                            aria-valuemin="0"
                            aria-valuemax="100">
                        </div>
                    </div>
                    <small><?= $average_health ?>%</small>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include "footer.php"; ?>