<?php

session_start();

include __DIR__ . "/../db/connection.php";

$pageTitle = "Add Vehicle | MotoCare";

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
        // Get the last inserted vehicle ID
        $vehicle_id = $conn->lastInsertId();

        // Fetch user details for reminders
        $user_stmt = $conn->prepare("SELECT email, first_name, last_name FROM users WHERE id = :user_id");
        $user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        $email = $user['email'];
        $first_name = $user['first_name'];
        $last_name = $user['last_name'];

        // Compose reminder message
        $msg = "Your vehicle is due for its next maintenance on $next_service_date.";

        // Calculate reminder date (e.g., 7 days before next service date)
        $reminder_date = date('Y-m-d', strtotime("$next_service_date -7 days"));

        // Check if reminder already exists for 'next_service'
        $reminder_check = $conn->prepare("SELECT id FROM reminders WHERE vehicle_id = :vehicle_id AND reminder_type = 'next_service' AND is_sent = 0");
        $reminder_check->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
        $reminder_check->execute();

        if ($reminder_check->rowCount() > 0) {
            // Update existing reminder
            $update = $conn->prepare("UPDATE reminders SET reminder_date = :reminder_date, message = :message WHERE vehicle_id = :vehicle_id AND reminder_type='next_service' AND is_sent=0");
            $update->bindParam(':reminder_date', $reminder_date, PDO::PARAM_STR);
            $update->bindParam(':message', $msg, PDO::PARAM_STR);
            $update->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
            $update->execute();
        } else {

            $insert_reminder = $conn->prepare("INSERT INTO reminders (user_id, user_email, vehicle_id, vehicle_name, first_name, last_name, reminder_type, reminder_date, message) VALUES (:user_id, :user_email, :vehicle_id, :vehicle_name, :first_name, :last_name, 'next_service', :reminder_date, :message)");
            $insert_reminder->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $insert_reminder->bindParam(':user_email', $email, PDO::PARAM_STR);
            $insert_reminder->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
            $insert_reminder->bindParam(':vehicle_name', $vehicle_name, PDO::PARAM_STR);
            $insert_reminder->bindParam(':first_name', $first_name, PDO::PARAM_STR);
            $insert_reminder->bindParam(':last_name', $last_name, PDO::PARAM_STR);
            $insert_reminder->bindParam(':reminder_date', $reminder_date, PDO::PARAM_STR);
            $insert_reminder->bindParam(':message', $msg, PDO::PARAM_STR);
            $insert_reminder->execute();
        }


        echo "<script>alert('Vehicle added successfully! Redirecting to your vehicles page...'); window.location.href='vehicles.php';</script>";
        exit();
    } else {
        echo "<script>alert('Vehicle addition failed. Please try again.');</script>";
    }
}
include __DIR__ . "/header.php";
?>

<section class="add_vehicle">
    <div class="container d-flex align-items-center justify-content-center">
        <div class="row">
            <div class="col">
                <div class="heading mb-5">
                    <h2>Add your <span>vehicle</span> and its <span>details</span></h2>
                </div>

                <div class="vehicleForm-container d-flex align-items-center justify-content-center">
                    <form action="" method="POST" class="vehicleForm">
                        <!-- Vehicle Name -->
                        <label for="vehicleName">
                            <input class="input" name="vehicle_name" type="text" placeholder="" required>
                            <span>Vehicle Name</span>
                        </label>

                        <!-- Model -->
                        <label for="model">
                            <input class="input" id="model" name="model" type="text" placeholder="" required>
                            <span>Model</span>
                        </label>

                        <!-- Vehicle Type -->
                        <label for="vehicle_type">
                            <input class="input" id="vehicle_type" name="vehicle_type" type="text" placeholder="" required>
                            <span>Vehicle Type</span>
                        </label>

                        <!-- Registration Number -->
                        <label for="registration_no">
                            <input class="input" id="registration_no" placeholder="" name="registration_no" type="text" required>
                            <span>Registration Number</span>
                        </label>

                        <!-- Purchase Date -->
                        <label for="purchaseDate">
                            <input class="input" id="purchaseDate" name="purchase_date" type="date" required>
                            <span>Purchase Date</span>
                        </label>

                        <!-- Current KM -->
                        <label for="current_km">
                            <input class="input" id="current_km" placeholder="" name="current_km" type="number" min="0" required>
                            <span>Current Odometer Reading (KM)</span>
                        </label>

                        <!-- Last Service Date (Optional) -->
                        <label for="last_service_date">
                            <input class="input" id="last_service_date" name="last_service_date" type="date">
                            <span>Last Service Date (Optional)</span>
                        </label>

                        <!-- Last Service KM (Optional) -->
                        <label for="last_service_km">
                            <input class="input" id="last_service_km" name="last_service_km" type="number" min="0">
                            <span>Last Service KM (optional)</span>
                        </label>

                        <!-- Next Service Date -->
                        <label for="nextServiceDate">
                            <input class="input" id="nextServiceDate" name="next_service_date" type="date" required>
                            <span>Next Service Date</span>
                        </label>

                        <!-- Next Service KM -->
                        <label for="next_service_km">
                            <input class="input" id="next_service_km" placeholder="" name="next_service_km" type="number" min="0" required>
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
<?php include __DIR__ . "/spinner.php"; ?>
<?php include __DIR__ . "/footer.php"; ?>