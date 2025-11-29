<?php
// includes/auth/reset_password.php
require __DIR__ . '/../../db/connection.php';
require __DIR__ . '/email_helpers.php';

date_default_timezone_set('Asia/Kolkata');

$error = null;
$success = null;
$showForm = false;

// Config
$tokenExpirySeconds = 3600; // 1 hour

try {
    if (!isset($_GET['token']) || !isset($_GET['uid'])) {
        throw new Exception("Invalid password reset link.");
    }

    $rawToken = $_GET['token'];
    $uid = (int) $_GET['uid'];
    if (!$rawToken || !$uid) throw new Exception("Invalid link.");

    // Fetch stored token hash
    $stmt = $conn->prepare("SELECT id, email, first_name, reset_token, reset_token_created_at FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) throw new Exception("User not found.");

    if (empty($user['reset_token'])) {
        throw new Exception("No valid reset request found. Please request a new reset link.");
    }

    // Check expiry if timestamp exists
    if (!empty($user['reset_token_created_at'])) {
        $created = new DateTime($user['reset_token_created_at']);
        $now = new DateTime();
        if ($now->getTimestamp() - $created->getTimestamp() > $tokenExpirySeconds) {
            // Clear token (optional)
            $upd = $conn->prepare("UPDATE users SET reset_token = NULL, reset_token_created_at = NULL WHERE id = :id");
            $upd->execute([':id' => $uid]);
            throw new Exception("Reset link has expired. Please request a new one.");
        }
    }

    // Compare hashed tokens
    $tokenHash = hash('sha256', $rawToken);
    if (!hash_equals($tokenHash, $user['reset_token'])) {
        throw new Exception("Invalid reset token.");
    }

    // If POST arrived with new password, update it
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        if (strlen($password) < 8) throw new Exception("Password must be at least 8 characters.");
        if ($password !== $password2) throw new Exception("Passwords do not match.");

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Update user record, clear reset token
        $upd = $conn->prepare("UPDATE users SET password = :pw, reset_token = NULL, reset_token_created_at = NULL, is_verified = 1 WHERE id = :id");
        $upd->execute([
            ':pw' => $passwordHash,
            ':id' => $uid
        ]);

        // Optionally: send a confirmation email
        try {
            $mailer = getMailer();
            $html =
            '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Reset - MotoCare</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #000000;">
        
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #000000; padding: 40px 20px;">
            <tr>
                <td align="center">
                    
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; background-color: #0a0a0a; border: 2px solid #2b2b2b; border-radius: 24px; overflow: hidden;">
                        
                        <!-- Accent Bar -->
                        <tr>
                            <td style="background: linear-gradient(90deg, #f82900 0%, #ff4520 100%); height: 4px;"></td>
                        </tr>
                        
                        <!-- Header -->
                        <tr>
                            <td style="background-color: #2a2828; padding: 30px; text-align: center; border-bottom: 1px solid #2b2b2b;">
                                <h1 style="margin: 0; font-size: 32px; font-weight: 700; color: #ffffff;">
                                    Moto<span style="color: #f82900; border-bottom: 2px solid #f82900; padding-bottom: 2px;">Care</span>
                                </h1>
                                <p style="margin: 8px 0 0 0; font-size: 14px; color: #b0b0b0; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Password Reset Confirmation</p>
                            </td>
                        </tr>
                        
                        <!-- Content -->
                        <tr>
                            <td style="padding: 40px 30px; background-color: #0a0a0a; text-align: center;">
                                
                                <!-- Success Icon -->
                                <div style="width: 80px; height: 80px; margin: 0 auto 25px auto; background: linear-gradient(135deg, #f82900, #ff4520); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(248, 41, 0, 0.4);">
                                    <span style="font-size: 40px; color: #ffffff; display: flex; align-items: center;" justify-content: center;>🔒</span>
                                </div>
                                
                                <p style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: #ffffff;">
                                    Hello <span style="color: #f82900;">'. htmlspecialchars($user['first_name'] ?? 'User') .'</span>,
                                </p>
                                
                                <h2 style="margin: 0 0 20px 0; font-size: 1.8em; font-weight: bold; color: #ffffff;">
                                    Password Changed Successfully
                                </h2>
                                
                                <p style="margin: 0 0 30px 0; font-size: 15px; line-height: 1.7; color: #b0b0b0;">
                                    Your password has been updated successfully. You can now use your new password to log in to your MotoCare account.
                                </p>
                                
                                <!-- Info Box -->
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background: rgba(248, 41, 0, 0.1); border: 1px solid #f82900; border-radius: 12px; margin-bottom: 30px;">
                                    <tr>
                                        <td style="padding: 20px; text-align: left;">
                                            <p style="margin: 0 0 10px 0; font-size: 14px; color: #ffffff; font-weight: 600;">
                                                ℹ️ Password Changed Details:
                                            </p>
                                            <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #b0b0b0;">
                                                <strong style="color: #f82900;">Date:</strong> ' . date('F d, Y') . '<br>
                                                <strong style="color: #f82900;">Time:</strong> ' . date('h:i A') . '<br>
                                                <strong style="color: #f82900;">IP Address:</strong> ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . '
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Login Button -->
                                <a href="https://autocare.onrender.com/includes/login.php" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #f82900, #ff4520); color: #ffffff; text-decoration: none; border-radius: 12px; font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(248, 41, 0, 0.4);">
                                    Login to Your Account
                                </a>
                                
                            </td>
                        </tr>
                        
                        <!-- Security Notice -->
                        <tr>
                            <td style="padding: 30px; background-color: #121212; border-top: 1px solid #2b2b2b;">
                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding-right: 15px; vertical-align: top; width: 32px;">
                                            <div style="width: 32px; height: 32px; background: rgba(248, 41, 0, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <span style="font-size: 18px;">⚠️</span>
                                            </div>
                                        </td>
                                        <td style="text-align: left;">
                                            <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #ffffff;">Security Alert</p>
                                            <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #b0b0b0;">
                                                If you did not perform this password change, please contact our support team immediately and secure your account.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style="padding: 30px; background-color: #2a2828; text-align: center; border-top: 1px solid #2b2b2b;">
                                <p style="margin: 0 0 16px 0; font-size: 14px; color: #b0b0b0;">
                                    Need help? <a href="mailto:autocare.service.app@gmail.com" style="color: #f82900; text-decoration: none; font-weight: 600;">Contact Support</a>
                                </p>
                                <p style="margin: 0 0 8px 0; font-size: 13px; color: #b0b0b0;">
                                    © 2025 MotoCare. All rights reserved.
                                </p>
                                <p style="margin: 0; font-size: 13px; color: #999;">
                                    Powered by <span style="color: #f82900; font-weight: bold;">MotoCare</span> | Vehicle Maintenance Made Simple
                                </p>
                            </td>
                        </tr>
                        
                    </table>
                    
                </td>
            </tr>
        </table>
        
    </body>
    </html>
    ';
            $mailer->send($user['email'], "MotoCare: Password changed", $html, "Your MotoCare password was changed.");
        } catch (Exception $e) {
            log_error("Reset password: confirmation email failed for {$uid}: " . $e->getMessage());
        }

        $success = "Your password has been reset. You may now <a href='/includes/login.php'>login</a>.";
    } else {
        $showForm = true;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    log_error("reset_password.php error: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | MotoCare</title>
    <link rel="icon" type="image/png" href="../../assets/images/motocare_logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/style.css">
</head>

<body>
    <div class="reset-container">
        <!-- Logo -->
        <div class="reset-header">
            <div class="logo">Moto<span>Care</span></div>
            <h2><i class="bi bi-shield-lock"></i> Reset Password</h2>
            <p>Enter your new password below</p>
        </div>

        <!-- Error Alert -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Success Alert -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <!-- Reset Form -->
        <?php if ($showForm): ?>
            <form method="POST" action="" class="reset-form">
                <!-- New Password -->
                <div class="form-group">
                    <label for="password">
                        <i class="bi bi-lock"></i>
                        New Password
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            required 
                            minlength="8"
                            placeholder="Enter new password (min 8 chars)">
                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strength-fill"></div>
                        </div>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="password2">
                        <i class="bi bi-lock-fill"></i>
                        Confirm Password
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            name="password2" 
                            id="password2" 
                            required 
                            minlength="8"
                            placeholder="Confirm your password">
                        <button type="button" class="toggle-password" onclick="togglePassword('password2', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn">
                    <i class="bi bi-check-circle"></i>
                    Reset Password
                </button>
            </form>
        <?php else: ?>
            <!-- Success Message -->
            <div class="success-message">
                <div class="success-icon">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h3>Password Reset!</h3>
                <p>Your password has been successfully updated.</p>
                <a href="../login.php" class="btn-secondary">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Go to Login
                </a>
            </div>
        <?php endif; ?>

        <!-- Back to Login Link -->
        <?php if ($showForm): ?>
            <div class="back-login">
                <p>Remember your password?</p>
                <a href="../login.php">
                    <i class="bi bi-arrow-left"></i>
                    Back to Login
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="../../assets/script.js"></script>
</body>
</html>