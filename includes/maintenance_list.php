<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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
        } 
        else {
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
                                            <button class="btn-icon btn-view" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn-icon btn-edit" title="Edit">
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