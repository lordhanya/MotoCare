<?php
require __DIR__ . '/../../db/connection.php';
require __DIR__ . '/email_helpers.php';

$pageTitle = "Forgot Password | MotoCare";

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        if (!$email) throw new Exception("Please enter a valid email address.");

        // Find user
        $stmt = $conn->prepare("SELECT id, first_name, is_verified FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            // Intentionally vague to avoid email enumeration
            $success = "If an account with that email exists, we've sent a reset link.";
        } else {
            // Create secure token
            $token = bin2hex(random_bytes(32)); // raw token sent to user
            $tokenHash = hash('sha256', $token); // store hashed token
            $now = (new DateTime())->format('Y-m-d H:i:s');

            // Store hash and timestamp
            $upd = $conn->prepare("UPDATE users SET reset_token = :tokenHash, reset_token_created_at = :created WHERE id = :id");
            $upd->execute([
                ':tokenHash' => $tokenHash,
                ':created' => $now,
                ':id' => $user['id']
            ]);

            // Build reset URL (APP_BASE_URL should be set)
            $baseUrl = rtrim((getenv('APP_BASE_URL') ?: 'http://localhost:5000'), '/');
            $resetUrl = "{$baseUrl}/includes/auth/reset_password.php?token={$token}&uid={$user['id']}";

            // Compose email (simple HTML)
            $html = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Reset Password - MotoCare</title>
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
                            <p style='margin: 8px 0 0 0; font-size: 14px; color: #b0b0b0; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;'>Reset you MotoCare Password</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style='padding: 40px 30px; background-color: #0a0a0a;'>
                            
                            <p style='margin: 0 0 24px 0; font-size: 18px; font-weight: 600; color: #ffffff;'>
                                Hello <span style='color: #f82900;'>" . htmlspecialchars($user['first_name']) . "</span>,
                            </p>
                    <p style='margin: 0 0 24px 0; font-size: 15px; line-height: 1.7; color: #b0b0b0;'>
                                We received a request to reset the password for your <strong style='color: #ffffff;'>MotoCare</strong> account. Click the button below to reset it.
                            </p>

                            <!-- Info Box -->
                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background: rgba(248, 41, 0, 0.1); border: 1px solid #f82900; border-radius: 12px; margin-bottom: 30px;'>
                                <tr>
                                    <td style='padding: 16px 20px;'>
                                        <p style='margin: 0; font-size: 14px; line-height: 1.6; color: #b0b0b0;'>
                                            ⚠️ This reset link will expire in <strong style='color: #f82900;'>1 hour</strong>.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                         <!-- CTA Button -->
                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='margin: 30px 0;'>
                                <tr>
                                    <td align='center'>
                                        <a href='{$resetUrl}' style='display: inline-block; background: linear-gradient(135deg, #f82900, #ff4520); color: #ffffff; padding: 16px 48px; border-radius: 12px; text-decoration: none; font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;'>
                                            ✓ Reset Password
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
                                            {$resetUrl}
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
                                If you didn't request a password reset, please ignore this email.
                            </p>
                        </td>
                    </tr>
                       
                    
                <!-- Footer -->
                    <tr>
                        <td style='padding: 30px; background-color: #2a2828; text-align: center; border-top: 1px solid #2b2b2b;'>
                            <p style='margin: 0 0 16px 0; font-size: 14px; color: #b0b0b0;'>
                                Need help? <a href=\"mailto:" . ($_ENV['SUPPORT_EMAIL'] ?? 'support@motocare.com') . "\" style=\"color: #f82900; text-decoration: none; font-weight: 600;\">Contact Support</a>
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

            // Send email via Resend
            try {
                $mailer = getMailer();
                $mailer->send($email, "MotoCare: Password Reset", $html, "Reset your MotoCare password: {$resetUrl}");
            } catch (Exception $e) {
                // Log and continue so we don't reveal internal errors to the user
                log_error("Forgot password: send failed for user {$user['id']}: " . $e->getMessage());
            }

            $success = "If an account with that email exists, we've sent a reset link.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        log_error("Forgot password error: " . $e->getMessage());
    }
}

?>

<link rel="stylesheet" href="../../assets/style.css">
<section class="forgotPass-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="forgotPass-card">
                    <h2 class="title">Forgot password</h2>

                    <p class="subtitle">
                        Enter your registered email and a reset link will be sent if your account exists.
                    </p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <label for="email">
                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="input"
                                required
                                autocomplete="email"
                                placeholder=" " />
                            <span>Registered email address</span>
                        </label>

                        <button type="submit" class="submit">
                            Send reset link
                        </button>
                    </form>

                    <p class="back-login">
                        <a href="/includes/login.php"><i class="bi bi-box-arrow-in-left"></i> Back to login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
include __DIR__ . '/../spinner.php';
include __DIR__ . '/../footer.php'; ?>