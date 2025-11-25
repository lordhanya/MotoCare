<?php
// ————— Cron Job Version for Render —————

// Display all errors (useful in Render cron logs)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Composer (PHPMailer + Dotenv)
require __DIR__ . '/../vendor/autoload.php';

// Load .env (local dev) – Render uses env vars
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../');
    $dotenv->load();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// DB connection (uses getenv() inside your connection.php)
require __DIR__ . '/../db/connection.php';

$today = date("Y-m-d");
echo "=== MotoCare Cron Started at " . date("H:i:s") . " ===\n";
echo "Checking reminders for date: $today\n";

// Fetch pending reminders
$sql = "SELECT id, user_email, vehicle_name, message, first_name, last_name
        FROM reminders
        WHERE reminder_date = ? AND is_sent = 0";

$stmt = $conn->prepare($sql);
$stmt->bindParam(1, $today, PDO::PARAM_STR);
$stmt->execute();

$count = $stmt->rowCount();
echo "Reminders found: $count\n";

if ($count == 0) {
    echo "No reminders due. Exiting.\n";
    exit;
}

// Loop through reminders
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
    $vehicleName = htmlspecialchars($row['vehicle_name'] ?? 'Your Vehicle');
    $messageHTML = nl2br(htmlspecialchars($row['message'] ?? ''));

    echo "\n--- Sending reminder ID {$row['id']} to {$row['user_email']} ({$fullName}) ---\n";

    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
        $mail->Port       = $_ENV['MAIL_PORT'];

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);


        // Recipient
        $mail->addAddress($row['user_email'], $fullName);

        $mail->isHTML(true);
        $mail->Subject = "MotoCare Reminder - {$vehicleName}";

        $mail->Body = '
        <html>
        <head>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    background: #f5f5f5; 
                    margin: 0; 
                    padding: 20px; 
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 Moto; 
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
                    background: #fafafa; 
                    border-bottom: 1px solid #eee; 
                    text-align: center; 
                }
                .header h1 { 
                    color: #383838; 
                    margin: 0; 
                    font-size: 2em; 
                    font-weight: bold; 
                }
                .header h1 span { 
                    color: #f82900; 
                    border-bottom: 2px solid #f82900; 
                    padding-bottom: 2px; 
                }
                .content { 
                    padding: 30px; 
                }
                .greeting { 
                    font-size: 1.1em; 
                    margin-bottom: 15px; 
                    color: #333; 
                }
                .greeting strong { 
                    color: #f82900; 
                }
                .vehicle-name { 
                    color: #f82900; 
                    font-size: 1.3em; 
                    font-weight: bold; 
                    margin: 10px 0 20px 0; 
                    padding: 12px 20px; 
                    background: #fff3f0; 
                    border-left: 4px solid #f82900; 
                    border-radius: 4px; 
                }
                .message { 
                    margin: 25px 0; 
                    padding: 20px; 
                    font-size: 1em; 
                    background: #fff3f0; 
                    border-left: 4px solid #f82900; 
                    border-radius: 4px; 
                    line-height: 1.8; 
                }
                .message p { 
                    margin: 10px 0; 
                }
                .message strong { 
                    color: #f82900; 
                }
                .thank-you { 
                    text-align: center; 
                    font-size: 1.05em; 
                    color: #555; 
                    margin: 25px 0; 
                    padding: 15px; 
                    background: #f9f9f9; 
                    border-radius: 6px; 
                }
                .footer { 
                    margin-top: 30px; 
                    padding: 25px 30px; 
                    background: #fafafa; 
                    border-top: 1px solid #eee; 
                    font-size: 0.95em; 
                    color: #666; 
                    line-height: 1.8; 
                }
                .footer p { 
                    margin: 5px 0; 
                }
                .footer strong { 
                    color: #383838; 
                }
                .footer-branding { 
                    text-align: center; 
                    margin-top: 20px; 
                    padding-top: 20px; 
                    border-top: 1px solid #ddd; 
                    font-size: 0.85em; 
                    color: #999; 
                }
                .footer-branding span { 
                    color: #f82900; 
                    border: none; 
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
                    <p class="greeting">Hey, <strong>' . htmlspecialchars($fullName) . '</strong> 👋</p>

                    <h3 class="vehicle-name">🚗 ' . $vehicleName . '</h3>

                    <div class="message">
                        ' . $messageHTML . '
                    </div>

                    <p class="thank-you">
                        ✨ <strong>Thank you for using MotoCare!</strong>
                    </p>
                </div>

                <div class="footer">
                    <p><strong>Best Regards,</strong></p>
                    <p>Ashif Rahman<br>Creator, MotoCare</p>

                    <div class="footer-branding">
                        Powered by <span>MotoCare</span> | Vehicle Maintenance Made Simple
                    </div>
                </div>
            </div>
        </body>
        </html>
        ';

        $mail->AltBody = "Hello {$fullName},\n\nVehicle: {$vehicleName}\n\n{$row['message']}\n\n— MotoCare";

        // Send email
        $mail->send();
        echo "Email sent successfully.\n";

        // Mark reminder as sent
        $update = $conn->prepare("UPDATE reminders SET is_sent = 1 WHERE id = ?");
        $update->bindValue(1, $row['id'], PDO::PARAM_INT);
        $update->execute();

        echo "Reminder marked as sent.\n";
    } catch (Exception $e) {
        echo "Mailer error: {$mail->ErrorInfo}\n";
    }

    // Clean PHPMailer
    $mail->clearAddresses();
    $mail->clearAttachments();
}

echo "\n=== MotoCare Cron Finished at " . date("H:i:s") . " ===\n";
