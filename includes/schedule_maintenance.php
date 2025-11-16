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
?>

<h2>Add Maintenance Schedule</h2>
<form method="POST" action="">
    <label>Vehicle:
        <select name="vehicle_id" required>
            <option value="" disabled selected>Select Vehicle</option>
            <?php foreach ($vehicles as $vehicle): ?>
                <option value="<?= $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['vehicle_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <br>

    <label>Scheduled Service Type:
        <input type="text" name="scheduled_service_type" required>
    </label>
    <br>

    <label>Due Date:
        <input type="date" name="due_date">
    </label>
    <br>

    <label>Due KM:
        <input type="number" name="due_km" min="0">
    </label>
    <br>

    <label>Status:
        <select name="status" required>
            <option value="pending" selected>Pending</option>
            <option value="completed">Completed</option>
            <option value="missed">Missed</option>
        </select>
    </label>
    <br>

    <button type="submit">Add Schedule</button>
</form>
