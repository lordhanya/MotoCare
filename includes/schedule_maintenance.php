<?php
session_start();
include __DIR__ . "/../db/connection.php";

$pageTitle = "Schedule Maintenance | MotoCare";

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
    $scheduled_service_type = trim($_POST['scheduled_service_type']);
    $due_date = !empty($_POST['due_date']) ? trim($_POST['due_date']) : null;
    $due_km = isset($_POST['due_km']) ? (int)$_POST['due_km'] : null;
    $status = trim($_POST['status']);

    $insert = $conn->prepare("INSERT INTO maintenance_schedule (vehicle_id, scheduled_service_type, due_date, due_km, status) VALUES (:vehicle_id, :scheduled_service_type, :due_date, :due_km, :status)");
    $insert->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
    $insert->bindParam(':scheduled_service_type', $scheduled_service_type, PDO::PARAM_STR);
    $insert->bindParam(':due_date', $due_date, PDO::PARAM_STR);
    $insert->bindParam(':due_km', $due_km, PDO::PARAM_INT);
    $insert->bindParam(':status', $status, PDO::PARAM_STR);

    if ($insert->execute()) {
        echo "<script>alert('Maintenance schedule added successfully!'); window.location.href='schedule_list.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to add maintenance schedule. Please try again.');</script>";
    }
}

include __DIR__ . "/header.php";
include __DIR__ . "/dashNav.php";
include __DIR__ . "/sidebar.php";
?>

<section class="schedule-maintenance-section">
    <div class="container min-vh-100">
        <div class="row">
            <div class="col-12">
                <div class="page-header d-grid">
                    <h2>Add <span>Maintenance</span> Schedule</h2>
                    <p class="subtitle">Keep track of your vehicle's service history</p>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-8 col-md-10">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <div class="form-container d-flex align-items-center justify-content-center">
                    <form method="POST" action="" class="schedule_form" id="scheduleForm">
                        <div class="form-grid">
                            <!-- vehicle selection -->
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

                            <!-- Scheduled Service Type -->
                            <div class="form-group">
                                <label for="scheduled_service_type" class="form-label">
                                    <i class="bi bi-wrench-adjustable me-2"></i>Scheduled Service Type
                                </label>
                                <input type="text"
                                    name="scheduled_service_type"
                                    id="scheduled_service_type"
                                    class="form-control"
                                    placeholder="e.g., Oil Change, Brake-Oil Change"
                                    required>
                            </div>

                            <!-- Due Date -->
                            <div class="form-group">
                                <label for="due_date" class="form-label">
                                    <i class="bi bi-calendar-event me-2"></i>Due Date
                                </label>
                                <input type="date"
                                    name="due_date"
                                    id="due_date"
                                    class="form-control">
                            </div>

                            <!-- Due KM -->
                            <div class="form-group">
                                <label for="due_km" class="form-label">
                                    <i class="bi bi-speedometer2 me-2"></i>Due KM
                                </label>
                                <input type="number"
                                    name="due_km"
                                    id="due_km"
                                    class="form-control"
                                    min="0"
                                    placeholder="e.g., 60000">
                            </div>

                            <!-- Status -->
                            <div class="form-group">
                                <label for="status" class="form-label">
                                    <i class="bi bi-info-circle-fill me-2"></i>Status
                                </label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="pending" selected>Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="missed">Missed</option>
                                </select>
                            </div>
                        </div>
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-submit">
                                <i class="bi bi-check-circle-fill me-2"></i>Submit
                            </button>
                            <a href="schedule_list.php" class="btn btn-cancel">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/spinner.php";?>
<?php include __DIR__ . "/footer.php"; ?>