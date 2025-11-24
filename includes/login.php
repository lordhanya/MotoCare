<?php
session_start();
include __DIR__ . "/../db/connection.php";

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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AutoCare</title>
    <link rel="icon" type="image/png" href="../assets/images/AutoCare_logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body></body>

<section class="loginForm-section d-flex align-items-center justify-content-center">
    <div class="loginForm-container d-flex align-items-center justify-content-center">
        <form class="loginForm" action="login.php" method="POST">
            <p class="title">Log in </p>
            <p class="message">Login now and get full access to our app. </p>
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