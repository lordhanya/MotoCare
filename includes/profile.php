<?php
session_start();
include __DIR__ . "/../db/connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Edit profile info
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $update = $conn->prepare("UPDATE users SET first_name=:first_name, last_name=:last_name, email=:email WHERE id=:user_id");
        $update->bindParam(':first_name', $first_name, PDO::PARAM_STR);
        $update->bindParam(':last_name', $last_name, PDO::PARAM_STR);
        $update->bindParam(':email', $email, PDO::PARAM_STR);
        $update->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        if ($update->execute()) {
            $_SESSION['message'] = "Profile updated successfully!";
            // Update session for immediate reflected changes
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
        } else {
            $_SESSION['message'] = "Failed to update profile!";
        }
        header("Location: profile.php"); 
        exit();
    }
    // Delete account
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $delete = $conn->prepare("DELETE FROM users WHERE id = :user_id");
        $delete->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        if ($delete->execute()) {
            session_destroy();
            header("Location: register.php"); 
            exit();
        } else {
            $_SESSION['message'] = "Failed to delete account!";
        }
        header("Location: profile.php");
        exit();
    }
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include __DIR__ . "/header.php";
include __DIR__ . "/dashNav.php";
include __DIR__ . "/sidebar.php";
?>

<section class="profile-section">
    <div class="container">
        <div class="row">
            <div class="heading">
                <h2>Account <span>Settings</span></h2>
            </div>
        </div>
        <div class="row mt-3">
            <div class="profileContainer bg-dark mt-3 p-5">
                <h3>My Profile</h3>
                <div class="profileFlex d-flex align-items-center justify-content-between mt-4">
                    <div class="pfpContainer d-flex align-items-center gap-3">
                        <i class="bi bi-person-circle userIcon"></i>
                        <!-- <img src="profile-pic.jpg" alt="Profile Picture" class="userIcon rounded-circle" style="width:70px;height:70px;object-fit:cover;"> -->
                        <div class="userData ms-3">
                            <p class='user-name mb-1'>
                                <?php echo isset($_SESSION['first_name']) ? $_SESSION['first_name'] : ''; ?>
                                <?php echo isset($_SESSION['last_name']) ? $_SESSION['last_name'] : ''; ?>
                            </p>
                            <p class="email mb-0">
                                <?= isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Guest'; ?>
                            </p>
                        </div>
                    </div>
                    <div class="profileEditBtn">
                        <button type="button" class="d-flex px-4 py-2 align-items-center justify-content-center gap-2">
                            <i class="bi bi-pencil-square"></i> Edit
                        </button>
                    </div>
                </div>
                <div class="profileFlex d-flex align-items-center justify-content-between mt-4 py-4 px-5">
                    <div class="personalInfo-container d-grid align-items-center justify-content-between mt-3 gap-3 rounded">
                        <h5 class="fw-bold fs-3 mb-2 mt-3">Personal Information</h5>
                        <div class="userInfo d-flex align-items-center gap-5 mt-3">
                            <div class="infoGroup d-grid align-items-center gap-2">
                                <label class="text-secondary mb-0" for="firstName">First Name</label>
                                <p class="first-name fw-medium mb-2"><?php echo isset($_SESSION['first_name']) ? $_SESSION['first_name'] : ''; ?></p>
                            </div>
                            <div class="infoGroup d-grid align-items-center gap-2">
                                <label class="text-secondary mb-0" for="lastName">Last Name</label>
                                <p class="last-name fw-medium mb-2"><?php echo isset($_SESSION['last_name']) ? $_SESSION['last_name'] : ''; ?></p>
                            </div>
                            <div class="infoGroup d-grid align-items-center gap-2">
                                <label class="text-secondary mb-0" for="email">Email Address</label>
                                <p class="email text-break mb-2"><?= isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Guest'; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="editDeleteBtn d-grid align-items-center gap-3">
                        <button type="button" class="d-flex btn-shadow editBtn px-4 py-2 me-2 align-items-center justify-content-center gap-2 profile-edit-btn" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="bi bi-pencil-square"></i> Edit
                        </button>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone!');">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="d-flex btn-shadow deleteBtn px-4 py-2 me-2 align-items-center justify-content-center gap-2 profile-edit-btn">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
</section>

<!-- Edit Profile Modal or Section -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="edit">
      <div class="modal-header">
        <h5 class="modal-title" id="editProfileLabel">Edit Personal Information</h5>
        <button type="button" class="btn-close bg-danger rounded-5 border-0" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="first_name" class="form-label">First Name</label>
          <input id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($_SESSION['first_name']) ?>">
        </div>
        <div class="mb-3">
          <label for="last_name" class="form-label">Last Name</label>
          <input id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($_SESSION['last_name']) ?>">
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input id="email" name="email" type="email" class="form-control" value="<?= htmlspecialchars($_SESSION['email']) ?>">
        </div>
      </div>
      <div class="modal-footer gap-3">
        <button class="btn d-flex align-items-center justify-content-center gap-2 saveBtn" type="submit"><i class="bi bi-check2-square"></i> Save Changes</button>
        <button type="button" class="btn btn-secondary d-flex align-items-center justify-content-center gap-2 border-0" data-bs-dismiss="modal"> <i class="bi bi-x-circle"></i> Cancel</button>
      </div>
    </form>
  </div>
</div>


<?php include __DIR__ . "/footer.php"; ?>