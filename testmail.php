<?php
error_reporting(E_ALL); // Show all errors
ini_set('display_errors', 1); // Ensure errors are displayed

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

// SMTP2GO credentials
$smtpHost = 'mail.smtp2go.com';
$smtpUsername = 'crimetest';
$smtpPassword = 'vopy1J9fU9j4JnI5';
$smtpPort = 587; // Change to 2525 or 465 if needed
$smtpSecure = 'tls'; // Change to 'ssl' for port 465

// Recipient
$toEmail = 'gflauder@hotmail.com';
$toName = 'Gary';

// Debugging settings
$debugLevel = 3; // 0 = off, 1 = commands, 2 = data and responses, 3 = full interaction

$mail = new PHPMailer(true);

try {
    echo "Initializing PHPMailer...\n";
    flush();

    // Enable detailed debugging
    $mail->SMTPDebug = $debugLevel;
    $mail->Debugoutput = function ($str, $level) {
        echo "SMTP Debug [$level]: $str\n"; // Print all debug output to screen
        flush(); // Force immediate output
    };

    // SMTP Configuration
    echo "Configuring SMTP settings...\n";
    flush();
    
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUsername;
    $mail->Password   = $smtpPassword;
    $mail->Port       = $smtpPort;

    // Set encryption type
    if ($smtpSecure === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($smtpSecure === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = '';
    }

    // Sender info
    $mail->setFrom('test@ela.solutions', 'Crime Test');

    // Recipient
    $mail->addAddress($toEmail, $toName);

    // Email Content
    $mail->isHTML(false);
    $mail->Subject = 'SMTP2GO PHPMailer Test';
    $mail->Body    = 'This is a test email sent from PHPMailer using SMTP2GO.';

    // Send email
    echo "Attempting to send email...\n";
    flush();
    
    if ($mail->send()) {
        echo "✅ Email sent successfully!\n";
    } else {
        echo "❌ Failed to send email.\n";
    }
} catch (Exception $e) {
    echo "❌ Email could not be sent. Error: {$mail->ErrorInfo}\n";
}
