<?php

session_start();
include "header.php";
include "../db/connection.php";

if (isset($_SESSION['login_success'])) {
    echo "<script>alert('Login Successful!');</script>";
    unset($_SESSION['login_success']);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include __DIR__ . "/sidebar.php";
include __DIR__ . "/dashNav.php";

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $vehicle_name = trim($_POST['vehicle_name']);
    $model = trim($_POST['model']);
    $registration_no = trim($_POST['registration_no']);
    $vehicle_type = trim($_POST['vehicle_type']);
    $purchase_date = trim($_POST['purchase_date']);
    $current_km = (int)$_POST['current_km'];
    $last_service_date = !empty($_POST['last_service_date']) ? trim($_POST['last_service_date']) : null;
    $last_service_km = !empty($_POST['last_service_km']) ? (int)$_POST['last_service_km'] : null;
    $next_service_date = trim($_POST['next_service_date']);
    $next_service_km = (int)$_POST['next_service_km'];


    $insert = $conn->prepare("INSERT INTO vehicles (user_id, vehicle_name, model, vehicle_type, registration_no, purchase_date, current_km, last_service_date, last_service_km, next_service_date, next_service_km) VALUES (:user_id, :vehicle_name, :model, :vehicle_type, :registration_no, :purchase_date, :current_km, :last_service_date, :last_service_km, :next_service_date, :next_service_km)");
    $insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $insert->bindParam(':vehicle_name', $vehicle_name, PDO::PARAM_STR);
    $insert->bindParam(':model', $model, PDO::PARAM_STR);
    $insert->bindParam(':vehicle_type', $vehicle_type, PDO::PARAM_STR);
    $insert->bindParam(':registration_no', $registration_no, PDO::PARAM_STR);
    $insert->bindParam(':purchase_date', $purchase_date, PDO::PARAM_STR);
    $insert->bindParam(':current_km', $current_km, PDO::PARAM_INT);
    $insert->bindParam(':last_service_date', $last_service_date, PDO::PARAM_STR);
    $insert->bindParam(':last_service_km', $last_service_km, PDO::PARAM_INT);
    $insert->bindParam(':next_service_date', $next_service_date, PDO::PARAM_STR);
    $insert->bindParam(':next_service_km', $next_service_km, PDO::PARAM_INT);

    if ($insert->execute()) {
        echo "<script>alert('Vehicle added successfully! Redirecting to your dashboard...'); 
        window.location.href='dashboard.php';</script>";
        exit();
    } else {
        echo "<script>alert('Vehicle addition failed. Please try again.');</script>";
    }
}
?>

<section class="add_vehicle">
    <div class="container d-flex align-items-center justify-content-center">
        <div class="row align-items-center">
            <div class="col">
                <div class="heading d-flex align-items-center justify-content-center mb-5">
                    <h2>Add your <span>vehicle</span> and its <span>details</span></h2>
                </div>

                <div class="vehicleForm-container">
                    <form action="" method="POST" class="vehicleForm">
                        <!-- Vehicle Name -->
                        <label>
                            <input class="input" name="vehicle_name" type="text" required>
                            <span>Vehicle Name</span>
                        </label>

                        <!-- Model -->
                        <label>
                            <input class="input" name="model" type="text" required>
                            <span>Model</span>
                        </label>

                        <!-- Vehicle Type -->
                        <label>
                            <input class="input" name="vehicle_type" type="text" required>
                            <span>Vehicle Type (Car, Bike, Scooter, etc.)</span>
                        </label>

                        <!-- Registration Number -->
                        <label>
                            <input class="input" name="registration_no" type="text" required>
                            <span>Registration Number</span>
                        </label>

                        <!-- Purchase Date -->
                        <label>
                            <input class="input" id="purchaseDate" name="purchase_date" type="date" required>
                            <span>Purchase Date</span>
                        </label>

                        <!-- Current KM -->
                        <label>
                            <input class="input" name="current_km" type="number" min="0" required>
                            <span>Current Odometer Reading (KM)</span>
                        </label>

                        <!-- Last Service Date (Optional) -->
                        <label>
                            <input class="input" name="last_service_date" type="date">
                            <span>Last Service Date (if any)</span>
                        </label>

                        <!-- Last Service KM (Optional) -->
                        <label>
                            <input class="input" name="last_service_km" type="number" min="0">
                            <span>Odometer at Last Service, if any (KM)</span>
                        </label>

                        <!-- Next Service Date -->
                        <label>
                            <input class="input" id="nextServiceDate" name="next_service_date" type="date" required>
                            <span>Next Service Date</span>
                        </label>

                        <!-- Next Service KM -->
                        <label>
                            <input class="input" name="next_service_km" type="number" min="0" required>
                            <span>Next Service Odometer (KM)</span>
                        </label>

                        <!-- Submit -->
                        <div class="submitBtn mt-2 d-flex align-items-center justify-content-center">
                            <button class="btn submit px-5" type="submit">Submit</button>
                        </div>
                    </form>


                </div>
            </div>
        </div>
    </div>
</section>

<?php include "footer.php"; ?>