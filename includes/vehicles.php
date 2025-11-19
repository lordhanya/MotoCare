<?php
session_start();
include __DIR__ . "/header.php";
include __DIR__ . "/../db/connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vehicle_id'])) {
    error_log("Delete request received for vehicle_id: {$_POST['vehicle_id']}");

    $vehicle_id = (int)$_POST['vehicle_id'];
    $action = $_POST['action'] ?? 'update';

    if (isset($_POST['vehicle_id']) && isset($_POST['vehicle_name']) && !empty($_POST['vehicle_name'])) {


        $vehicle_id = (int)$_POST['vehicle_id'];
        $vehicle_name = trim($_POST['vehicle_name']);

        $model = trim($_POST['model'] ?? '');
        $vehicle_type = trim($_POST['vehicle_type'] ?? '');
        $registration_no = trim($_POST['registration_no'] ?? '');
        $current_km = isset($_POST['current_km']) ? (int)$_POST['current_km'] : 0;
        $purchase_date = trim($_POST['purchase_date'] ?? '');
        $last_service_date = trim($_POST['last_service_date'] ?? '');
        $last_service_km = isset($_POST['last_service_km']) ? (int)$_POST['last_service_km'] : null;
        $next_service_date = trim($_POST['next_service_date'] ?? '');
        $next_service_km = isset($_POST['next_service_km']) ? (int)$_POST['next_service_km'] : 0;

        $check = $conn->prepare("SELECT id FROM vehicles WHERE id = :vehicle_id AND user_id = :user_id");
        $check->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
        $check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $check->execute();

        if ($check->rowCount() > 0) {
            $update = $conn->prepare("UPDATE vehicles SET vehicle_name = :vehicle_name, model = :model, vehicle_type = :vehicle_type, registration_no = :registration_no, current_km = :current_km, purchase_date = :purchase_date, last_service_date = :last_service_date, last_service_km = :last_service_km, next_service_date = :next_service_date, next_service_km = :next_service_km WHERE id = :vehicle_id AND user_id = :user_id");
            $update->bindParam(':vehicle_name', $vehicle_name, PDO::PARAM_STR);
            $update->bindParam(':model', $model, PDO::PARAM_STR);
            $update->bindParam(':vehicle_type', $vehicle_type, PDO::PARAM_STR);
            $update->bindParam(':registration_no', $registration_no, PDO::PARAM_STR);
            $update->bindParam(':current_km', $current_km, PDO::PARAM_INT);
            $update->bindParam(':purchase_date', $purchase_date, PDO::PARAM_STR);
            $update->bindParam(':last_service_date', $last_service_date, PDO::PARAM_STR);
            $update->bindParam(':last_service_km', $last_service_km, PDO::PARAM_INT);
            $update->bindParam(':next_service_date', $next_service_date, PDO::PARAM_STR);
            $update->bindParam(':next_service_km', $next_service_km, PDO::PARAM_INT);
            $update->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
            $update->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($update->execute()) {
                $_SESSION['message'] = "Vehicle updated successfully!";
            } else {
                $_SESSION['message'] = "Failed to update vehicle.";
            }
        } else {
            $_SESSION['message'] = "Vehicle not found or access denied.";
        }

        header("Location: vehicles.php");
        exit();
    } elseif ($action === "delete") {
        $check = $conn->prepare("SELECT id FROM vehicles WHERE id = :vehicle_id AND user_id = :user_id");
        $check->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
        $check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $check->execute();

        if ($check->rowCount() > 0) {
            $delete = $conn->prepare("DELETE FROM vehicles WHERE id = :vehicle_id AND user_id = :user_id");
            $delete->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
            $delete->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            if ($delete->execute()) {
                $_SESSION['message'] = "Vehicle deleted successfully!";
            } else {
                $_SESSION['message'] = "Failed to delete vehicle!";
            }
        } else {
            $_SESSION['message'] = "Vehicle not found or access denied.";
        }
        header("Location: vehicles.php");
        exit();
    }
}



$stmt = $conn->prepare("SELECT * FROM vehicles WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . "/sidebar.php";
include __DIR__ . "/dashNav.php";
?>

<section class="vehicles text-white py-5 px-3">
    <div class="container">
        <h2>Your <span>Vehicles</span></h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert text-center my-3 ms-auto me-auto d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-bell"></i>
                <?php
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="row d-flex">
            <?php if ($vehicles && count($vehicles) > 0): ?>
                <?php foreach ($vehicles as $vehicle):
                    switch (strtolower($vehicle['vehicle_type'])) {
                        case "car":
                            $image = "../assets/images/car_model.png";
                            break;
                        case "bike":
                            $image = "../assets/images/bike_model.png";
                            break;
                        case "scooter":
                            $image = "../assets/images/scooter_model.png";
                            break;
                        default:
                            $image = "../assets/images/default.png";
                    }
                ?>
                    <div class="col-md-4">
                        <div class="vehicle-card">
                            <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($vehicle['vehicle_type']); ?>" style="width: 18rem; height: 10rem; object-fit: contain;">
                            <hr>
                            <div class="card-body mt-3">
                                <h4><?php echo htmlspecialchars($vehicle['vehicle_name']); ?></h4>
                                <p>Model: <?php echo htmlspecialchars($vehicle['model']); ?></p>
                                <p>Type: <?php echo htmlspecialchars($vehicle['vehicle_type']); ?></p>
                                <p>Registration No: <?php echo htmlspecialchars($vehicle['registration_no']); ?></p>
                                <p>Current KM: <?php echo htmlspecialchars($vehicle['current_km']); ?></p>

                                <div class="d-flex align-items-center justify-content-center gap-5 mt-3">
                                    <button type="button" class="btn detailsBtn d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $vehicle['id']; ?>">
                                        <i class="bi bi-list-ul"></i> Details
                                    </button>

                                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this vehicle?');">
                                        <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn deleteBtn d-flex align-items-center justify-content-center gap-2">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for vehicle details -->
                    <div class="modal fade" id="detailsModal<?php echo $vehicle['id']; ?>" tabindex="-1" aria-labelledby="detailsModalLabel<?php echo $vehicle['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="detailsModalLabel<?php echo $vehicle['id']; ?>"><?php echo htmlspecialchars($vehicle['vehicle_name']); ?> Details</h1>
                                    <button type="button" class="btn-close closeBtn" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body ms-2">
                                    <ul>
                                        <li><strong>Model:</strong> <?php echo htmlspecialchars($vehicle['model']); ?></li>
                                        <li><strong>Type:</strong> <?php echo htmlspecialchars($vehicle['vehicle_type']); ?></li>
                                        <li><strong>Registration No:</strong> <?php echo htmlspecialchars($vehicle['registration_no']); ?></li>
                                        <li><strong>Current KM:</strong> <?php echo htmlspecialchars($vehicle['current_km']); ?></li>
                                        <li><strong>Purchase Date:</strong> <?php echo htmlspecialchars($vehicle['purchase_date']); ?></li>
                                        <li><strong>Last Service Date:</strong> <?php echo htmlspecialchars($vehicle['last_service_date']); ?></li>
                                        <li><strong>Next Service Date:</strong> <?php echo htmlspecialchars($vehicle['next_service_date']); ?></li>
                                        <li><strong>Next Service KM:</strong> <?php echo htmlspecialchars($vehicle['next_service_km']); ?></li>
                                    </ul>
                                </div>
                                <div class="modal-footer d-flex align-items-center justify-content-center gap-3">
                                    <button class="btn editBtn px-4 py-2 d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $vehicle['id']; ?>" data-bs-dismiss="modal">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-secondary px-3 py-2 d-flex align-items-center justify-content-center gap-2" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for vehicle edit -->
                    <div class="modal fade" id="editModal<?php echo $vehicle['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $vehicle['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="editModalLabel<?php echo $vehicle['id']; ?>">Edit <?php echo htmlspecialchars($vehicle['vehicle_name']); ?></h1>
                                    <button type="button" class="btn-close closeBtn" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="" class="editForm">
                                        <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">

                                        <div class="form-group mb-3">
                                            <label for="vehicleName<?php echo $vehicle['id']; ?>" class="form-label">Vehicle Name</label>
                                            <input type="text" class="form-control" id="vehicleName<?php echo $vehicle['id']; ?>" name="vehicle_name" value="<?php echo htmlspecialchars($vehicle['vehicle_name']); ?>" required>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="model<?php echo $vehicle['id']; ?>" class="form-label">Model</label>
                                            <input type="text" class="form-control" id="model<?php echo $vehicle['id']; ?>" name="model" value="<?php echo htmlspecialchars($vehicle['model']); ?>" required>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="vehicle_type<?php echo $vehicle['id']; ?>" class="form-label">Vehicle Type (Car, Bike, Scooter, etc.)</label>
                                            <input type="text" class="form-control" id="vehicle_type<?php echo $vehicle['id']; ?>" name="vehicle_type" value="<?php echo htmlspecialchars($vehicle['vehicle_type']); ?>" required>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="registration_no<?php echo $vehicle['id']; ?>" class="form-label">Registration Number</label>
                                            <input type="text" class="form-control" id="registration_no<?php echo $vehicle['id']; ?>" name="registration_no" value="<?php echo htmlspecialchars($vehicle['registration_no']); ?>" required>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="purchaseDate<?php echo $vehicle['id']; ?>" class="form-label">Purchase Date</label>
                                            <input type="date" class="form-control" id="purchaseDate<?php echo $vehicle['id']; ?>" name="purchase_date" value="<?php echo htmlspecialchars($vehicle['purchase_date']); ?>" required>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="current_km<?php echo $vehicle['id']; ?>" class="form-label">Current Odometer Reading (KM)</label>
                                            <input type="number" class="form-control" id="current_km<?php echo $vehicle['id']; ?>" name="current_km" value="<?php echo htmlspecialchars($vehicle['current_km']); ?>" required>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="last_service_date<?php echo $vehicle['id']; ?>" class="form-label">Last Service Date (if any)</label>
                                            <input type="date" class="form-control" id="last_service_date<?php echo $vehicle['id']; ?>" name="last_service_date" value="<?php echo htmlspecialchars($vehicle['last_service_date']); ?>">
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="last_service_km<?php echo $vehicle['id']; ?>" class="form-label">Odometer at Last Service, if any (KM)</label>
                                            <input type="number" class="form-control" id="last_service_km<?php echo $vehicle['id']; ?>" name="last_service_km" value="<?php echo htmlspecialchars($vehicle['last_service_km']); ?>">
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="next_service_date<?php echo $vehicle['id']; ?>" class="form-label">Next Service Date</label>
                                            <input type="date" class="form-control" id="next_service_date<?php echo $vehicle['id']; ?>" name="next_service_date" value="<?php echo htmlspecialchars($vehicle['next_service_date']); ?>" required>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="next_service_km<?php echo $vehicle['id']; ?>" class="form-label">Next Service Odometer (KM)</label>
                                            <input type="number" class="form-control" id="next_service_km<?php echo $vehicle['id']; ?>" name="next_service_km" value="<?php echo htmlspecialchars($vehicle['next_service_km']); ?>" required>
                                        </div>

                                        <div class="btnGroup mt-2 d-flex align-items-center justify-content-center gap-4">
                                            <button type="submit" class="btn px-3 py-2 d-flex align-items-center justify-content-center gap-2 saveBtn">
                                                <i class="bi bi-check2-square"></i> Save Changes
                                            </button>
                                            <button type="button" class="btn px-5 py-2 d-flex align-items-center justify-content-center gap-2 btn-secondary" data-bs-dismiss="modal">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </button>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="mt-5 d-flex align-items-center justify-content-center vehicleNull p-5 my-5 ms-auto me-auto">
                    <p>No vehicles found. Add one to see it here!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . "/spinner.php"; ?>
<?php include __DIR__ . "/footer.php"; ?>