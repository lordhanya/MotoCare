<?php

require __DIR__ . '/../../db/connection.php';
require __DIR__ . '/email_helpers.php';

$pageTitle = "Resend Verification Email | MotoCare";

$error = null;
$success = null;

try {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $uid   = filter_input(INPUT_POST, 'uid', FILTER_VALIDATE_INT);

        if (!$email && !$uid) {
            throw new Exception("Please enter your email address.");
        }

        // Fetch user
        if ($email) {
            $stmt = $conn->prepare("SELECT id, email, first_name, is_verified FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
        } else {
            $stmt = $conn->prepare("SELECT id, email, first_name, is_verified FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $uid]);
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("User not found.");
        }

        if ((int)$user['is_verified'] === 1) {
            throw new Exception("Your account is already verified.");
        }

        // Generate new verification token
        $token = bin2hex(random_bytes(32));

        // Store new token (NO timestamp)
        $stmt = $conn->prepare("UPDATE users SET verify_token = :token WHERE id = :id");
        $stmt->execute([
            ':token' => $token,
            ':id' => $user['id']
        ]);

        // Build verification URL
        $baseUrl = getenv('APP_BASE_URL') ?: 'http://localhost';
        $baseUrl = rtrim($baseUrl, '/');
        $verifyUrl = "{$baseUrl}/includes/auth/verify.php?token={$token}&uid={$user['id']}";


        // Email content
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
                                Moto<span style='color: #f82900;'>Care</span>
                            </h1>
                            <p style='margin: 8px 0 0 0; font-size: 14px; color: #b0b0b0; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;'>Email Verification</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style='padding: 40px 30px; background-color: #0a0a0a;'>
                            
                            <p style='margin: 0 0 24px 0; font-size: 18px; font-weight: 600; color: #ffffff;'>
                                Hello <span style='color: #f82900;'>" . htmlspecialchars($user['first_name']) . "</span>,
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
                                Need help? <a href=\"mailto:" . ($_ENV['SUPPORT_EMAIL'] ?? 'support@Motocare.com') . "\" style=\"color: #f82900; text-decoration: none; font-weight: 600;\">Contact Support</a>
                            </p>
                            <p style='margin: 0; font-size: 13px; color: #b0b0b0;'>
                                © 2025 MotoCare. All rights reserved.
                            </p>
                            <p style='margin: 0; font-size: 13px; color: #999;'>
                                Powered by <span style='color: #f82900; font-weight: bold;'>MotoCare</span> | Vehicle Maintenance Made Simple
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

        // Send email
        $mailer = getMailer();
        $mailer->send(
            $user['email'],
            "MotoCare: Verify Your Account",
            $html,
            "Verify your account using this link: $verifyUrl"
        );

        // Success message for HTML UI
        $success = "A new verification email has been sent successfully.";
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    log_error("resend_verification.php: " . $e->getMessage());
}

include __DIR__ . "/../header.php";
?>

<link rel="stylesheet" href="../../assets/style.css">

<body>
    <div class="verification-container">
        <!-- Header -->
        <div class="verification-header">
            <div class="verification-icon">
                <i class="bi bi-envelope-check-fill"></i>
            </div>
            <h2>Resend Verification</h2>
            <p>Enter your registered email address and we'll send you a new verification link</p>
        </div>

        <!-- Info Box -->
        <div class="info-box">
            <i class="bi bi-info-circle-fill"></i>
            <div class="info-box-text">
                Check your spam folder if you don't receive the email within a few minutes.
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="" method="POST" class="verification-form" id="verificationForm">
            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="bi bi-envelope-fill me-2" style="color: var(--accent-color);"></i> Email Address
                </label>
                <input type="email" name="email" id="email" class="form-input" placeholder="your.email@example.com" required
                    value="<?php echo htmlspecialchars($email ?? ''); ?>" autocomplete="email">
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                <i class="bi bi-send-fill"></i> Resend Verification Email
            </button>
        </form>

        <!-- Footer -->
        <div class="verification-footer">
            <div class="footer-links">
                <a href="../login.php" class="footer-link">
                    <i class="bi bi-box-arrow-in-right"></i> Back to Login
                </a>
                <a href="../register.php" class="footer-link">
                    <i class="bi bi-person-plus"></i> Create Account
                </a>
            </div>
        </div>
    </div>

    <script src="../../assets/script.js"></script>
</body>