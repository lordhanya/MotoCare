<?php
session_start();
require __DIR__ . '/../../db/connection.php';
require __DIR__ . '/../../vendor/autoload.php';

if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../../');
    $dotenv->load();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = $_SESSION['email'] ?? null;
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    // 1. Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        // 2. Check user
        $stmt = $conn->prepare('SELECT id, first_name, is_verified FROM users WHERE email = :email LIMIT 1');
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = 'No account found with this email.';
        } elseif ((int)$user['is_verified'] === 1) {
            // Already verified – redirect
            header('Location: ../pages/login.php');
            exit;
        } else {
            // 3. Generate & save token
            $new_token = bin2hex(random_bytes(32));
            $update = $conn->prepare('UPDATE users SET verify_token = :token WHERE id = :id');
            $update->bindParam(':token', $new_token);
            $update->bindParam(':id', $user['id']);
            $update->execute();

            // 4. Build URL
            $verifyUrl = 'https://autocare.onrender.com/includes/auth/verify.php?token=' . urlencode($new_token);

            // 5. Send mail
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->SMTPAuth   = true;
                $mail->Host = $_ENV['MAIL_HOST'];
                $mail->Username = $_ENV['MAIL_USERNAME'];
                $mail->Password = $_ENV['MAIL_PASSWORD'];
                $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
                $mail->Port = $_ENV['MAIL_PORT'];


                $mail->setFrom(
                    $_ENV['MAIL_FROM_ADDRESS'],
                    $_ENV['MAIL_FROM_NAME']
                );


                $mail->addAddress($email, $user['first_name']);

                $mail->isHTML(true);
                $mail->Subject = 'Resend Verification - AutoCare';

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
                $success = 'Verification email has been resent! Please check your inbox.';
            } catch (Exception $e) {
                error_log($_ENV['MAIL_PASSWORD']);
                error_log('Mail Error: ' . $mail->ErrorInfo);
                $error = 'Failed to send verification email. Try again later.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Resend Verification | AutoCare</title>
    <link rel='icon' type='image/png' href='../assets/images/AutoCare_logo.png'>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css'>
    <link rel='preconnect' href='https://fonts.googleapis.com'>
    <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap' rel='stylesheet'>

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
</head>

<body>
    <div class='verification-container'>
        <!-- Header -->
        <div class='verification-header'>
            <div class='verification-icon'>
                <i class='bi bi-envelope-check-fill'></i>
            </div>
            <h2>Resend Verification</h2>
            <p>Enter your registered email address and we'll send you a new verification link</p>
        </div>

        <!-- Info Box -->
        <div class='info-box'>
            <i class='bi bi-info-circle-fill'></i>
            <div class='info-box-text'>
                Check your spam folder if you don't receive the email within a few minutes.
            </div>
        </div>

        <?php if ($error): ?>
            <div class='alert alert-danger' role='alert'>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif ($success): ?>
            <div class='alert alert-success' role='alert'>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action='' method='POST' class='verification-form' id='verificationForm'>
            <div class='form-group'>
                <label for='email' class='form-label'>
                    <i class='bi bi-envelope-fill me-2' style='color: var(--accent-color);'></i>
                    Email Address
                </label>
                <input
                    type='email'
                    name='email'
                    id='email'
                    class='form-input'
                    placeholder='your.email@example.com'
                    required
                    value='<?php echo htmlspecialchars($email ?? ''); ?>'
                    autocomplete='email'>
            </div>

            <button type='submit' class='submit-btn' id='submitBtn'>
                <i class='bi bi-send-fill'></i>
                Resend Verification Email
            </button>
        </form>

        <!-- Footer -->
        <div class='verification-footer'>
            <div class='footer-links'>
                <a href='../login.php' class='footer-link'>
                    <i class='bi bi-box-arrow-in-right'></i>
                    Back to Login
                </a>
                <a href='../register.php' class='footer-link'>
                    <i class='bi bi-person-plus'></i>
                    Create Account
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

</html>