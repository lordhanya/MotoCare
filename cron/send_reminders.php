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
    $html = <<<HTML
        <h1>MotoCare Reminder</h1>
        <p>Hello <strong>{$fullName}</strong>,</p>
        <p>This is your scheduled reminder for your vehicle:</p>

        <h3 style="color:#f82900;">{$vehicle}</h3>

        <div style="border-left:4px solid #f82900;padding:10px;margin:20px 0;">
            {$messageHTML}
        </div>

        <p>Thank you for using <strong>MotoCare</strong>.</p>
HTML;

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
