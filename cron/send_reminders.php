<?php

// Show cron logs (Render)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';

// Load .env in local dev
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Load DB connection
require __DIR__ . '/../db/connection.php';

// Load Resend Mailer
require __DIR__ . '/../includes/auth/email_helpers.php';

echo "=== MotoCare Cron Started — " . date("Y-m-d H:i:s") . " ===\n";

$today = date("Y-m-d");
echo "Checking reminders for: $today\n";

// Fetch pending reminders
$sql = "SELECT id, user_email, vehicle_name, message, first_name, last_name
        FROM reminders
        WHERE reminder_date = ? AND is_sent = 0";

$stmt = $conn->prepare($sql);
$stmt->execute([$today]);

$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($reminders) . " reminders.\n";

if (count($reminders) === 0) {
    echo "No reminders for today.\n";
    exit;
}

$mailer = getMailer();  // Initialize resend once

foreach ($reminders as $row) {

    echo "\n--- Sending reminder #{$row['id']} to {$row['user_email']} ---\n";

    $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
    $vehicle = htmlspecialchars($row['vehicle_name']);
    $messageHTML = nl2br(htmlspecialchars($row['message']));

    // Build HTML body

    $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MotoCare Reminder</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background-color: #000000;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #000000;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" align="center" style="max-width: 600px; width: 100%; background: linear-gradient(180deg, #0a0a0a 0%, #000000 100%); border: 1px solid #2b2b2b; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #0a0a0a; padding: 30px 30px 20px; border-bottom: 3px solid #f82900; text-align: center;">
                            <h1 style="margin: 0 0 8px 0; font-size: 32px; font-weight: 700; color: #ffffff; letter-spacing: 0.5px;">
                                Moto<span style="color: #f82900;">Care</span>
                            </h1>
                            <p style="margin: 0; font-size: 13px; color: #b0b0b0; text-transform: uppercase; letter-spacing: 2px;">
                                Vehicle Maintenance Reminder
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            
                            <!-- Greeting -->
                            <h2 style="margin: 0 0 20px 0; font-size: 20px; font-weight: 600; color: #ffffff;">
                                Hello {$fullName},
                            </h2>
                            
                            <!-- Intro Text -->
                            <p style="margin: 0 0 30px 0; font-size: 15px; color: #b0b0b0; line-height: 1.6;">
                                This is your scheduled reminder for your vehicle:
                            </p>
                            
                            <!-- Vehicle Card -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #2a2828; border: 1px solid #2b2b2b; border-left: 4px solid #f82900; border-radius: 12px; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 8px 0; font-size: 12px; color: #b0b0b0; text-transform: uppercase; letter-spacing: 1px;">
                                            Your Vehicle
                                        </p>
                                        <p style="margin: 0; font-size: 24px; font-weight: 700; color: #f82900;">
                                            {$vehicle}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Message Card -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #121212; border: 1px solid #2b2b2b; border-radius: 12px; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 25px; font-size: 15px; color: #ffffff; line-height: 1.8;">
                                        {$messageHTML}
                                    </td>
                                </tr>
                            </table>
                            
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #0a0a0a; padding: 30px; border-top: 1px solid #2b2b2b; text-align: center;">
                            
                            <p style="margin: 0 0 15px 0; font-size: 14px; color: #b0b0b0; line-height: 1.6;">
                                Thank you for using MotoCare.<br>
                                We help you keep your vehicle in perfect condition.
                            </p>
                            
                            <!-- Divider -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 20px 0;">
                                <tr>
                                    <td style="height: 1px; background: linear-gradient(90deg, transparent, #f82900, transparent);"></td>
                                </tr>
                            </table>
                            
                            <p style="margin: 20px 0 0 0; font-size: 16px; font-weight: 600; color: #ffffff;">
                                Moto<span style="color: #f82900;">Care</span>
                            </p>
                            
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

    try {
        // Send through Resend
        $mailer->send(
            $row['user_email'],
            "MotoCare Reminder - {$vehicle}",
            $html,
            "Reminder for {$vehicle}:\n{$row['message']}"
        );

        echo "Email sent ✓\n";

        // Mark reminder as sent
        $update = $conn->prepare("UPDATE reminders SET is_sent = 1 WHERE id = ?");
        $update->execute([$row['id']]);

        echo "Marked as sent ✓\n";
    } catch (Exception $e) {
        echo "ERROR sending email: " . $e->getMessage() . "\n";
        log_error("Cron reminder error: " . $e->getMessage());
        continue;
    }
}

echo "\n=== MotoCare Cron Finished — " . date("Y-m-d H:i:s") . " ===\n";
