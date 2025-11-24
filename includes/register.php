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
            $mail->SMTPAuth   = true;
            $mail->Host       = $_ENV['MAIL_HOST'];
            $mail->Username   = $_ENV['MAIL_USERNAME'];
            $mail->Password   = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $mail->Port       = $_ENV['MAIL_PORT'];

            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);


            $mail->isHTML(true);
            $mail->Subject = "Verify Your AutoCare Account";

            $mail->Body = '
                <html>
    <head>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                background: #f4f4f4; 
                margin: 0; 
                padding: 20px; 
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                padding: 30px; 
                background: white; 
                border-radius: 12px; 
                box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            }
            .header { 
                text-align: center; 
                margin-bottom: 30px; 
                padding-bottom: 20px; 
                border-bottom: 2px solid #f82900; 
            }
            .header h1 { 
                color: #383838; 
                margin: 0; 
                font-size: 2em; 
            }
            .header p { 
                font-size: 0.9em; 
                color: #666; 
                margin: 10px 0 0 0; 
                text-transform: uppercase; 
                letter-spacing: 1px; 
            }
            span { 
                color: #f82900; 
            }
            .greeting { 
                font-size: 1.1em; 
                margin-bottom: 20px; 
            }
            .message { 
                margin: 25px 0; 
                padding: 20px; 
                font-size: 1em; 
                background: #fff3f0; 
                border-left: 4px solid #f82900; 
                line-height: 1.8; 
                border-radius: 4px; 
            }
            .button-container { 
                text-align: center; 
                margin: 30px 0; 
            }
            .verify-button { 
                display: inline-block; 
                padding: 15px 40px; 
                background: #f82900; 
                color: white; 
                text-decoration: none; 
                border-radius: 8px; 
                font-weight: bold; 
                font-size: 1em; 
                letter-spacing: 0.5px; 
            }
            .verify-button:hover { 
                background: #ff4520; 
            }
            .url-box { 
                margin: 20px 0; 
                padding: 15px; 
                background: #f9f9f9; 
                border: 1px solid #ddd; 
                border-radius: 6px; 
                word-break: break-all; 
                font-size: 0.85em; 
                color: #666; 
            }
            .url-box strong { 
                color: #f82900; 
            }
            .footer { 
                margin-top: 40px; 
                padding-top: 20px; 
                border-top: 1px solid #ddd; 
                font-size: 0.9em; 
                color: #666; 
                line-height: 1.8; 
            }
            .footer strong { 
                color: #383838; 
            }
            .security-note { 
                margin-top: 30px; 
                padding: 15px; 
                background: #f9f9f9; 
                border-radius: 6px; 
                font-size: 0.85em; 
                color: #666; 
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Auto<span>Care</span></h1>
                <p>Email Verification</p>
            </div>
            
            <p class="greeting">Hey, <strong>' . htmlspecialchars($user['first_name']) . '</strong> 👋</p>
            
            <div class="message">
                <p>Welcome to <strong>AutoCare</strong>!</p>
                <p>Thank you for signing up. To complete your registration and start managing your vehicle maintenance, please verify your email address by clicking the button below.</p>
            </div>
            
            <div class="button-container">
                <a href="' . $verifyUrl . '" class="verify-button">Verify Email Address</a>
            </div>
            
            <p style="text-align: center; font-size: 0.9em; color: #999; margin: 10px 0;">
                This link will expire in <strong style="color: #f82900;">24 hours</strong>
            </p>
            
            <div class="url-box">
                <strong>Button not working?</strong><br>
                Copy and paste this URL into your browser:<br>
                ' . htmlspecialchars($verifyUrl) . '
            </div>
            
            <div class="security-note">
                🔒 <strong>Security Note:</strong> If you did not create an account with AutoCare, please ignore this email or contact our support team.
            </div>
            
            <div class="footer">
                <p><strong>Thank you for choosing AutoCare!</strong></p>
                <p>
                    Best Regards,<br>
                    <strong>Ashif Rahman</strong><br>
                    Creator, AutoCare
                </p>
                <p style="margin-top: 20px; font-size: 0.85em;">
                    Need help? Contact us at <a href="mailto:autocare.service.app@gmail.com" style="color: #f82900; text-decoration: none;">autocare.service.app@gmail.com</a>
                </p>
            </div>
        </div>
    </body>
    </html>
            ';

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