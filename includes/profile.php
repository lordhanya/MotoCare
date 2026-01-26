<?php
session_start();
include __DIR__ . "/../db/connection.php";

$pageTitle = "Profile | MotoCare";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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
            session_unset();
            header("Location: register.php");
            exit();
        } else {
            $_SESSION['message'] = "Failed to delete account!";
        }
        header("Location: profile.php");
        exit();
    }
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$imgSrc = $user['profile_image'];

if (empty($imgSrc)) {
    $imgSrc = '../assets/images/default.jpg';
} else {
    $imgSrc = '../assets/images/' . $imgSrc;
}

include __DIR__ . "/header.php";
include __DIR__ . "/dashNav.php";
include __DIR__ . "/sidebar.php";
?>

<section class="profileSection">
    <div class="container">
        <div class="row">
            <div class="heading text-white">
                <h2>Account <span>Settings</span></h2>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show text-center my-3 ms-auto me-auto d-flex align-items-center justify-content-center gap-2" role="alert">
                <i class="bi bi-bell"></i> <?php echo htmlspecialchars($_SESSION['message']);
                                            unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row mt-3">
            <div class="profileContainer mt-3">
                <h3>My Profile</h3>
                <div class="profileFlex d-flex flex-column align-items-center mt-4 gap-3">
                    <div class="pfpContainer d-flex flex-column align-items-center mb-3">
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                            alt="Profile picture"
                            class="rounded-circle"
                            width="120">
                        <div class="userData text-center mt-2">
                            <p class="user-name mb-1"><?php echo htmlspecialchars($user['first_name'] ?? '') . ' ' . htmlspecialchars($user['last_name'] ?? ''); ?></p>
                            <p class="email mb-0"><?php echo htmlspecialchars($user['email'] ?? 'Guest'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="profileEditBtn d-flex align-items-center justify-content-center">
                    <button type="button" id="pfpEdit" class="d-flex px-4 py-2 align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#pfpModal">
                        <i class="bi bi-pencil-square"></i> Change Profile Pic
                    </button>

                    <!-- Modal -->
                    <div class="modal fade" id="pfpModal" tabindex="-1" aria-labelledby="pfpModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="pfpModalLabel">Profile Picture Selection</h1>
                                    <button type="button" class="btn-close border-0 d-flex align-items-center justify-content-center" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-circle fs-2 text-white"></i></button>
                                </div>
                                <div class="modal-body">
                                    <div class="profile-form-container">
                                        <form action="save_profile.php" id="profileForm" method="post" enctype="multipart/form-data">

                                            <!-- Upload Section -->
                                            <div class="form-section">
                                                <label class="form-label">
                                                    <i class="bi bi-cloud-upload"></i>
                                                    Upload Profile Picture
                                                </label>
                                                <div class="upload-wrapper">
                                                    <div class="file-input-wrapper">
                                                        <input type="file" name="profile_image" id="fileInput" accept="image/*">
                                                        <label for="fileInput" class="file-label">
                                                            <i class="bi bi-image"></i>
                                                            <span id="fileName">Choose/drag an image file</span>
                                                        </label>
                                                    </div>
                                                    <button type="button" id="clearBtn" class="btn-clear">
                                                        <i class="bi bi-x-circle"></i>
                                                        Clear
                                                    </button>
                                                </div>
                                                <div class="preview-container" id="previewContainer" style="display: none;">
                                                    <img id="imagePreview" src="" alt="Preview">
                                                    <button type="button" id="removePreview" class="btn-remove-preview">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Divider -->
                                            <div class="divider">
                                                <span>OR</span>
                                            </div>

                                            <!-- Avatar Selection -->
                                            <div class="form-section">
                                                <label class="form-label">
                                                    <i class="bi bi-person-circle"></i>
                                                    Choose Default Avatar
                                                </label>
                                                <div class="avatar-grid" id="avatarGrid">
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p1.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p1.jpg" alt="Avatar 1">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p2.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p2.jpg" alt="Avatar 2">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p3.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p3.jpg" alt="Avatar 3">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p4.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p4.jpg" alt="Avatar 4">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p5.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p5.jpg" alt="Avatar 5">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p6.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p6.jpg" alt="Avatar 6">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p7.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p7.jpg" alt="Avatar 7">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p8.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p8.jpg" alt="Avatar 8">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p9.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p9.jpg" alt="Avatar 9">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p10.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p10.jpg" alt="Avatar 10">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p11.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p11.jpg" alt="Avatar 11">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p12.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p12.jpg" alt="Avatar 12">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p13.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p13.jpg" alt="Avatar 13">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <label class="avatar-option">
                                                        <input type="radio" name="default_avatar" value="p14.jpg">
                                                        <div class="avatar-wrapper">
                                                            <img src="../assets/images/p14.jpg" alt="Avatar 14">
                                                            <div class="avatar-check">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="form-actions">
                                                <button type="submit" name="save_profile" class="btn-submit">
                                                    <i class="bi bi-check-circle"></i>
                                                    Save Profile Picture
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profileFlex d-flex align-items-center justify-content-between mt-5 gap-4">
                <div class="personalInfo-container d-grid align-items-center justify-content-between gap-3 rounded">
                    <h5 class="fw-bold mb-2 mt-3">Personal Information</h5>
                    <div class="userInfo d-flex align-items-center gap-5 mt-3">
                        <div class="infoGroup d-grid align-items-center gap-2">
                            <label class="mb-0" for="firstName">First Name</label>
                            <p class="first-name fw-medium mb-2"><?php echo isset($user['first_name']) ? $user['first_name'] : ''; ?></p>
                        </div>
                        <div class="infoGroup d-grid align-items-center gap-2">
                            <label class="mb-0" for="lastName">Last Name</label>
                            <p class="last-name fw-medium mb-2"><?php echo isset($user['last_name']) ? $user['last_name'] : ''; ?></p>
                        </div>
                        <div class="infoGroup d-grid align-items-center gap-2">
                            <label class="mb-0" for="email">Email Address</label>
                            <p class="email text-break mb-2"><?= isset($user['email']) ? htmlspecialchars($user['email']) : 'Guest'; ?></p>
                        </div>
                    </div>
                </div>
                <div class="editDeleteBtn d-grid align-items-center gap-3">
                    <button type="button" class="d-flex editBtn px-4 py-2 align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="bi bi-pencil-square"></i> Edit
                    </button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="d-flex deleteBtn px-4 py-2 align-items-center justify-content-center gap-2"
                            onclick="return confirm('Are you sure you want to delete your account? This cannot be undone!')">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="edit">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileLabel">Edit Personal Information</h5>
                <button type="button" class="btn-close border-0 d-flex align-items-center justify-content-center" data-bs-dismiss="modal"><i class="bi bi-x fs-2 text-white"></i></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>">
                </div>
                <div class="mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
                </div>
            </div>
            <div class="modal-footer gap-3">
                <button class="btn d-flex align-items-center justify-content-center gap-2 saveBtn" type="submit"><i class="bi bi-check2-square"></i> Save Changes</button>
                <button type="button" class="btn btn-secondary d-flex align-items-center justify-content-center gap-2" data-bs-dismiss="modal"> <i class="bi bi-x-circle"></i> Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . "/footer.php"; ?>