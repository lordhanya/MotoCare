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
                                Need help? <a href='mailto:support@Motocare.com' style='color: #f82900; text-decoration: none; font-weight: 600;'>Contact Support</a>
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

<style>
    :root {
        --accent-color: #f82900;
        --accent-hover: #ff4520;
        --bg-dark: #000;
        --bg-card: #0a0a0a;
        --bg-card-hover: #121212;
        --bg-secondary: #2a2828;
        --text-primary: #fff;
        --text-secondary: #b0b0b0;
        --border-color: #2b2b2b;
        --border-light: rgba(255, 255, 255, 0.1);
        --success-color: #00ff88;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: var(--bg-dark);
        color: var(--text-primary);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        position: relative;
        overflow-x: hidden;
    }

    /* Background Effect */
    body::before {
        content: '';
        position: absolute;
        top: -20%;
        left: -10%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(248, 41, 0, 0.08) 0%, transparent 70%);
        pointer-events: none;
        filter: blur(60px);
    }

    body::after {
        content: '';
        position: absolute;
        bottom: -20%;
        right: -10%;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(248, 41, 0, 0.05) 0%, transparent 70%);
        pointer-events: none;
        filter: blur(80px);
    }

    /* Main Container */
    .verification-container {
        width: 100%;
        max-width: 480px;
        background: var(--bg-card);
        border: 2px solid var(--border-color);
        border-radius: 24px;
        padding: 3rem;
        box-shadow: 0 10px 50px rgba(0, 0, 0, 0.5), 0 0 0 1px var(--border-light) inset;
        position: relative;
        z-index: 1;
        transition: all 0.3s ease;
    }

    .verification-container:hover {
        border-color: var(--accent-color);
        box-shadow: 0 15px 60px rgba(248, 41, 0, 0.2), 0 0 0 1px var(--accent-color) inset;
    }

    /* Header */
    .verification-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .verification-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        box-shadow: 0 8px 20px rgba(248, 41, 0, 0.3);
    }

    .verification-icon i {
        font-size: 2rem;
        color: white;
    }

    .verification-header h2 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        letter-spacing: -0.5px;
    }

    .verification-header p {
        font-size: 0.95rem;
        color: var(--text-secondary);
        line-height: 1.6;
        margin: 0;
    }

    /* Form */
    .verification-form {
        margin-top: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        color: var(--text-primary);
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-input {
        width: 100%;
        background: var(--bg-secondary);
        border: 2px solid var(--border-color);
        color: var(--text-primary);
        padding: 1rem 1.25rem;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
        outline: none;
    }

    .form-input:hover {
        border-color: var(--accent-hover);
        background: var(--bg-card-hover);
    }

    .form-input:focus {
        border-color: var(--accent-color);
        background: var(--bg-card-hover);
        box-shadow: 0 0 0 4px rgba(248, 41, 0, 0.1);
    }

    .form-input::placeholder {
        color: var(--text-secondary);
        opacity: 0.6;
    }

    /* Submit Button */
    .submit-btn {
        width: 100%;
        background: var(--accent-color);
        border: 2px solid var(--accent-color);
        color: white;
        padding: 1rem 2rem;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        box-shadow: 0 5px 20px rgba(248, 41, 0, 0.3);
    }

    .submit-btn:hover {
        background: var(--accent-hover);
        border-color: var(--accent-hover);
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(248, 41, 0, 0.5);
    }

    .submit-btn:active {
        transform: translateY(0);
    }

    .submit-btn i {
        font-size: 1.1rem;
    }

    /* Footer Links */
    .verification-footer {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid var(--border-color);
        text-align: center;
    }

    .footer-links {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    .footer-link {
        color: var(--text-secondary);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .footer-link:hover {
        color: var(--accent-color);
    }

    .footer-link i {
        font-size: 1rem;
    }

    /* Info Box */
    .info-box {
        background: rgba(248, 41, 0, 0.1);
        border: 1px solid var(--accent-color);
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .info-box i {
        color: var(--accent-color);
        font-size: 1.25rem;
        flex-shrink: 0;
        margin-top: 0.1rem;
    }

    .info-box-text {
        font-size: 0.9rem;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .verification-container {
            padding: 2rem 1.5rem;
        }

        .verification-header h2 {
            font-size: 1.5rem;
        }

        .verification-icon {
            width: 60px;
            height: 60px;
        }

        .verification-icon i {
            font-size: 1.75rem;
        }

        .form-input {
            padding: 0.875rem 1rem;
            font-size: 0.95rem;
        }

        .submit-btn {
            padding: 0.875rem 1.5rem;
            font-size: 0.95rem;
        }

        .footer-links {
            flex-direction: column;
            gap: 1rem;
        }
    }

    @media (max-width: 400px) {
        .verification-container {
            padding: 1.5rem 1rem;
        }

        .verification-header h2 {
            font-size: 1.35rem;
        }

        body::before,
        body::after {
            display: none;
        }
    }

    /* Loading State */
    .submit-btn.loading {
        pointer-events: none;
        opacity: 0.7;
    }

    .submit-btn.loading::after {
        content: '';
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
        margin-left: 0.5rem;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

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

    <script>
        // Add loading state to button on submit
        document.getElementById('verificationForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
            btn.innerHTML = "<i class='bi bi-hourglass-split'></i> Sending...";
        });

        // Email input validation border color feedback
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('input', function() {
            if (this.validity.valid) {
                this.style.borderColor = 'var(--success-color)';
            } else if (this.value.length > 0) {
                this.style.borderColor = 'var(--accent-color)';
            } else {
                this.style.borderColor = 'var(--border-color)';
            }
        });
    </script>
</body>