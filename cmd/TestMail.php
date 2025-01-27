<?php

global $PPHP;
$db = $PPHP['db'];
$config = $PPHP['config']['articles'];

$isProduction = $config['production']; // Production flag

// Fetch a random user from the database
function getRandomUser($db) {
    $query = $db->prepare('SELECT * FROM users ORDER BY RAND() LIMIT 1');
    $query->execute();
    $user = $query->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("No users found in the database.");
    }

    return [
        'name' => "{$user['first_name']} {$user['last_name']}",
        'email' => $user['email'],
    ];
}

// Replace placeholders in email templates
function replacePlaceholders($templateContent, $placeholders) {
    foreach ($placeholders as $key => $value) {
        $templateContent = str_replace("{{ $key }}", $value, $templateContent); // Replace {{ key }} with value
    }
    return $templateContent;
}

// Send email using system Sendmail
function sendEmail($to, $subject, $body) {
    $headers = "From: no-reply@example.com\r\n"; // Example From Address
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Use PHP's mail() function, which interacts with the system Sendmail
    if (mail($to, $subject, $body, $headers)) {
        echo "Email sent successfully to $to<br>";
    } else {
        echo "Failed to send email to $to<br>";
    }
}

// Web-accessible script logic
function runEmailTests($templateDirectory, $db, $isProduction) {
    // Ensure the template directory exists
    if (!is_dir($templateDirectory)) {
        echo "Error: Template directory \"$templateDirectory\" does not exist.<br>";
        exit(1);
    }

    // Collect all .twig templates in the directory
    $templates = array_filter(scandir($templateDirectory), function ($file) use ($templateDirectory) {
        return is_file($templateDirectory . DIRECTORY_SEPARATOR . $file) && preg_match('/\.twig$/', $file);
    });

    if (empty($templates)) {
        echo "No templates found in \"$templateDirectory\".<br>";
        return;
    }

    // Loop through each template
    foreach ($templates as $template) {
        echo "Processing template: $template<br>";

        // Read the template content
        $templateContent = file_get_contents($templateDirectory . DIRECTORY_SEPARATOR . $template);

        // Static placeholders for testing
        $placeholders = [
            'article' => 'Example Article',
            'completionLink' => 'https://example.com/review',
        ];

        // Fetch a random user to inject into the email
        try {
            $user = getRandomUser($db);
            $placeholders['user'] = $user['name']; // Add formatted user for placeholder replacement
        } catch (Exception $e) {
            echo "Error fetching user: " . $e->getMessage() . "<br>";
            continue;
        }

        // Replace placeholders in the template
        $emailBody = replacePlaceholders($templateContent, $placeholders);

        // Define email metadata
        $subject = "Email Test: $template";
        $to = "{$user['name']} <{$user['email']}>";

        // Skip in production
        if ($isProduction) {
            echo "Production mode enabled. Emails will not be sent.<br>";
            return;
        }

        // Send the email
        sendEmail($to, $subject, $emailBody);
    }

    echo "All email templates have been processed.<br>";
}

// Trigger the script
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $templateDir = '/var/www/html/templates'; // Static value or configurable
    echo "<h1>Running Email Tests</h1>";
    runEmailTests($templateDir, $db, $isProduction);
}