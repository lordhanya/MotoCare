<?php
session_start();
include "../db/connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's vehicles for dropdown
$stmt = $conn->prepare("SELECT id, vehicle_name FROM vehicles WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $service_type = trim($_POST['service_type']);
    $service_date = trim($_POST['service_date']);
    $service_km = isset($_POST['service_km']) ? (int)$_POST['service_km'] : null;
    $service_notes = trim($_POST['service_notes']);
    $cost = isset($_POST['cost']) ? floatval($_POST['cost']) : 0.00;
    $status = trim($_POST['status']);

    $insert = $conn->prepare("INSERT INTO maintenance (vehicle_id, service_type, service_date, service_km, service_notes, cost, status) VALUES (:vehicle_id, :service_type, :service_date, :service_km, :service_notes, :cost, :status)");
    $insert->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
    $insert->bindParam(':service_type', $service_type, PDO::PARAM_STR);
    $insert->bindParam(':service_date', $service_date, PDO::PARAM_STR);
    $insert->bindParam(':service_km', $service_km, PDO::PARAM_INT);
    $insert->bindParam(':service_notes', $service_notes, PDO::PARAM_STR);
    $insert->bindParam(':cost', $cost);
    $insert->bindParam(':status', $status, PDO::PARAM_STR);

    if ($insert->execute()) {
        $_SESSION['success_message'] = "Maintenance record added successfully!";
        header("Location: maintenance_list.php");
        exit();
    } else {
        $error_message = "Failed to add maintenance record. Please try again.";
    }
}

include __DIR__ . "/header.php";
include __DIR__ . "/dashNav.php";
include __DIR__ . "/sidebar.php";
?>

<section class="add-maintenance-section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="page-header">
                    <h2>Add <span>Maintenance</span> Record</h2>
                    <p class="subtitle">Keep track of your vehicle's service history</p>
                </div>
            </div>
        </div>

        <div class="row justify-content-center mt-4">
            <div class="col-lg-8 col-md-10">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" action="" class="maintenance-form">
                        <div class="form-grid">
                            <!-- Vehicle Selection -->
                            <div class="form-group">
                                <label for="vehicle_id" class="form-label">
                                    <i class="bi bi-car-front-fill me-2"></i>Vehicle
                                </label>
                                <select name="vehicle_id" id="vehicle_id" class="form-select" required>
                                    <option value="" disabled selected>Select Vehicle</option>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?= $vehicle['id'] ?>">
                                            <?= htmlspecialchars($vehicle['vehicle_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Service Type -->
                            <div class="form-group">
                                <label for="service_type" class="form-label">
                                    <i class="bi bi-wrench-adjustable me-2"></i>Service Type
                                </label>
                                <input type="text"
                                    name="service_type"
                                    id="service_type"
                                    class="form-control"
                                    placeholder="e.g., Oil Change, Tire Rotation"
                                    required>
                            </div>

                            <!-- Service Date -->
                            <div class="form-group">
                                <label for="service_date" class="form-label">
                                    <i class="bi bi-calendar-event me-2"></i>Service Date
                                </label>
                                <input type="date"
                                    name="service_date"
                                    id="service_date"
                                    class="form-control"
                                    required>
                            </div>

                            <!-- Service KM -->
                            <div class="form-group">
                                <label for="service_km" class="form-label">
                                    <i class="bi bi-speedometer2 me-2"></i>Service Odometer (KM)
                                </label>
                                <input type="number"
                                    name="service_km"
                                    id="service_km"
                                    class="form-control"
                                    placeholder="e.g., 50000"
                                    min="0">
                            </div>

                            <!-- Cost -->
                            <div class="form-group">
                                <label for="cost" class="form-label">
                                    <i class="bi bi-currency-rupee me-2"></i>Cost
                                </label>
                                <input type="number"
                                    name="cost"
                                    id="cost"
                                    class="form-control"
                                    placeholder="0.00"
                                    min="0"
                                    step="0.01"
                                    value="0.00">
                            </div>

                            <!-- Status -->
                            <div class="form-group">
                                <label for="status" class="form-label">
                                    <i class="bi bi-check-circle me-2"></i>Status
                                </label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="completed" selected>Completed</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                        </div>

                        <!-- Service Notes (Full Width) -->
                        <div class="form-group full-width">
                            <label for="service_notes" class="form-label">
                                <i class="bi bi-file-text me-2"></i>Service Notes
                            </label>
                            <textarea name="service_notes"
                                id="service_notes"
                                class="form-control"
                                rows="4"
                                placeholder="Add any additional notes about the service..."></textarea>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-submit">
                                <i class="bi bi-check-circle-fill me-2"></i>Add Maintenance
                            </button>
                            <a href="maintenance_list.php" class="btn btn-cancel">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/footer.php"; ?>