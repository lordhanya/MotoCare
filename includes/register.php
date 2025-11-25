<?php
session_start();
include __DIR__ . '/../db/connection.php';
require __DIR__ . '/auth/email_helpers.php';

$pageTitle = "Register | MotoCare"; 


if ($_SERVER['REQUEST_METHOD']   == 'POST') {
    $first_name = ucfirst(strtolower(trim($_POST['first_name'])));
    $last_name = ucfirst(strtolower(trim($_POST['last_name'])));
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email address!";
        header("Location: register.php");
        exit;
    }

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $check = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $check->bindParam(':email', $email, PDO::PARAM_STR);
    $check->execute();

    if ($check->rowCount() > 0) {
        $_SESSION['message'] = "Email Already Exists! Try a different one.";
        header("Location: register.php");
        exit();
    } else {
        if ($password == $confirm_password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));

            // Insert new user with hashed password and token
            $insert = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, verify_token, is_verified) VALUES (:first_name, :last_name, :email, :password, :verify_token, 0)");
            $insert->bindParam(':first_name', $first_name, PDO::PARAM_STR);
            $insert->bindParam(':last_name', $last_name, PDO::PARAM_STR);
            $insert->bindParam(':email', $email, PDO::PARAM_STR);
            $insert->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $insert->bindParam(':verify_token', $token, PDO::PARAM_STR);

            if ($insert->execute()) {
                $user_id = $conn->lastInsertId();

                try {
                    $mailer = getMailer();
                    $verifyUrl = rtrim(getenv('APP_BASE_URL') ?: 'http://localhost', '/') . "/includes/auth/verify.php?token={$token}&uid={$user_id}";

                    $html = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Email Verification - MotoCare</title>
</head>
<body style='margin: 0; padding: 0; font-family: \"Inter\", -apple-system, BlinkMacSystemFont, \"Segoe UI\", Arial, sans-serif; background-color: #000000;'>
    
    <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #000000; padding: 40px 20px;'>
        <tr>
            <td align='center'>
                
                <table width='100%' cellpadding='0' cellspacing='0' border='0' style='max-width: 600px; background-color: #0a0a0a; border: 2px solid #2b2b2b; border-radius: 24px; overflow: hidden;'>
                    
                    <!-- Accent Bar -->
                    <tr>
                        <td style='background: linear-gradient(90deg, #f82900 0%, #ff4520 100%); height: 4px;'></td>
                    </tr>
                    
                    <!-- Header -->
                    <tr>
                        <td style='background-color: #2a2828; padding: 30px; text-align: center; border-bottom: 1px solid #2b2b2b;'>
                            <h1 style='margin: 20px 0 0 0; font-size: 32px; font-weight: 700; color: #ffffff;'>
                                Moto<span style='color: #f82900; margin-bottom: 2px solid #f82900;'>Care</span>
                            </h1>
                            <p style='margin: 8px 0 0 0; font-size: 14px; color: #b0b0b0; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;'>Email Verification</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style='padding: 40px 30px; background-color: #0a0a0a;'>
                            
                            <p style='margin: 0 0 24px 0; font-size: 18px; font-weight: 600; color: #ffffff;'>
                                Hello <span style='color: #f82900;'>" . htmlspecialchars($first_name) . "</span>,
                            </p>
                            
                            <p style='margin: 0 0 24px 0; font-size: 15px; line-height: 1.7; color: #b0b0b0;'>
                                Thank you for registering with <strong style='color: #ffffff;'>MotoCare</strong>! Please verify your email address to complete your registration.
                            </p>
                            
                            <!-- Info Box -->
                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background: rgba(248, 41, 0, 0.1); border: 1px solid #f82900; border-radius: 12px; margin-bottom: 30px;'>
                                <tr>
                                    <td style='padding: 16px 20px;'>
                                        <p style='margin: 0; font-size: 14px; line-height: 1.6; color: #b0b0b0;'>
                                            ⚠️ This verification link will expire in <strong style='color: #f82900;'>24 hours</strong>.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- CTA Button -->
                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='margin: 30px 0;'>
                                <tr>
                                    <td align='center'>
                                        <a href='{$verifyUrl}' style='display: inline-block; background: linear-gradient(135deg, #f82900, #ff4520); color: #ffffff; padding: 16px 48px; border-radius: 12px; text-decoration: none; font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;'>
                                            ✓ Verify Email Address
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Alternative Link -->
                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #2a2828; border: 1px solid #2b2b2b; border-radius: 12px; margin-top: 30px;'>
                                <tr>
                                    <td style='padding: 20px;'>
                                        <p style='margin: 0 0 12px 0; font-size: 13px; color: #b0b0b0; font-weight: 600; text-transform: uppercase;'>
                                            Button not working?
                                        </p>
                                        <p style='margin: 0 0 8px 0; font-size: 14px; color: #b0b0b0;'>
                                            Copy and paste this link:
                                        </p>
                                        <p style='margin: 0; font-size: 13px; color: #f82900; word-break: break-all; background-color: #121212; padding: 12px; border-radius: 8px;'>
                                            {$verifyUrl}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                        </td>
                    </tr>
                    
                    <!-- Security Notice -->
                    <tr>
                        <td style='padding: 30px; background-color: #121212; border-top: 1px solid #2b2b2b;'>
                            <p style='margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #ffffff;'>🔒 Security Notice</p>
                            <p style='margin: 0; font-size: 13px; line-height: 1.6; color: #b0b0b0;'>
                                If you didn't create an account with MotoCare, please ignore this email.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style='padding: 30px; background-color: #2a2828; text-align: center; border-top: 1px solid #2b2b2b;'>
                            <p style='margin: 0 0 16px 0; font-size: 14px; color: #b0b0b0;'>
                                Need help? <a href='mailto:motocare.service.app@gmail.com' style='color: #f82900; text-decoration: none; font-weight: 600;'>Contact Support</a>
                            </p>
                            <p style='margin: 0; font-size: 13px; color: #b0b0b0;'>
                                © 2025 MotoCare. All rights reserved.
                            </p>
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
</body>
</html>
";

                    $mailer->send($email, "Verify your MotoCare account", $html, "Verify your account: $verifyUrl");
                } catch (Exception $e) {
                    log_error("Failed to send verification email for user {$user_id}: " . $e->getMessage());
                }

                echo "<script>
    alert('Registration Successful! Please Verify Your Email.');
    window.location.href = 'login.php';
</script>";
                exit;
            } else {
                $_SESSION['message'] = "Registration Failed!";
                header("Location: register.php");
                exit;
            }
        } else {
            $_SESSION['message'] = "Passwords do not match!";
            header("Location: register.php");
            exit();
        }
    }
}

include __DIR__ . "/header.php";
?>




<section class="registerForm-section d-flex align-items-center justify-content-center">
    <div class="registerForm-container d-flex align-items-center justify-content-center">
        <form class="registerForm" action="register.php" method="POST">
            <p class="title">Register </p>
            <p class="message">Signup now and get full access to our app. </p>
            <!-- Alert -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert text-center text-danger rounded-3 border-1 border-danger my-3 ms-auto me-auto d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-bell"></i>
                    <?php
                    echo htmlspecialchars($_SESSION['message']);
                    unset($_SESSION['message']);
                    ?>
                </div>
            <?php endif; ?>
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