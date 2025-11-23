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
echo "=== AutoCare Cron Started at " . date("H:i:s") . " ===\n";
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
    echo "\n--- Sending reminder ID {$row['id']} to {$row['user_email']} ({$fullName}) ---\n";

    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = getenv('MAIL_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('MAIL_USERNAME');
        $mail->Password   = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = getenv('MAIL_ENCRYPTION');
        $mail->Port       = getenv('MAIL_PORT');

        // Sender
        $mail->setFrom(
            getenv('MAIL_FROM_ADDRESS'),
            getenv('MAIL_FROM_NAME')
        );

        // Recipient
        $mail->addAddress($row['user_email'], $fullName);

        $vehicleName = htmlspecialchars($row['vehicle_name'] ?? '');
        $messageHTML = nl2br(htmlspecialchars($row['message'] ?? ''));

        $mail->isHTML(true);
        $mail->Subject = "AutoCare Reminder";

        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: white; border-radius: 10px;  }
                .header { font-size: 1.5em; font-weight: bold; margin-bottom: 20px; }
                .header h1{ color: #383838ff; }
                .header p{ font-size: 1em; }
                span{ color: #f82900; border-bottom: 2px solid #f82900;}
                .message { margin: 20px 0; padding: 15px; font-size: 1em; background: #fff3f0; border-left: 4px solid #f82900; line-height: 20px;}
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 1em; color: #666; line-height: 20px;}
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'><h1>Auto<span>Care</span></h1></div>
                    <p>Hey, <strong>{$fullName}</strong> 👋</p>
                    <h3 style='color: #f82900;'>{$vehicleName}</h3>
                <div class='message'>{$messageHTML}</div>
                    <p>Thank you for using AutoCare!</p>
                <div class='footer'>
                    <p>Best Regards,<br> 
                    Ashif Rahman<br>
                    Creator, AutoCare</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->AltBody = "Hello {$fullName},\n\n{$row['message']}\n\n— AutoCare";

        // Send email
        $mail->send();
        echo "Email sent.\n";

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

echo "\n=== AutoCare Cron Finished at " . date("H:i:s") . " ===\n";
