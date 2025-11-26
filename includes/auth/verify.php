<?php
require __DIR__ . '/../../db/connection.php';
require __DIR__ . '/email_helpers.php';

try {
    if (!isset($_GET['token']) || !isset($_GET['uid'])) {
        throw new Exception("Invalid verification link.");
    }

    $token = $_GET['token'];
    $uid = (int) $_GET['uid'];

    // Fetch user
    $stmt = $conn->prepare("SELECT id, email, verify_token, is_verified FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found.");
    }

    // Already verified
    if ((int)$user['is_verified'] === 1) {
        header("Location: /?verified=already");
        exit;
    }

    // Token mismatch or missing
    if (!$user['verify_token'] || !hash_equals($user['verify_token'], $token)) {
        throw new Exception("Invalid or expired token.");
    }

    // Mark as verified and clear token
    $stmt = $conn->prepare("UPDATE users SET is_verified = 1, verify_token = NULL WHERE id = :id");
    $stmt->execute([':id' => $uid]);

    // Send welcome email
    try {
        $mailer = getMailer();
        $to = $user['email'];
        $subject = "Welcome to MotoCare!";
        $html = '
    <html>
    <head>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #ffffff; 
                background: #000000; 
                margin: 0; 
                padding: 20px; 
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                padding: 0; 
                background: white; 
                border-radius: 12px; 
                box-shadow: 0 2px 10px rgba(0,0,0,0.08); 
                overflow: hidden; 
            }
            .accent-bar { 
                height: 4px; 
                background: linear-gradient(90deg, #f82900 0%, #ff4520 100%); 
            }
            .header { 
                padding: 30px 30px 20px 30px; 
                background: #2a2828; 
                border-bottom: 1px solid #2b2b2b; 
                text-align: center; 
            }
            .header h1 { 
                color: #ffffff; 
                margin: 0; 
                font-size: 2em; 
                font-weight: bold; 
            }
            .header h1 span { 
                color: #f82900; 
                border-bottom: 2px solid #f82900; 
            }
            .content {
                padding: 40px 30px;
                text-align: center; 
            }
            .content h2 { 
                color: #383838; 
                font-size: 1.8em; 
                margin: 0 0 15px 0; 
                font-weight: bold; 
            }
            .content h2 span { 
                color: #f82900; 
            }
            .content p { 
                font-size: 1.05em; 
                color: #b0b0b0; 
                line-height: 1.8; 
                margin: 0 0 30px 0; 
            }
            .cta-button { 
                display: inline-block; 
                padding: 15px 40px; 
                background: #f82900; 
                color: white; 
                text-decoration: none; 
                border-radius: 8px; 
                font-weight: bold; 
                font-size: 1em; 
                letter-spacing: 0.5px; 
                transition: all 0.3s ease; 
            }
            .cta-button:hover { 
                background: #ff4520; 
            }
            .footer { 
                padding: 25px 30px; 
                background: #2a2828; 
                border-top: 1px solid #eee; 
                text-align: center; 
                font-size: 0.9em; 
                color: #b0b0b0; 
            }
            .footer span { 
                color: #f82900; 
                font-weight: bold; 
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="accent-bar"></div>
            
            <div class="header">
                <h1>Moto<span>Care</span></h1>
            </div>
            
            <div class="content">
                
                <h2>Welcome to Moto<span>Care</span></h2>
                
                <p>
                    🎉 Your account has been successfully verified!<br>
                    You can now log in and start managing your vehicle maintenance with ease.
                </p>
                
                <a href="https://motocare.store/includes/login.php" class="cta-button">
                    Login to Your Account
                </a>
            </div>
            
            <div class="footer">
                <p style="margin: 0; font-size: 13px; color: #b0b0b0;">
                    © 2025 MotoCare. All rights reserved.
                </p>
                 <p style="margin: 0; font-size: 13px; color: #b0b0b0;">
                    Powered by Moto<span>Care</span> | Vehicle Maintenance Made Simple
                 </p>
            </div>
        </div>
    </body>
    </html>
';
        $mailer->send($to, $subject, $html, "Welcome to MotoCare. Your account is verified.");
    } catch (Exception $e) {
        log_error("Welcome email failed for user {$uid}: " . $e->getMessage());
    }

    // Redirect to success
    header("Location: ../login.php?verified=success");
    exit;
} catch (Exception $ex) {
    log_error("verify.php error: " . $ex->getMessage());
    header("Location: resend_verificaton.php/?verified=error&message=" . urlencode($ex->getMessage()));
    exit;
}
