<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch scheduled maintenance joined with vehicle data
$sql = "SELECT ms.*, v.vehicle_name 
FROM maintenance_schedule ms
JOIN vehicles v ON ms.vehicle_id = v.id
WHERE v.user_id = :user_id
ORDER BY ms.due_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$today = date('Y-m-d');
$overdue_count = 0;
$upcoming_count = 0;
$pending_count = 0;

foreach ($schedules as $schedule) {
    if ($schedule['due_date'] < $today && strtolower($schedule['status']) !== 'completed') {
        $overdue_count++;
    } elseif ($schedule['due_date'] >= $today && strtolower($schedule['status']) === 'pending') {
        $upcoming_count++;
    }
    if (strtolower($schedule['status']) === 'pending') {
        $pending_count++;
    }
}

include __DIR__ . "/header.php";
include __DIR__ . "/dashNav.php";
include __DIR__ . "/sidebar.php";
?>

<section class="scheduled-maintenance-section">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h2>Scheduled Maintenance</h2>
                <p>Manage upcoming and scheduled vehicle services</p>
            </div>
            <div class="header-actions">
                <a href="add_schedule.php" class="btn-add-schedule">
                    <i class="bi bi-calendar-plus"></i>
                    Schedule Service
                </a>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-overview">
            <div class="stat-box stat-overdue">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Overdue</span>
                    <span class="stat-value"><?= $overdue_count ?></span>
                </div>
            </div>
            <div class="stat-box stat-upcoming">
                <div class="stat-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Upcoming</span>
                    <span class="stat-value"><?= $upcoming_count ?></span>
                </div>
            </div>
            <div class="stat-box stat-pending">
                <div class="stat-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value"><?= $pending_count ?></span>
                </div>
            </div>
        </div>

        <!-- Schedule Table Card -->
        <div class="table-card">
            <div class="table-header">
                <h3>Service Schedule</h3>
                <div class="table-actions">
                    <button class="btn-filter" onclick="toggleFilters()">
                        <i class="bi bi-funnel"></i>
                        Filter
                    </button>
                    <button class="btn-export">
                        <i class="bi bi-download"></i>
                        Export
                    </button>
                </div>
            </div>

            <?php if ($schedules && count($schedules) > 0): ?>
                <div class="table-responsive">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Service Type</th>
                                <th>Due Date</th>
                                <th>Due KM</th>
                                <th>Days Left</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $row):
                                // Calculate days left
                                $due_date = new DateTime($row['due_date']);
                                $today_date = new DateTime($today);
                                $interval = $today_date->diff($due_date);
                                $days_left = $interval->invert ? -$interval->days : $interval->days;

                                // Determine status class
                                $statusClass = '';
                                $statusText = ucfirst($row['status']);

                                switch (strtolower($row['status'])) {
                                    case 'completed':
                                        $statusClass = 'status-completed';
                                        break;
                                    case 'pending':
                                        $statusClass = 'status-pending';
                                        break;
                                    case 'scheduled':
                                        $statusClass = 'status-scheduled';
                                        break;
                                    default:
                                        $statusClass = 'status-default';
                                }

                                // Determine priority based on days left
                                $priorityClass = '';
                                $priorityText = '';
                                $priorityIcon = '';

                                if ($days_left < 0) {
                                    $priorityClass = 'priority-critical';
                                    $priorityText = 'Overdue';
                                    $priorityIcon = 'bi-exclamation-circle-fill';
                                } elseif ($days_left <= 7) {
                                    $priorityClass = 'priority-high';
                                    $priorityText = 'Urgent';
                                    $priorityIcon = 'bi-exclamation-triangle-fill';
                                } elseif ($days_left <= 30) {
                                    $priorityClass = 'priority-medium';
                                    $priorityText = 'Soon';
                                    $priorityIcon = 'bi-info-circle-fill';
                                } else {
                                    $priorityClass = 'priority-low';
                                    $priorityText = 'Normal';
                                    $priorityIcon = 'bi-check-circle-fill';
                                }

                                // Determine service type icon
                                $serviceIcon = '';
                                switch (strtolower($row['scheduled_service_type'])) {
                                    case 'oil change':
                                        $serviceIcon = 'bi-droplet-fill';
                                        break;
                                    case 'tire rotation':
                                        $serviceIcon = 'bi-circle';
                                        break;
                                    case 'brake service':
                                        $serviceIcon = 'bi-stop-circle';
                                        break;
                                    case 'inspection':
                                        $serviceIcon = 'bi-search';
                                        break;
                                    default:
                                        $serviceIcon = 'bi-wrench';
                                }

                                // Row class for overdue
                                $rowClass = $days_left < 0 && strtolower($row['status']) !== 'completed' ? 'row-overdue' : '';
                            ?>
                                <tr class="<?= $rowClass ?>">
                                    <td>
                                        <div class="vehicle-cell">
                                            <i class="bi bi-car-front"></i>
                                            <span><?= htmlspecialchars($row['vehicle_name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="service-type">
                                            <i class="bi <?= $serviceIcon ?>"></i>
                                            <?= htmlspecialchars($row['scheduled_service_type']); ?>
                                        </div>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['due_date'])); ?></td>
                                    <td><?= number_format($row['due_km']); ?> km</td>
                                    <td>
                                        <div class="days-left <?= $days_left < 0 ? 'overdue' : '' ?>">
                                            <?php if ($days_left < 0): ?>
                                                <i class="bi bi-exclamation-circle"></i>
                                                <?= abs($days_left) ?> days overdue
                                            <?php elseif ($days_left == 0): ?>
                                                <i class="bi bi-calendar-event"></i>
                                                Today
                                            <?php else: ?>
                                                <i class="bi bi-calendar"></i>
                                                <?= $days_left ?> days
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="priority-badge <?= $priorityClass ?>">
                                            <i class="bi <?= $priorityIcon ?>"></i>
                                            <?= $priorityText ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-complete" title="Mark Complete">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button class="btn-icon btn-edit" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn-icon btn-delete" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <h3>No Scheduled Maintenance</h3>
                    <p>Stay on top of your vehicle maintenance by scheduling your services</p>
                    <a href="add_schedule.php" class="btn-add-first">
                        <i class="bi bi-calendar-plus"></i>
                        Schedule First Service
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
    function toggleFilters() {
        // Add filter functionality here
        alert('Filter functionality to be implemented');
    }
</script>

<?php include __DIR__ . "/footer.php"; ?>