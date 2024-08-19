<?php
$to = 'recipient@example.com';
$subject = 'Test Email';
$message = 'This is a test email.';
$headers = 'From: sender@example.com' . "\r\n" .
           'Reply-To: sender@example.com' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

if (mail($to, $subject, $message, $headers)) {
    echo 'Mail sent successfully.';
} else {
    echo 'Mail sending failed.';
}
?>