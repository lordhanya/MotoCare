<?php
session_start();
include __DIR__ . "/../db/connection.php";

$pageTitle = "Login | MotoCare";

$verifiedMessage = '';
$alertType = '';

if (isset($_GET['verified'])) {
    if ($_GET['verified'] === 'success') {
        $verifiedMessage = "Your email has been successfully verified!";
        $alertType = 'success';
    } elseif ($_GET['verified'] === 'error') {
        $verifiedMessage = "Your email verification failed!";
        $alertType = 'danger';
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $_email = trim($_POST['email']);
    $_password = trim($_POST['password']);

    if (empty($_email) || empty($_password)) {
        echo "<script>alert('Please fill in all fields.');</script>";
    } elseif (!filter_var($_email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format.');</script>";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->bindParam(':email', $_email, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if ($user['is_verified'] == 0) {
                    echo "<script>alert('Please verify your email before logging in.');</script>";
                    exit;
                }

                if (password_verify($_password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['login_success'] = true;

                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];

                    $stmt = $conn->prepare("SELECT COUNT(*) FROM vehicles WHERE user_id = :user_id");
                    $stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                    $stmt->execute();

                    $vehicleCount = $stmt->fetchColumn();

                    if ($vehicleCount > 0) {
                        header("Location: dashboard.php");
                        exit;
                    } else {
                        header("Location: add_vehicle.php");
                        exit;
                    }
                } else {
                    echo "<script>alert('Incorrect password! Try again.');</script>";
                }
            } else {
                echo "<script>alert('Email not registered, try creating an account buddy.');</script>";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            echo "<script>alert('Database error occurred.');</script>";
        }
    }
}

?>
<section class="loginForm-section d-flex align-items-center justify-content-center">
    <div class="loginForm-container d-flex align-items-center justify-content-center">
        <form class="loginForm" action="login.php" method="POST">
            <p class="title">Log in </p>
            <p class="message">Login now and get full access to our app. </p>
            <div class="info-box">
                <i class="bi bi-info-circle-fill"></i>
                <div class="info-box-text">
                    Check your spam folder if you don't receive the email within a few minutes.
                </div>
            </div>
            <?php if ($alertType === 'success'): ?>
                <div class="alert bg-dark alert-success text-success border-success" style="margin-bottom:2rem;">
                    <i class="bi bi-patch-check"></i> <?php echo htmlspecialchars($verifiedMessage); ?>
                </div>
            <?php elseif ($alertType === 'danger'): ?>
                <div class="alert bg-dark alert-danger text-danger border-danger" style="margin-bottom:2rem;">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($verifiedMessage); ?>
                </div>
            <?php endif; ?>

            <label>
                <input class="input" name="email" type="email" placeholder="" required>
                <span>Email</span>
            </label>

            <label>
                <input class="input" name="password" type="password" placeholder="" required>
                <span>Password</span>
            </label>
            <button class="submit" type="submit">Submit</button>
            <p class="signup">Don't have an account? <a href="register.php">Create one now!</a></p>
            <p class="resend_verification mb-3">
                Didn't receive verification email?
                <a href="auth/resend_verification.php">Resend Email</a>
            </p>
        </form>
    </div>
</section>
<?php include __DIR__ . "/spinner.php"; ?>
<?php include __DIR__ . "/footer.php"; ?>