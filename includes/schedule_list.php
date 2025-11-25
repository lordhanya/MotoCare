<?php
session_start();
include "../db/connection.php";

$pageTitle = "Schedule List | MotoCare";

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
                                                    <?= abs($days_left) ?> day/s overdue
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
                                                    <button type="button" class="btn-close closeBtn" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST" action="" class="scheduleUpdateForm" id="scheduleUpdateForm">
                                                        <input type="hidden" name="schedule_id" value="<?= $row['id'] ?>">
                                                        <div class="form-grid">
                                                            <div class="form-group mb-3">
                                                                <label for="vehicle_id<?= $row['id']; ?>" class="form-label">
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
                                                                <label for="scheduled_service_type<?= $row['id']; ?>" class="form-label">
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
                                                                <label for="due_date<?= $row['id']; ?>" class="form-label">
                                                                    <i class="bi bi-calendar-event me-2"></i>Due Date
                                                                </label>
                                                                <input type="date"
                                                                    name="due_date"
                                                                    id="due_date<?= $row['id']; ?>"
                                                                    value="<?= htmlspecialchars($row['due_date']); ?>"
                                                                    class="form-control">
                                                            </div>

                                                            <div class="form-group mb-3">
                                                                <label for="due_km<?= $row['id']; ?>" class="form-label">
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
                                                                <label for="status<?= $row['id']; ?>" class="form-label">
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
                                                            <div class="form-actions d-flex align-items-center gap-2 justify-content-center">
                                                                <button type="submit" class="btn btn-submit">
                                                                    <i class="bi bi-check-circle-fill me-2"></i>Submit
                                                                </button>
                                                                <button type="button" class="btn d-flex align-items-center justify-content-center gap-2 btn-secondary" data-bs-dismiss="modal">
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
    /**
     * ========================================
     * SCHEDULE LIST - FILTER & EXPORT
     * ========================================
     */

    // ========================================
    // FILTER FUNCTIONALITY
    // ========================================

    /**
     * Toggle filter panel visibility
     */
    function toggleFilters() {
        const filterPanel = document.getElementById("filterPanel");

        if (!filterPanel) {
            createFilterPanel();
        } else {
            filterPanel.classList.toggle("active");
        }
    }

    /**
     * Create filter panel dynamically for schedule list
     */
    function createFilterPanel() {
        const tableCard = document.querySelector(".table-card");
        const tableHeader = document.querySelector(".table-header");

        // Create filter panel HTML
        const filterPanelHTML = `
    <div id="filterPanel" class="filter-panel active">
      <div class="filter-grid">
        <!-- Vehicle Filter -->
        <div class="filter-group">
          <label for="filterVehicle">Vehicle</label>
          <select id="filterVehicle" class="filter-input">
            <option value="">All Vehicles</option>
          </select>
        </div>
        
        <!-- Service Type Filter -->
        <div class="filter-group">
          <label for="filterServiceType">Service Type</label>
          <input type="text" id="filterServiceType" class="filter-input" placeholder="e.g., Oil Change">
        </div>
        
        <!-- Status Filter -->
        <div class="filter-group">
          <label for="filterStatus">Status</label>
          <select id="filterStatus" class="filter-input">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="missed">Missed</option>
          </select>
        </div>
        
        <!-- Priority Filter -->
        <div class="filter-group">
          <label for="filterPriority">Priority</label>
          <select id="filterPriority" class="filter-input">
            <option value="">All Priorities</option>
            <option value="overdue">Overdue</option>
            <option value="urgent">Urgent</option>
            <option value="soon">Soon</option>
            <option value="normal">Normal</option>
          </select>
        </div>
        
        <!-- Date Range Filter -->
        <div class="filter-group">
          <label for="filterDateFrom">Due Date From</label>
          <input type="date" id="filterDateFrom" class="filter-input">
        </div>
        
        <div class="filter-group">
          <label for="filterDateTo">Due Date To</label>
          <input type="date" id="filterDateTo" class="filter-input">
        </div>
        
        <!-- Days Left Filter -->
        <div class="filter-group">
          <label for="filterDaysLeft">Max Days Left</label>
          <input type="number" id="filterDaysLeft" class="filter-input" placeholder="e.g., 30" min="0">
        </div>
        
        <!-- Due KM Filter -->
        <div class="filter-group">
          <label for="filterDueKmMin">Min Due KM</label>
          <input type="number" id="filterDueKmMin" class="filter-input" placeholder="0" min="0">
        </div>
        
        <!-- Filter Actions -->
        <div class="filter-actions">
          <button class="btn-apply-filter" onclick="applyScheduleFilters()">
            <i class="bi bi-check-circle"></i> Apply Filters
          </button>
          <button class="btn-reset-filter" onclick="resetScheduleFilters()">
            <i class="bi bi-x-circle"></i> Reset
          </button>
        </div>
      </div>
    </div>
  `;

        // Insert filter panel after table header
        tableHeader.insertAdjacentHTML("afterend", filterPanelHTML);

        // Populate vehicle dropdown
        populateScheduleVehicleFilter();
    }

    /**
     * Populate vehicle filter dropdown
     */
    function populateScheduleVehicleFilter() {
        const filterVehicle = document.getElementById("filterVehicle");
        const vehicleCells = document.querySelectorAll(
            ".schedule-table .vehicle-cell span"
        );

        if (!filterVehicle || !vehicleCells.length) return;

        // Get unique vehicles
        const vehicles = new Set();
        vehicleCells.forEach((cell) => {
            vehicles.add(cell.textContent.trim());
        });

        // Add options to dropdown
        vehicles.forEach((vehicle) => {
            const option = document.createElement("option");
            option.value = vehicle;
            option.textContent = vehicle;
            filterVehicle.appendChild(option);
        });
    }

    /**
     * Apply filters to schedule table
     */
    function applyScheduleFilters() {
        const table = document.querySelector(".schedule-table tbody");
        if (!table) return;

        const rows = table.querySelectorAll("tr");
        let visibleCount = 0;

        // Get filter values
        const filters = {
            vehicle: document.getElementById("filterVehicle")?.value.toLowerCase() || "",
            serviceType: document.getElementById("filterServiceType")?.value.toLowerCase() || "",
            status: document.getElementById("filterStatus")?.value.toLowerCase() || "",
            priority: document.getElementById("filterPriority")?.value.toLowerCase() || "",
            dateFrom: document.getElementById("filterDateFrom")?.value || "",
            dateTo: document.getElementById("filterDateTo")?.value || "",
            daysLeft: parseInt(document.getElementById("filterDaysLeft")?.value) || Infinity,
            dueKmMin: parseFloat(document.getElementById("filterDueKmMin")?.value) || 0,
        };

        // Filter each row
        rows.forEach((row) => {
            const vehicle =
                row.querySelector(".vehicle-cell span")?.textContent.toLowerCase() || "";
            const serviceType =
                row.querySelector(".service-type")?.textContent.toLowerCase() || "";
            const statusBadge =
                row.querySelector(".status-badge")?.textContent.toLowerCase() || "";
            const priorityBadge =
                row.querySelector(".priority-badge")?.textContent.toLowerCase() || "";
            const dateText = row.cells[2]?.textContent || "";
            const dueKmText = row.cells[3]?.textContent.replace(/[,km]/g, "") || "0";
            const daysLeftText = row.querySelector(".days-left")?.textContent || "";

            // Extract days left number
            const daysLeftMatch = daysLeftText.match(/(\d+)/);
            const daysLeftValue = daysLeftMatch ? parseInt(daysLeftMatch[1]) : 0;
            const isOverdue = daysLeftText.toLowerCase().includes("overdue");

            const dueKm = parseFloat(dueKmText);
            const rowDate = convertDateToISO(dateText);

            // Check all filters
            const matchesVehicle = !filters.vehicle || vehicle.includes(filters.vehicle);
            const matchesServiceType = !filters.serviceType || serviceType.includes(filters.serviceType);
            const matchesStatus = !filters.status || statusBadge.includes(filters.status);
            const matchesPriority = !filters.priority || priorityBadge.includes(filters.priority);
            const matchesDateFrom = !filters.dateFrom || rowDate >= filters.dateFrom;
            const matchesDateTo = !filters.dateTo || rowDate <= filters.dateTo;
            const matchesDaysLeft = isOverdue ?
                false :
                daysLeftValue <= filters.daysLeft;
            const matchesDueKm = dueKm >= filters.dueKmMin;

            const isVisible =
                matchesVehicle &&
                matchesServiceType &&
                matchesStatus &&
                matchesPriority &&
                matchesDateFrom &&
                matchesDateTo &&
                matchesDaysLeft &&
                matchesDueKm;

            row.style.display = isVisible ? "" : "none";
            if (isVisible) visibleCount++;
        });

        // Show message if no results
        showFilterResults(visibleCount);
    }

    /**
     * Convert date text to ISO format
     */
    function convertDateToISO(dateText) {
        const months = {
            Jan: "01",
            Feb: "02",
            Mar: "03",
            Apr: "04",
            May: "05",
            Jun: "06",
            Jul: "07",
            Aug: "08",
            Sep: "09",
            Oct: "10",
            Nov: "11",
            Dec: "12",
        };

        const parts = dateText.split(" ");
        if (parts.length !== 3) return "";

        const month = months[parts[0]];
        const day = parts[1].replace(",", "").padStart(2, "0");
        const year = parts[2];

        return `${year}-${month}-${day}`;
    }

    /**
     * Show filter results message
     */
    function showFilterResults(count) {
        // Remove existing message
        const existingMsg = document.querySelector(".filter-result-message");
        if (existingMsg) existingMsg.remove();

        // Create new message
        const filterPanel = document.getElementById("filterPanel");
        if (!filterPanel) return;

        const message = document.createElement("div");
        message.className = "filter-result-message";
        message.style.cssText = `
    padding: 1rem;
    margin-top: 1rem;
    background: var(--bg-card);
    border: 2px solid var(--accent-color);
    border-radius: 10px;
    color: var(--accent-color);
    font-weight: 600;
    text-align: center;
  `;
        message.innerHTML = `<i class="bi bi-funnel-fill me-2"></i>Showing ${count} schedule(s)`;

        filterPanel.appendChild(message);
    }

    /**
     * Reset all filters
     */
    function resetScheduleFilters() {
        // Reset all filter inputs
        document.getElementById("filterVehicle").value = "";
        document.getElementById("filterServiceType").value = "";
        document.getElementById("filterStatus").value = "";
        document.getElementById("filterPriority").value = "";
        document.getElementById("filterDateFrom").value = "";
        document.getElementById("filterDateTo").value = "";
        document.getElementById("filterDaysLeft").value = "";
        document.getElementById("filterDueKmMin").value = "";

        // Show all rows
        const rows = document.querySelectorAll(".schedule-table tbody tr");
        rows.forEach((row) => {
            row.style.display = "";
        });

        // Remove filter result message
        const message = document.querySelector(".filter-result-message");
        if (message) message.remove();
    }

    // ========================================
    // EXPORT FUNCTIONALITY
    // ========================================

    /**
     * Export schedule table data to CSV
     */
    function exportToCSV() {
        const table = document.querySelector(".schedule-table");
        if (!table) return;

        let csv = [];
        const rows = table.querySelectorAll("tr");

        rows.forEach((row, index) => {
            const cols = row.querySelectorAll("td, th");
            const csvRow = [];

            cols.forEach((col, colIndex) => {
                // Skip Actions column (last column)
                if (colIndex === cols.length - 1 && index !== 0) return;

                let cellText = col.textContent.trim();
                // Clean up text
                cellText = cellText.replace(/\s+/g, " ");
                // Escape quotes
                cellText = cellText.replace(/"/g, '""');
                // Wrap in quotes
                csvRow.push(`"${cellText}"`);
            });

            csv.push(csvRow.join(","));
        });

        // Create CSV file
        const csvContent = csv.join("\n");
        const blob = new Blob([csvContent], {
            type: "text/csv;charset=utf-8;"
        });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);

        link.setAttribute("href", url);
        link.setAttribute("download", `maintenance_schedule_${Date.now()}.csv`);
        link.style.visibility = "hidden";

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Show success message
        showExportMessage("CSV file exported successfully!");
    }

    /**
     * Export using print dialog (PDF)
     */
    function exportToPDF() {
        window.print();
    }

    /**
     * Show export success message
     */
    function showExportMessage(message) {
        const alertDiv = document.createElement("div");
        alertDiv.className =
            "alert text-center text-success border-1 rounded-3 border-success my-3 ms-auto me-auto d-flex align-items-center justify-content-center gap-2";
        alertDiv.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
    animation: slideIn 0.3s ease-out;
  `;
        alertDiv.innerHTML = `
    <i class="bi bi-check-circle-fill"></i>
    ${message}
  `;

        document.body.appendChild(alertDiv);

        // Auto remove after 3 seconds
        setTimeout(() => {
            alertDiv.style.animation = "slideOut 0.3s ease-out";
            setTimeout(() => alertDiv.remove(), 300);
        }, 3000);
    }

    // ========================================
    // ENHANCED EXPORT BUTTON
    // ========================================

    /**
     * Create export dropdown menu
     */
    function createExportDropdown() {
        const exportBtn = document.getElementById("exportBtn");
        if (!exportBtn) return;

        // Remove default click event
        exportBtn.onclick = null;

        // Create dropdown menu
        const dropdown = document.createElement("div");
        dropdown.className = "export-dropdown";
        dropdown.style.cssText = `
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    background: var(--bg-card);
    border: 2px solid var(--border-color);
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    display: none;
    z-index: 1000;
    min-width: 180px;
  `;

        dropdown.innerHTML = `
    <button class="export-option" onclick="exportToCSV()">
      <i class="bi bi-filetype-csv"></i>
      Export as CSV
    </button>
    <button class="export-option" onclick="exportToPDF()">
      <i class="bi bi-filetype-pdf"></i>
      Export as PDF
    </button>
  `;

        // Add styles for dropdown options
        const style = document.createElement("style");
        style.textContent = `
    .export-option {
      width: 100%;
      padding: 0.875rem 1.25rem;
      background: transparent;
      border: none;
      color: var(--text-primary);
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      transition: all 0.3s ease;
      text-align: left;
    }
    
    .export-option:hover {
      background: var(--bg-secondary);
      color: var(--accent-color);
    }
    
    .export-option i {
      font-size: 1.1rem;
    }
    
    .export-option:first-child {
      border-radius: 8px 8px 0 0;
    }
    
    .export-option:last-child {
      border-radius: 0 0 8px 8px;
    }
  `;
        document.head.appendChild(style);

        // Make export button container relative
        exportBtn.parentElement.style.position = "relative";
        exportBtn.parentElement.appendChild(dropdown);

        // Toggle dropdown on click
        exportBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            dropdown.style.display =
                dropdown.style.display === "none" ? "block" : "none";
        });

        // Close dropdown when clicking outside
        document.addEventListener("click", () => {
            dropdown.style.display = "none";
        });
    }

    // ========================================
    // SCHEDULE-SPECIFIC UTILITIES
    // ========================================

    /**
     * Highlight overdue schedules on page load
     */
    function highlightOverdueSchedules() {
        const rows = document.querySelectorAll(".schedule-table tbody tr");

        rows.forEach((row) => {
            const daysLeftCell = row.querySelector(".days-left");
            if (daysLeftCell && daysLeftCell.classList.contains("overdue")) {
                row.classList.add("row-overdue");
            }
        });
    }

    /**
     * Sort table by column
     */
    function sortScheduleTable(columnIndex) {
        const table = document.querySelector(".schedule-table");
        const tbody = table.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));

        // Toggle sort direction
        const currentDirection = table.dataset.sortDirection || "asc";
        const newDirection = currentDirection === "asc" ? "desc" : "asc";
        table.dataset.sortDirection = newDirection;

        // Sort rows
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();

            // Try to parse as number
            const aNum = parseFloat(aValue.replace(/[^0-9.-]/g, ""));
            const bNum = parseFloat(bValue.replace(/[^0-9.-]/g, ""));

            if (!isNaN(aNum) && !isNaN(bNum)) {
                return newDirection === "asc" ? aNum - bNum : bNum - aNum;
            }

            // Sort as string
            return newDirection === "asc" ?
                aValue.localeCompare(bValue) :
                bValue.localeCompare(aValue);
        });

        // Reorder rows
        rows.forEach((row) => tbody.appendChild(row));
    }

    /**
     * Add sort functionality to table headers
     */
    function enableTableSorting() {
        const headers = document.querySelectorAll(".schedule-table thead th");

        headers.forEach((header, index) => {
            // Skip Actions column
            if (index === headers.length - 1) return;

            header.style.cursor = "pointer";
            header.addEventListener("click", () => sortScheduleTable(index));

            // Add sort indicator
            header.innerHTML +=
                ' <i class="bi bi-arrow-down-up" style="font-size: 0.75rem; opacity: 0.5;"></i>';
        });
    }

    // ========================================
    // INITIALIZE ON PAGE LOAD
    // ========================================

    document.addEventListener("DOMContentLoaded", () => {
        // Create export dropdown
        createExportDropdown();

        // Highlight overdue schedules
        highlightOverdueSchedules();

        // Enable table sorting
        enableTableSorting();

        // Add CSS animations
        const style = document.createElement("style");
        style.textContent = `
    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    @keyframes slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(100%);
        opacity: 0;
      }
    }
  `;
        document.head.appendChild(style);
    });
</script>
<?php include __DIR__ . "/spinner.php"; ?>
<?php include __DIR__ . "/footer.php"; ?>