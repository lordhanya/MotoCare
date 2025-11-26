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
                                Need help? <a href='mailto:support@Motocare.com' style='color: #f82900; text-decoration: none; font-weight: 600;'>Contact Support</a>
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

<style>
    /* ========================================
   FORGOT PASS
   ======================================== */
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
        --danger-color: #ff4444;
        --success-color: #00ff88;
        --warning-color: #ffd700;
    }

    .forgotPass-section {
        width: 100%;
        min-height: 100vh;
        background: #1a1919;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2em 0;
    }

    .forgotPass-card {
        display: flex;
        flex-direction: column;
        gap: 1em;
        width: 100%;
        padding: 2em 2.25em;
        border-radius: 20px;
        background-color: #090909;
        color: var(--text-primary);
        box-shadow:
            15px 15px 30px rgb(25, 25, 25),
            -15px -15px 30px rgb(60, 60, 60);
        border: none;
        position: relative;
    }

    .forgotPass-card .title {
        font-size: 28px;
        font-weight: 600;
        letter-spacing: -1px;
        position: relative;
        display: flex;
        align-items: center;
        padding-left: 30px;
        color: var(--accent-color);
    }

    .forgotPass-card .title::before,
    .forgotPass-card .title::after {
        content: "";
        position: absolute;
        left: 0;
        height: 16px;
        width: 16px;
        border-radius: 50%;
        background-color: var(--accent-color);
    }

    .forgotPass-card .title::after {
        animation: pulse 1s linear infinite;
    }

    .forgotPass-card .subtitle {
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-bottom: 1rem;
    }

    .forgotPass-card .alert {
        border-radius: 12px;
        padding: 1rem 1.25rem;
        font-size: 0.9rem;
        margin-bottom: 1rem;
        border: 1px solid var(--accent-color);
        background: rgba(248, 41, 0, 0.1);
        color: var(--accent-color);
    }

    .forgotPass-card label {
        position: relative;
        width: 100%;
        display: block;
        margin-bottom: 1em;
    }

    .forgotPass-card .input {
        background-color: #333;
        color: var(--text-primary);
        width: 100%;
        padding: 20px 8px 6px 10px;
        border-radius: 10px;
        outline: none;
        border: 1px solid rgba(105, 105, 105, 0.4);
        font-size: medium;
    }

    .forgotPass-card label .input+span {
        color: rgba(255, 255, 255, 0.5);
        position: absolute;
        left: 10px;
        top: 0px;
        font-size: 0.9em;
        cursor: text;
        transition: 0.3s ease;
    }

    .forgotPass-card label .input:placeholder-shown+span {
        top: 15px;
        font-size: 0.9em;
    }

    .forgotPass-card label .input:focus+span,
    .forgotPass-card label .input:valid+span {
        color: var(--accent-color);
        top: 2px;
        font-size: 0.7em;
        font-weight: 600;
    }

    .forgotPass-card .submit {
        border: none;
        outline: none;
        padding: 10px;
        border-radius: 10px;
        font-size: 16px;
        color: var(--text-primary);
        background-color: var(--accent-color);
        cursor: pointer;
        transition: background 0.3s ease;
        width: 100%;
    }

    .forgotPass-card .submit:hover {
        background-color: var(--accent-hover);
    }

    .forgotPass-card .back-login {
        text-align: center;
        margin-top: 10px;
        font-size: 0.9rem;
    }

    .forgotPass-card .back-login a {
        color: var(--accent-color);
        text-decoration: none;
    }

    .forgotPass-card .back-login a:hover {
        text-decoration: underline var(--accent-color);
    }

    /* Pulse animation used for the title dot */
    @keyframes pulse {
        0% {
            transform: scale(1);
            opacity: 1;
        }

        50% {
            transform: scale(1.3);
            opacity: 0.5;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .footer {
        background: linear-gradient(180deg, var(--bg-card) 0%, var(--bg-dark) 100%);
        border-top: 1px solid var(--border-color);
        padding: 3rem 1rem 1.5rem;
        position: relative;
        overflow: hidden;
    }

    /* Decorative gradient overlay */
    .footer::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg,
                transparent,
                var(--accent-color) 20%,
                var(--accent-hover) 50%,
                var(--accent-color) 80%,
                transparent);
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }

    .footer-col h4 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 1.5rem;
        letter-spacing: 0.5px;
        position: relative;
        display: inline-block;
    }

    .footer-col h4 span {
        color: var(--accent-color);
        position: relative;
    }

    .footer-col h4::after {
        content: "";
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg,
                transparent,
                var(--accent-color),
                transparent);
        border-radius: 2px;
    }

    /* Social Links */
    .social-links {
        display: flex;
        gap: 1.5rem;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
    }

    .social-links a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        color: var(--text-secondary);
        font-size: 1.4rem;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .social-links a::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
        opacity: 0;
        transition: opacity 0.4s ease;
        z-index: -1;
    }

    .social-links a:hover {
        transform: translateY(-5px) scale(1.05);
        border-color: var(--accent-color);
        color: var(--text-primary);
        box-shadow: 0 8px 24px rgba(248, 41, 0, 0.4);
    }

    .social-links a:hover::before {
        opacity: 1;
    }

    .social-links a:active {
        transform: translateY(-2px) scale(1);
    }

    /* Specific icon hover effects */
    .social-links a:nth-child(1):hover {
        box-shadow: 0 8px 24px rgba(59, 89, 152, 0.5);
    }

    .social-links a:nth-child(2):hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
    }

    .social-links a:nth-child(3):hover {
        box-shadow: 0 8px 24px rgba(225, 48, 108, 0.5);
    }

    .social-links a:nth-child(4):hover {
        box-shadow: 0 8px 24px rgba(0, 119, 181, 0.5);
    }

    /* Footer Bottom */
    .footer-bottom {
        margin-top: 3rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border-color);
        position: relative;
    }

    .footer-bottom p {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
        line-height: 1.6;
    }

    .footer-bottom p:first-child {
        font-weight: 500;
        color: var(--text-primary);
    }

    .footer-bottom a {
        color: var(--accent-color);
        text-decoration: none;
        font-weight: 600;
        position: relative;
        transition: all 0.3s ease;
        display: inline-block;
    }

    .footer-bottom a::after {
        content: "";
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--accent-hover);
        transition: width 0.3s ease;
    }

    .footer-bottom a:hover {
        color: var(--accent-hover);
        transform: translateX(3px);
    }

    .footer-bottom a:hover::after {
        width: 100%;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .footer {
            padding: 2rem 1rem 1rem;
        }

        .footer-col h4 {
            font-size: 1.3rem;
        }

        .social-links {
            gap: 1rem;
        }

        .social-links a {
            width: 45px;
            height: 45px;
            font-size: 1.2rem;
        }

        .footer-bottom {
            margin-top: 2rem;
            padding-top: 1rem;
        }

        .footer-bottom p {
            font-size: 0.85rem;
        }
    }

    @media (max-width: 576px) {
        .footer-col h4 {
            font-size: 1.2rem;
        }

        .social-links a {
            width: 42px;
            height: 42px;
            font-size: 1.1rem;
        }
    }

    /* Animation for social icons on load */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .social-links a {
        animation: fadeInUp 0.6s ease forwards;
        opacity: 0;
    }

    .social-links a:nth-child(1) {
        animation-delay: 0.1s;
    }

    .social-links a:nth-child(2) {
        animation-delay: 0.2s;
    }

    .social-links a:nth-child(3) {
        animation-delay: 0.3s;
    }

    .social-links a:nth-child(4) {
        animation-delay: 0.4s;
    }

    /* Pulse effect for copyright text */
    .footer-bottom p:first-child {
        animation: fadeIn 1s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }
</style>
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