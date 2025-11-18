<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Edit Schedule
if (isset($_POST['vehicle_id'], $_POST['maintenance_id'])) {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $service_type = (int)$_POST['service_type'];
    $service_date = trim($_POST['service_date'] ?? '');
    $service_km = isset($_POST['service_km']) ? (int)$_POST['service_km'] : 0;
    $service_notes = trim($_POST['service_notes'] ?? '');
    $cost = isset($_POST['cost']) ? (int)$_POST['due_km'] : 0;
    $status = trim($_POST['status'] ?? '');

    $check = $conn->prepare("
        SELECT m.id FROM maintenance m
        JOIN vehicles v ON m.vehicle_id = v.id
        WHERE m.id = :maintenance_id AND v.user_id = :user_id
    ");
    $check->bindParam(':maintenance_id', $maintenance_id, PDO::PARAM_INT);
    $check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $check->execute();

    if ($check->rowCount() > 0) {
        $update = $conn->prepare("UPDATE maintenance_schedule 
            SET vehicle_id = :vehicle_id, service_type = :service_type, 
                service_date = :service_date, service_km = :service_km, service_notes = :service_notes, cost = :cost, status = :status 
            WHERE id = :maintenance_id
        ");
        $update->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
        $update->bindParam(':service_type', $service_type, PDO::PARAM_STR);
        $update->bindParam(':service_date', $service_date, PDO::PARAM_STR);
        $update->bindParam(':service_km', $service_km, PDO::PARAM_INT);
        $update->bindParam(':service_notes', $service_notes, PDO::PARAM_STR);
        $update->bindParam(':cost', $cost, PDO::PARAM_INT);
        $update->bindParam(':status', $status, PDO::PARAM_STR);
        $update->bindParam(':maintenance_id', $maintenance_id, PDO::PARAM_INT);

        if ($update->execute()) {
            $_SESSION['message'] = "Your maintenance record has been updated!";
        } else {
            $_SESSION['message'] = "Failed to update your maintenance record!";
        }
    } else {
        $_SESSION['message'] = "Maintenance record was not found or access denied.";
    }
}

// DELETE RECORD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maintenance_id'], $_POST['action']) && $_POST['action'] === 'delete') {
    $maintenance_id = (int)$_POST['maintenance_id'];

    $check = $conn->prepare("
        SELECT m.id FROM maintenance m
        JOIN vehicles v ON m.vehicle_id = v.id
        WHERE m.id = :mid AND v.user_id = :uid
    ");
    $check->bindParam(':mid', $maintenance_id, PDO::PARAM_INT);
    $check->bindParam(':uid', $user_id, PDO::PARAM_INT);
    $check->execute();
    $exists = $check->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $delete = $conn->prepare("DELETE FROM maintenance WHERE id = :mid");
        $delete->bindParam(':mid', $maintenance_id, PDO::PARAM_INT);
        $delete->execute();

        if ($delete->execute()) {
            $_SESSION['message'] = "Record deleted successfully!";
        } else {
            $_SESSION['message'] = "Failed to delete your record!";
        }
    } else {
        $_SESSION['message'] = "Record not found or access denied.";
    }
}

// Fetch maintenance records joined with vehicle data
$sql = "SELECT m.*, v.vehicle_name 
FROM maintenance m 
JOIN vehicles v ON m.vehicle_id = v.id 
WHERE v.user_id = :user_id
ORDER BY m.service_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

//seperate query for vehicle data
$vehiclesStmt = $conn->prepare("SELECT id, vehicle_name FROM vehicles WHERE user_id = :user_id");
$vehiclesStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$vehiclesStmt->execute();
$vehicles = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . "/header.php";
include __DIR__ . "/dashNav.php";
include __DIR__ . "/sidebar.php";
?>

<section class="maintenance-list-section">
    <div class="container">
        <div class="no-print">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-content">
                    <h2>Maintenance Records</h2>
                    <p>Track and manage all your vehicle maintenance history</p>
                </div>
                <div class="header-actions">
                    <a href="add_maintenance.php" class="btn-add-maintenance">
                        <i class="bi bi-plus-circle"></i>
                        Add New Record
                    </a>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="stats-overview">
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="bi bi-card-checklist"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-label">Total Records</span>
                        <span class="stat-value"><?= count($records) ?></span>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-label">Total Spent</span>
                        <span class="stat-value">₹<?= number_format(array_sum(array_column($records, 'cost')), 2) ?></span>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-label">Last Service</span>
                        <span class="stat-value"><?= $records ? date('M d, Y', strtotime($records[0]['service_date'])) : 'N/A' ?></span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Alert -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert text-center text-success rounded-3 border-1 border-suscess my-3 ms-auto me-auto d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-bell"></i>
                <?php
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>
        <!-- Maintenance Table Card -->
        <div class="table-card">
            <div class="table-header">
                <h3>Service History</h3>
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
                        document.getElementById("exportBtn").addEventListener("click", function() {
                            window.print();
                        });
                    </script>
                </div>
            </div>

            <?php if ($records && count($records) > 0): ?>
                <div class="table-responsive">
                    <table class="maintenance-table">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Service Type</th>
                                <th>Date</th>
                                <th>Odometer (KM)</th>
                                <th>Cost</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $row):
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

                                // Determine service type icon
                                $serviceIcon = '';
                                switch (strtolower($row['service_type'])) {
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
                            ?>
                                <tr>
                                    <td>
                                        <div class="vehicle-cell">
                                            <i class="bi bi-caret-right-fill"></i>
                                            <span><?= htmlspecialchars($row['vehicle_name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="service-type">
                                            <i class="bi <?= $serviceIcon ?>"></i>
                                            <?= htmlspecialchars($row['service_type']); ?>
                                        </div>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['service_date'])); ?></td>
                                    <td><?= number_format($row['service_km']); ?> km</td>
                                    <td class="cost-cell">₹<?= number_format($row['cost'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="notes-cell">
                                            <?php
                                            $notes = htmlspecialchars($row['service_notes']);
                                            echo strlen($notes) > 50 ? substr($notes, 0, 50) . '...' : $notes;
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-view" title="View Details" data-bs-toggle="modal" data-bs-target="#viewDetailsModal<?= $row['id'] ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn-icon btn-edit" title="Edit" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this maintenance record?');">
                                                <input type="hidden" name="maintenance_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn-icon btn-delete" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal view for maintenance list -->
                                <div class="modal fade" id="viewDetailsModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content p-3">
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5" id="editModalLabel<?php echo $row['id']; ?>"><span class="text-danger"><?php echo htmlspecialchars($row['vehicle_name']); ?></span> Details</h1>
                                                <button type="button" class="btn-close closeBtn" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <ul>
                                                    <li><strong>SERVICE TYPE:</strong> <?php echo htmlspecialchars($row['service_type']); ?></li>
                                                    <li><strong>SERVICE DATE:</strong> <?php echo htmlspecialchars($row['service_date']); ?></li>
                                                    <li><strong>ODOMETER (KM):</strong> <?php echo htmlspecialchars($row['service_km']); ?></li>
                                                    <li><strong>COST:</strong> <?php echo htmlspecialchars($row['service_notes']); ?></li>
                                                    <li><strong>STATUS:</strong> <?php echo htmlspecialchars($row['cost']); ?></li>
                                                    <li><strong>NOTES:</strong> <?php echo htmlspecialchars($row['status']); ?></li>
                                                    <li><strong>CREATED AT:</strong> <?php echo htmlspecialchars($row['created_at']); ?></li>
                                                </ul>
                                            </div>
                                            <div class="modal-footer d-flex align-items-center justify-content-center gap-3">
                                                <button class="btn editBtn btn-danger px-4 py-2 d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $vehicle['id']; ?>" data-bs-dismiss="modal">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-secondary px-3 py-2 d-flex align-items-center justify-content-center gap-2" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal edit for maintenance list -->
                                <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content p-3">
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5" id="editModalLabel<?php echo $row['id']; ?>">Edit <span class="text-danger"><?php echo htmlspecialchars($row['vehicle_name']); ?></span> Maintenance Record</h1>
                                                <button type="button" class="btn-close closeBtn" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" action="" class="maintenance-form" id="maintenanceForm">
                                                    <input type="hidden" name="maintenance_id" value="<?= $row['id'] ?>">
                                                    <div class="form-grid">
                                                        <!-- Vehicle Selection -->
                                                        <div class="form-group">
                                                            <label for="vehicle_id<?= $currentVehicleId; ?>" class="text-dark form-label">
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

                                                        <!-- Service Type -->
                                                        <div class="form-group">
                                                            <label for="service_type<?= $row['id']; ?>" class="form-label text-dark">
                                                                <i class="bi bi-wrench-adjustable me-2"></i>Service Type
                                                            </label>
                                                            <input type="text"
                                                                name="service_type"
                                                                id="service_type<?= $row['id']; ?>"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($row['service_type']) ?>"
                                                                required>
                                                        </div>

                                                        <!-- Service Date -->
                                                        <div class="form-group">
                                                            <label for="service_date<?= $row['id']; ?>" class="form-label">
                                                                <i class="bi bi-calendar-event me-2"></i>Service Date
                                                            </label>
                                                            <input type="date"
                                                                name="service_date"
                                                                id="service_date<?= $row['id']; ?>"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($row['service_date']); ?>"
                                                                required>
                                                        </div>

                                                        <!-- Service KM -->
                                                        <div class="form-group">
                                                            <label for="service_km<?= $row['id']; ?>" class="form-label">
                                                                <i class="bi bi-speedometer2 me-2"></i>Service Odometer (KM)
                                                            </label>
                                                            <input type="number"
                                                                name="service_km"
                                                                id="service_km<?= $row['id']; ?>"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($row['service_km']); ?>"
                                                                min="0">
                                                        </div>

                                                        <!-- Cost -->
                                                        <div class="form-group">
                                                            <label for="cost<?= $row['id']; ?>" class="form-label">
                                                                <i class="bi bi-currency-rupee me-2"></i>Cost
                                                            </label>
                                                            <input type="number"
                                                                name="cost"
                                                                id="cost<?= $row['id']; ?>"
                                                                class="form-control"
                                                                min="0"
                                                                step="0.01"
                                                                value="<?= htmlspecialchars($row['cost']); ?>">
                                                        </div>

                                                        <!-- Status -->
                                                        <div class="form-group">
                                                            <label for="status<?= $row['id']; ?>" class="form-label">
                                                                <i class="bi bi-check-circle me-2"></i>Status
                                                            </label>
                                                            <select name="status" id="status<?= $row['id']; ?>" class="form-select" required>
                                                                <option value="completed" selected>Completed</option>
                                                                <option value="scheduled">Scheduled</option>
                                                                <option value="pending">Pending</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <!-- Service Notes (Full Width) -->
                                                    <div class="form-group full-width">
                                                        <label for="service_notes<?= $row['id']; ?>" class="form-label">
                                                            <i class="bi bi-file-text me-2"></i>Service Notes
                                                        </label>
                                                        <textarea name="service_notes"
                                                            id="service_notes<?= $row['id']; ?>"
                                                            class="form-control"
                                                            rows="4"></textarea>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <!-- Form Actions -->
                                                        <div class="form-actions">
                                                            <button type="submit" class="btn btn-submit">
                                                                <i class="bi bi-check-circle-fill me-2"></i>Submit
                                                            </button>
                                                            <a href="maintenance_list.php" class="btn btn-cancel">
                                                                <i class="bi bi-x-circle me-2"></i>Cancel
                                                            </a>
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
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h3>No Maintenance Records Yet</h3>
                    <p>Start tracking your vehicle maintenance by adding your first service record</p>
                    <a href="add_maintenance.php" class="btn-add-first">
                        <i class="bi bi-plus-circle"></i>
                        Add First Record
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
    function toggleFilters() {
        alert('Filter functionality to be implemented');
    }
</script>
<?php include __DIR__ . "/spinner.php"; ?>
<?php include __DIR__ . "/footer.php"; ?>