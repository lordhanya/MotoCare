<?php
require_once __DIR__ . '/../ResendMailer.php';

function getMailer()
{
    $apiKey = getenv('RESEND_API_KEY') ?: ($_ENV['RESEND_API_KEY'] ?? null);
    $from = getenv('FROM_EMAIL') ?: ($_ENV['FROM_EMAIL'] ?? 'MotoCare <autocare.service.app@gmail.com>');

    if (!$apiKey) {
        throw new Exception("RESEND_API_KEY not set in environment.");
    }
    return new ResendMailer($apiKey, $from);
}

function log_error($message)
{
    $logfile = __DIR__ . '/../../logs/email_errors.log';
    @mkdir(dirname($logfile), 0755, true);
    $time = date('Y-m-d H:i:s');
    file_put_contents($logfile, "[$time] $message\n", FILE_APPEND);
}
