<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id'], $_POST['action']) && $_POST['action'] === 'delete') {
    $schedule_id = (int)$_POST['schedule_id'];

    $check = $conn->prepare("
        SELECT ms.id FROM maintenance_schedule ms
        JOIN vehicles v ON ms.vehicle_id = v.id
        WHERE ms.id = :sid AND v.user_id = :uid
    ");
    $check->bindParam(':sid', $schedule_id, PDO::PARAM_INT);
    $check->bindParam(':uid', $user_id, PDO::PARAM_INT);
    $check->execute();
    $exists = $check->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $delete = $conn->prepare("DELETE FROM maintenance_schedule WHERE id = :sid");
        $delete->bindParam(':sid', $schedule_id, PDO::PARAM_INT);
        $delete->execute();

        if ($delete->execute()) {
            $_SESSION['message'] = "Schedule deleted successfully!";
        } else {
            $_SESSION['message'] = "Failed to delete your schedule!";
        }
    } else {
        $_SESSION['message'] = "Schedule not found or access denied.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id'], $_POST['action']) && $_POST['action'] === 'update') {
    $schedule_id = (int)$_POST['schedule_id'];

    $check = $conn->prepare("
        SELECT ms.id FROM maintenance_schedule ms
        JOIN vehicles v ON ms.vehicle_id = v.id
        WHERE ms.id = :sid AND v.user_id = :uid
    ");

    $check->bindParam(':sid', $schedule_id, PDO::PARAM_INT);
    $check->bindParam(':uid', $user_id, PDO::PARAM_INT);
    $check->execute();
    $exists = $check->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $update = $conn->prepare("UPDATE maintenance_schedule SET status = 'completed' WHERE id = :sid");
        $update->bindParam(':sid', $schedule_id, PDO::PARAM_INT);
        $update->execute();

        if ($update->execute()) {
            $_SESSION['message'] = "Your schedule has been updated!";
        } else {
            $_SESSION['message'] = "Failed to update your schedule!";
        }
    } else {
        $_SESSION['message'] = "Schedule not found or access denied.";
    }
        
}

$stmt = $conn->prepare("
    SELECT ms.*, v.vehicle_name FROM maintenance_schedule ms
    JOIN vehicles v ON ms.vehicle_id = v.id
    WHERE v.user_id = :uid
    ORDER BY ms.due_date ASC
");
$stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$today = date('Y-m-d');
$overdue_count = 0;
$upcoming_count = 0;
$pending_count = 0;

foreach ($schedules as $sc) {
    $due_date = $sc['due_date'];
    $status = strtolower($sc['status']);
    if ($due_date < $today && $status !== 'completed') {
        $overdue_count++;
    } elseif ($due_date >= $today && $status === 'pending') {
        $upcoming_count++;
    }
    if ($status === 'pending') {
        $pending_count++;
    }
}

include __DIR__ . "/header.php";
include __DIR__ . "/dashNav.php";
include __DIR__ . "/sidebar.php";
?>

<section class="scheduled-list-section">
    <div class="container">
        <div class="no-print">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-content">
                    <h2>Scheduled Maintenance</h2>
                    <p>Manage upcoming and scheduled vehicle services</p>
                </div>
                <div class="header-actions">
                    <a href="schedule_maintenance.php" class="btn-add-schedule">
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
        </div>
        <!-- Alert -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert text-center text-success border-1 rounded-3 border-success my-3 ms-auto me-auto d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-bell"></i>
                <?php
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>
        <!-- Schedule Table Card -->
        <div class="table-card">
            <div class="table-header">
                <h3>Service Schedule</h3>
                <div class="table-actions">
                    <button class="btn-filter" onclick="toggleFilters()">
                        <i class="bi bi-funnel"></i>
                        Filter
                    </button>
                    <button class="btn-export" id="exportBtn">
                        <i class="bi bi-download"></i>
                        Export
                    </button>
                    <script>
                        document.getElementById("exportBtn").addEventListener('click', function() {
                            window.print();
                        })
                    </script>
                </div>
            </div>

            <div class="content">
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
                                                <i class="bi bi-caret-right-fill"></i>
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
                                                    <?= $days_left ?> day/s
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
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to mark it as complete?');">
                                                    <input type="hidden" name="schedule_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="action" value="update">
                                                    <button class="btn-icon btn-complete" title="Mark Complete">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </form>
                                                <button class="btn-icon btn-edit" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                                    <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($row['id']) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button class="btn-icon btn-delete" title="Delete" type="submit">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
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
                        <a href="schedule_maintenance.php" class="btn-add-first">
                            <i class="bi bi-calendar-plus"></i>
                            Schedule First Service
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
    function toggleFilters() {
        // Add filter functionality here
        alert('Filter functionality to be implemented');
    }
</script>
<?php include __DIR__ . "/spinner.php"; ?>
<?php include __DIR__ . "/footer.php"; ?>