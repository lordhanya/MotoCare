<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include __DIR__ . "/../db/connection.php";

$today = date("Y-m-d");

echo "<h3>Reminder Email System - Debug Mode</h3>";
echo "Current Date: " . $today . "<br><br>";

// Query reminders
$sql = "SELECT r.id, r.user_email, r.vehicle_name, r.message, r.user_name
        FROM reminders r
        JOIN users u ON r.user_id = u.id
        WHERE r.reminder_date = ? AND r.is_sent = 0";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $today, PDO::PARAM_STR);
    $stmt->execute();

    $rowCount = $stmt->rowCount();
    echo "<strong>Rows found: " . $rowCount . "</strong><br><br>";

    if ($rowCount == 0) {
        echo "<p style='color: orange;'>No pending reminders for today.</p>";
        exit;
    }

    $successCount = 0;
    $failCount = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<hr>";
        echo "<strong>Processing Reminder ID: {$row['id']}</strong><br>";
        echo "Recipient: {$row['user_email']} ({$row['user_name']})<br>";
        echo "Vehicle: {$row['vehicle_name']}<br><br>";

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME'];
            $mail->Password   = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $mail->Port       = $_ENV['MAIL_PORT'];

            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);

            $mail->Timeout = 60;
            $mail->SMTPKeepAlive = true;

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);  // Use your actual Gmail
            $mail->addAddress($row["user_email"], $row["user_name"]);
            $mail->addReplyTo($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);

            // Content
            $mail->isHTML(true);                                        // Set email format to HTML
            $mail->Subject = "AutoCare Service Reminder";

            // HTML email body
            $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f4f4f4; }
                    .content { background: white; padding: 30px; border-radius: 10px; }
                    .header { color: #f82900; font-size: 24px; font-weight: bold; margin-bottom: 20px; }
                    .message { margin: 20px 0; padding: 15px; background: #fff3f0; border-left: 4px solid #f82900; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='content'>
                        <div class='header'>AutoCare Service Reminder</div>
                        <p>Hello <strong>{$row['user_name']}</strong>,</p>
                        <p>This is a friendly reminder for your vehicle:</p>
                        <h3 style='color: #f82900;'>{$row['vehicle_name']}</h3>
                        <div class='message'>
                            {$row['message']}
                        </div>
                        <p>Thank you for using AutoCare!</p>
                        <div class='footer'>
                            <p>— AutoCare Team<br>
                            This is an automated message. Please do not reply to this email.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            ";

            // Plain text version for email clients that don't support HTML
            $mail->AltBody = "Hello {$row['user_name']},\n\n"
                . "This is a reminder for your vehicle: {$row['vehicle_name']}.\n\n"
                . "{$row['message']}\n\n"
                . "Thank you for using AutoCare!\n\n"
                . "— AutoCare Team";

            // Send email
            if ($mail->send()) {
                echo "<p style='color: green;'>✅ Email sent successfully!</p>";

                // Update database
                $update = $conn->prepare("UPDATE reminders SET is_sent = 1 WHERE id = ?");
                $update->bindValue(1, $row["id"], PDO::PARAM_INT);
                $update->execute();

                echo "<p style='color: blue;'>✅ Database updated - reminder marked as sent</p>";
                $successCount++;
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ <strong>Email Error:</strong> {$mail->ErrorInfo}</p>";
            echo "<p style='color: red;'>Exception: {$e->getMessage()}</p>";
            $failCount++;
        }

        // Clear addresses for next iteration
        $mail->clearAddresses();
        $mail->clearAttachments();

        ob_flush();
        flush();
    }

    echo "<hr>";
    echo "<h3>Summary</h3>";
    echo "<p>✅ Successfully sent: <strong style='color: green;'>$successCount</strong></p>";
    echo "<p>❌ Failed: <strong style='color: red;'>$failCount</strong></p>";
    echo "<p>Total processed: <strong>" . ($successCount + $failCount) . "</strong></p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Database Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<br><p>Script completed at " . date("Y-m-d H:i:s") . "</p>";
