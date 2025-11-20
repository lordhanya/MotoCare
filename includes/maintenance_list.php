<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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

// Edit Maintenance Record Logic
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['maintenance_id'], $_POST['vehicle_id'], $_POST['service_type'], $_POST['service_date'], $_POST['service_km'], $_POST['service_notes'], $_POST['cost'], $_POST['status'])
) {

    $maintenance_id = (int)$_POST['maintenance_id'];
    $vehicle_id = (int)$_POST['vehicle_id'];
    $service_type = trim($_POST['service_type']);
    $service_date = trim($_POST['service_date']);
    $service_km = (int)$_POST['service_km'];
    $service_notes = trim($_POST['service_notes']);
    $cost = (int)$_POST['cost'];
    $status = trim($_POST['status']);

    $check = $conn->prepare("
        SELECT m.id FROM maintenance m
        JOIN vehicles v ON m.vehicle_id = v.id
        WHERE m.id = :maintenance_id AND v.user_id = :user_id
    ");
    $check->bindParam(':maintenance_id', $maintenance_id, PDO::PARAM_INT);
    $check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $check->execute();

    if ($check->rowCount() > 0) {
        $update = $conn->prepare("
            UPDATE maintenance
            SET vehicle_id = :vehicle_id,
                service_type = :service_type,
                service_date = :service_date,
                service_km = :service_km,
                service_notes = :service_notes,
                cost = :cost,
                status = :status
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
                                                <button type="button" class="btn-close closeBtn" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
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
                                                <button type="button" class="btn-close closeBtn" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" action="" class="maintenance-form" id="maintenanceForm">
                                                    <input type="hidden" name="maintenance_id" value="<?= $row['id'] ?>">
                                                    <div class="form-grid">
                                                        <!-- Vehicle Selection -->
                                                        <div class="form-group">
                                                            <label for="vehicle_id<?= $currentVehicleId; ?>" class="form-label">
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
                                                            <label for="service_type<?= $row['id']; ?>" class="form-label">
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
                                                            rows="4"><?= htmlspecialchars($row['service_notes']); ?>
                                                            </textarea>
                                                    </div>

                                                    <div class="modal-footer mt-4">
                                                        <!-- Form Actions -->
                                                        <div class="form-actions d-flex gap-3">
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
    /**
     * ========================================
     * MAINTENANCE LIST - FILTER & EXPORT
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
     * Create filter panel dynamically
     */
    function createFilterPanel() {
        const tableCard = document.querySelector(".maintenance-list-section .container .table-card");
        const tableHeader = document.querySelector(".maintenance-list-section .container .table-header");

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
            <option value="completed">Completed</option>
            <option value="pending">Pending</option>
            <option value="scheduled">Scheduled</option>
          </select>
        </div>
        
        <!-- Date Range Filter -->
        <div class="filter-group">
          <label for="filterDateFrom">Date From</label>
          <input type="date" id="filterDateFrom" class="filter-input">
        </div>
        
        <div class="filter-group">
          <label for="filterDateTo">Date To</label>
          <input type="date" id="filterDateTo" class="filter-input">
        </div>
        
        <!-- Cost Range Filter -->
        <div class="filter-group">
          <label for="filterCostMin">Min Cost</label>
          <input type="number" id="filterCostMin" class="filter-input" placeholder="0" min="0">
        </div>
        
        <div class="filter-group">
          <label for="filterCostMax">Max Cost</label>
          <input type="number" id="filterCostMax" class="filter-input" placeholder="10000" min="0">
        </div>
        
        <!-- Odometer Range Filter -->
        <div class="filter-group">
          <label for="filterOdometerMin">Min Odometer</label>
          <input type="number" id="filterOdometerMin" class="filter-input" placeholder="0" min="0">
        </div>

        <div class="filter-group">
          <label for="filterOdometerMax">Max Odometer</label>
          <input type="number" id="filterOdometerMax" class="filter-input" placeholder="100000" min="0">
        </div>

        <!-- Filter Actions -->
        <div class="filter-actions">
          <button class="btn-apply-filter" onclick="applyFilters()">
            <i class="bi bi-check-circle"></i> Apply Filters
          </button>
          <button class="btn-reset-filter" onclick="resetFilters()">
            <i class="bi bi-x-circle"></i> Reset
          </button>
        </div>
      </div>
    </div>
  `;

        // Insert filter panel after table header
        tableHeader.insertAdjacentHTML("afterend", filterPanelHTML);

        // Populate vehicle dropdown
        populateVehicleFilter();
    }

    /**
     * Populate vehicle filter dropdown with unique vehicles from table
     */
    function populateVehicleFilter() {
        const filterVehicle = document.getElementById("filterVehicle");
        const vehicleCells = document.querySelectorAll(".vehicle-cell span");

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
     * Apply filters to table
     */
    function applyFilters() {
        const odometerMax =
            parseFloat(document.getElementById("filterOdometerMax")?.value) || Infinity;
        // ...
        const matchesOdometerMax = odometer <= odometerMax;
        // ...
        const isVisible =
            matchesVehicle &&
            matchesServiceType &&
            matchesStatus &&
            matchesDateFrom &&
            matchesDateTo &&
            matchesCostMin &&
            matchesCostMax &&
            matchesOdometerMin &&
            matchesOdometerMax;

        const table = document.querySelector(".maintenance-table tbody");
        if (!table) return;

        const rows = table.querySelectorAll("tr");
        let visibleCount = 0;

        // Get filter values
        const filters = {
            vehicle: document.getElementById("filterVehicle")?.value.toLowerCase() || "",
            serviceType: document.getElementById("filterServiceType")?.value.toLowerCase() || "",
            status: document.getElementById("filterStatus")?.value.toLowerCase() || "",
            dateFrom: document.getElementById("filterDateFrom")?.value || "",
            dateTo: document.getElementById("filterDateTo")?.value || "",
            costMin: parseFloat(document.getElementById("filterCostMin")?.value) || 0,
            costMax: parseFloat(document.getElementById("filterCostMax")?.value) || Infinity,
            odometerMin: parseFloat(document.getElementById("filterOdometerMin")?.value) || 0,
        };

        // Filter each row
        rows.forEach((row) => {
            const vehicle =
                row.querySelector(".vehicle-cell span")?.textContent.toLowerCase() || "";
            const serviceType =
                row.querySelector(".service-type")?.textContent.toLowerCase() || "";
            const statusBadge =
                row.querySelector(".status-badge")?.textContent.toLowerCase() || "";
            const dateText = row.cells[2]?.textContent || "";
            const costText =
                row.querySelector(".cost-cell")?.textContent.replace(/[₹,]/g, "") || "0";
            const odometerText = row.cells[3]?.textContent.replace(/[,km]/g, "") || "0";

            const cost = parseFloat(costText);
            const odometer = parseFloat(odometerText);
            const rowDate = convertDateToISO(dateText);

            // Check all filters
            const matchesVehicle = !filters.vehicle || vehicle.includes(filters.vehicle);
            const matchesServiceType = !filters.serviceType || serviceType.includes(filters.serviceType);
            const matchesStatus = !filters.status || statusBadge.includes(filters.status);
            const matchesDateFrom = !filters.dateFrom || rowDate >= filters.dateFrom;
            const matchesDateTo = !filters.dateTo || rowDate <= filters.dateTo;
            const matchesCostMin = cost >= filters.costMin;
            const matchesCostMax = cost <= filters.costMax;
            const matchesOdometerMin = odometer >= filters.odometerMin;

            const isVisible =
                matchesVehicle &&
                matchesServiceType &&
                matchesStatus &&
                matchesDateFrom &&
                matchesDateTo &&
                matchesCostMin &&
                matchesCostMax &&
                matchesOdometerMin;

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
        message.innerHTML = `<i class="bi bi-funnel-fill me-2"></i>Showing ${count} record(s)`;

        filterPanel.appendChild(message);
    }

    /**
     * Reset all filters
     */
    function resetFilters() {
        // Reset all filter inputs
        document.getElementById("filterVehicle").value = "";
        document.getElementById("filterServiceType").value = "";
        document.getElementById("filterStatus").value = "";
        document.getElementById("filterDateFrom").value = "";
        document.getElementById("filterDateTo").value = "";
        document.getElementById("filterCostMin").value = "";
        document.getElementById("filterCostMax").value = "";
        document.getElementById("filterOdometerMin").value = "";
        document.getElementById("filterOdometerMax").value = "";

        // Show all rows
        const rows = document.querySelectorAll(".maintenance-table tbody tr");
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
     * Export table data to CSV
     */
    function exportToCSV() {
        const table = document.querySelector(".maintenance-table");
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
        link.setAttribute("download", `maintenance_records_${Date.now()}.csv`);
        link.style.visibility = "hidden";

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Show success message
        showExportMessage("CSV file exported successfully!");
    }

    /**
     * Export table data to Excel
     */
    function exportToExcel() {
        const table = document.querySelector(".maintenance-table");
        if (!table) return;

        // Clone table to manipulate
        const clonedTable = table.cloneNode(true);

        // Remove action columns
        const headerCells = clonedTable.querySelectorAll("thead th");
        headerCells[headerCells.length - 1].remove();

        const rows = clonedTable.querySelectorAll("tbody tr");
        rows.forEach((row) => {
            const cells = row.querySelectorAll("td");
            if (cells.length > 0) {
                cells[cells.length - 1].remove();
            }
        });

        // Create workbook
        const wb = XLSX.utils.table_to_book(clonedTable, {
            sheet: "Maintenance Records",
        });

        // Export
        XLSX.writeFile(wb, `maintenance_records_${Date.now()}.xlsx`);

        showExportMessage("Excel file exported successfully!");
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
    // INITIALIZE ON PAGE LOAD
    // ========================================

    document.addEventListener("DOMContentLoaded", () => {
        // Create export dropdown
        createExportDropdown();

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