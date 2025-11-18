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

// Edit Schedule
if (isset($_POST['vehicle_id'], $_POST['schedule_id'])) {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $schedule_id = (int)$_POST['schedule_id'];
    $service_type = trim($_POST['scheduled_service_type'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');
    $due_km = isset($_POST['due_km']) ? (int)$_POST['due_km'] : 0;
    $status = trim($_POST['status'] ?? '');

    // Make sure this schedule and vehicle belong to this user
    $check = $conn->prepare("
        SELECT ms.id FROM maintenance_schedule ms
        JOIN vehicles v ON ms.vehicle_id = v.id
        WHERE ms.id = :schedule_id AND v.user_id = :user_id
    ");
    $check->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
    $check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $check->execute();

    if ($check->rowCount() > 0) {
        $update = $conn->prepare("UPDATE maintenance_schedule 
            SET vehicle_id = :vehicle_id, scheduled_service_type = :scheduled_service_type, 
                due_date = :due_date, due_km = :due_km, status = :status 
            WHERE id = :schedule_id
        ");
        $update->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
        $update->bindParam(':scheduled_service_type', $service_type, PDO::PARAM_STR);
        $update->bindParam(':due_date', $due_date, PDO::PARAM_STR);
        $update->bindParam(':due_km', $due_km, PDO::PARAM_INT);
        $update->bindParam(':status', $status, PDO::PARAM_STR);
        $update->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);

        if ($update->execute()) {
            $_SESSION['message'] = "Your schedule has been updated!";
        } else {
            $_SESSION['message'] = "Failed to update your schedule!";
        }
    } else {
        $_SESSION['message'] = "Schedule was not found or access denied.";
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

// Fetch user's vehicles for dropdown
$stmt = $conn->prepare("SELECT id, vehicle_name FROM vehicles WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                                                <button type="button" title="Edit" class="btn-icon btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
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

                                    <!-- Modal for schedule edit -->
                                    <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content p-3">
                                                <div class="modal-header">
                                                    <h1 class="modal-title fs-5" id="editModalLabel<?php echo $row['id']; ?>">Edit <span class="text-danger"><?php echo htmlspecialchars($row['vehicle_name']); ?></span> Service Schedule</h1>
                                                    <button type="button" class="btn-close closeBtn" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST" action="" class="scheduleUpdateForm" id="scheduleUpdateForm">
                                                        <input type="hidden" name="schedule_id" value="<?= $row['id'] ?>">
                                                        <div class="form-grid">
                                                            <div class="form-group mb-3">
                                                                <label for="vehicle_id<?= $row['id']; ?>" class="text-dark form-label">
                                                                    <i class="bi bi-car-front-fill me-2"></i>Vehicle
                                                                </label>
                                                                <select name="vehicle_id" id="vehicle_id<?= $row['id']; ?>" class="form-select" required>
                                                                    <option value="" disabled>Select Vehicle</option>
                                                                    <?php foreach ($vehicles as $vehicle): ?>
                                                                        <option value="<?= $vehicle['id']; ?>" <?= ($vehicle['id'] == $row['vehicle_id']) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($vehicle['vehicle_name']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="form-group mb-3">
                                                                <label for="scheduled_service_type<?= $row['id']; ?>" class="form-label text-dark">
                                                                    <i class="bi bi-wrench-adjustable me-2"></i>Scheduled Service Type
                                                                </label>
                                                                <input type="text"
                                                                    name="scheduled_service_type"
                                                                    class="form-control"
                                                                    id="scheduled_service_type<?= $row['id']; ?>"
                                                                    value="<?= htmlspecialchars($row['scheduled_service_type']); ?>"
                                                                    required>
                                                            </div>

                                                            <div class="form-group mb-3">
                                                                <label for="due_date<?= $row['id']; ?>" class="form-label text-dark">
                                                                    <i class="bi bi-calendar-event me-2"></i>Due Date
                                                                </label>
                                                                <input type="date"
                                                                    name="due_date"
                                                                    id="due_date<?= $row['id']; ?>"
                                                                    value="<?= htmlspecialchars($row['due_date']); ?>"
                                                                    class="form-control">
                                                            </div>

                                                            <div class="form-group mb-3">
                                                                <label for="due_km<?= $row['id']; ?>" class="form-label text-dark">
                                                                    <i class="bi bi-speedometer2 me-2"></i>Due KM
                                                                </label>
                                                                <input type="number"
                                                                    name="due_km"
                                                                    id="due_km<?= $row['id']; ?>"
                                                                    class="form-control"
                                                                    value="<?= htmlspecialchars($row['due_km']); ?>"
                                                                    min="0">
                                                            </div>

                                                            <div class="form-group mb-3">
                                                                <label for="status<?= $row['id']; ?>" class="form-label text-dark">
                                                                    <i class="bi bi-info-circle-fill me-2"></i>Status
                                                                </label>
                                                                <select name="status" id="status<?= $row['id']; ?>" class="form-select" required>
                                                                    <option value="pending" <?= strtolower($row['status']) === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                                    <option value="completed" <?= strtolower($row['status']) === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                                    <option value="missed" <?= strtolower($row['status']) === 'missed' ? 'selected' : '' ?>>Missed</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer mt-4">
                                                            <div class="form-actions">
                                                                <button type="submit" class="btn btn-submit">
                                                                    <i class="bi bi-check-circle-fill me-2"></i>Submit
                                                                </button>
                                                                <button type="button" class="btn px-5 py-2 d-flex align-items-center justify-content-center gap-2 btn-secondary" data-bs-dismiss="modal">
                                                                    <i class="bi bi-x-circle"></i> Cancel
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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