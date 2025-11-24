<?php
require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../');
    $dotenv->load();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include __DIR__ . "/../db/connection.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $password   = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        echo "<script>alert('Buddy, passwords do not match!');
        window.location='register.php';</script>";
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->fetch()) {
        echo "<script>alert('Email already registered. Try logging in.');</script>";
        exit;
    }

    // Generate secure token
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $token  = bin2hex(random_bytes(32));

    // Insert user
    $insert = $conn->prepare("
    INSERT INTO users 
    (first_name, last_name, email, password, verify_token, is_verified) 
    VALUES 
    (:first_name, :last_name, :email, :password, :verify_token, 0)
");


    $insert->bindParam(':first_name', $first_name);
    $insert->bindParam(':last_name',  $last_name);
    $insert->bindParam(':email',      $email);
    $insert->bindParam(':password',   $hashed);
    $insert->bindParam(':verify_token', $token);

    if ($insert->execute()) {

        // Verification URL
        $verifyUrl = "https://autocare-gwv5.onrender.com/includes/auth/verify.php?token=" . $token;

        // Send Email
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = getenv('MAIL_HOST');
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('MAIL_USERNAME');
            $mail->Password   = getenv('MAIL_PASSWORD');
            $mail->SMTPSecure = getenv('MAIL_ENCRYPTION');
            $mail->Port       = getenv('MAIL_PORT');

            $mail->setFrom(getenv('MAIL_FROM_ADDRESS'), "AutoCare");
            $mail->addAddress($email, $first_name);

            $mail->isHTML(true);
            $mail->Subject = "Verify Your AutoCare Account";

            $mail->Body = "
                <h2>Welcome to AutoCare</h2>
                <p>Hello <strong>{$first_name}</strong>,</p>
                <p>Please verify your email to activate your account:</p>
                <a href='{$verifyUrl}' 
                   style='padding:10px 20px;background:#f82900;color:white;text-decoration:none;border-radius:6px;'>
                    Verify Email
                </a>
                <p>If the button doesn't work, copy and paste this URL:</p>
                <p>{$verifyUrl}</p>
            ";

            $mail->send();
        } catch (Exception $e) {
            error_log("Verification mail error: " . $mail->ErrorInfo);
        }

        echo "<script>alert('Registration successful! Please check your email to verify your account.'); 
              window.location='login.php';</script>";
        exit;
    } else {
        echo "<script>alert('Registration failed. Try again.');</script>";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Register | AutoCare</title>
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

<body>

    <section class="registerForm-section d-flex align-items-center justify-content-center">
        <div class="registerForm-container d-flex align-items-center justify-content-center">
            <form class="registerForm" action="register.php" method="POST">
                <p class="title">Register </p>
                <p class="message">Signup now and get full access to our app. </p>
                <div class="flex">
                    <label>
                        <input class="input" name="first_name" type="text" placeholder="" required>
                        <span>Firstname</span>
                    </label>

                    <label>
                        <input class="input" name="last_name" type="text" placeholder="" required>
                        <span>Lastname</span>
                    </label>
                </div>

                <label>
                    <input class="input" name="email" type="email" placeholder="" required>
                    <span>Email</span>
                </label>

                <label>
                    <input class="input" name="password" type="password" placeholder="" required>
                    <span>Password</span>
                </label>
                <label>
                    <input class="input" name="confirm_password" type="password" placeholder="" required>
                    <span>Confirm password</span>
                </label>
                <button class="submit" type="submit">Submit</button>
                <p class="signin">Already have an acount ? <a href="login.php">Signin</a> </p>
            </form>
        </div>
    </section>
    <?php include __DIR__ . "/spinner.php"; ?>
    <?php include __DIR__ . "/footer.php"; ?>