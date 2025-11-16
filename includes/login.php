<?php
session_start();
include "header.php";
include "../db/connection.php";

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
                if (password_verify($_password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['login_success'] = true;

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
        </form>
    </div>
</section>
<?php include __DIR__ . "/spinner.php"; ?>
<?php include "footer.php"; ?>
