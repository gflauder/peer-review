<?php

/**
 * Data cleanup utility
 * Cleans file paths in database tables
 */

// Load dependencies provided by Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration from var/config.ini
if (file_exists(__DIR__ . '/../var/config.ini')) {
    $config = parse_ini_file(__DIR__ . '/../var/config.ini', true);
} else {
    die("Configuration file not found at var/config.ini!\n");
}

// Initialize PDO database connection
$dbFile = $config['db']['sqlite_path'] ?? 'var/main.db';
if (!file_exists(__DIR__ . '/../' . $dbFile)) {
    die("Database file not found at {$dbFile}!\n");
}

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

function cleanFilePath($path) {
    // Remove /var/www/html/ prefix if present
    if (strpos($path, '/var/www/html/') === 0) {
        $path = substr($path, strlen('/var/www/html/'));
    }

    // Remove leading slash
    $path = ltrim($path, '/');

    // Only return if it's a proper var/articles path
    if (strpos($path, 'var/articles/') === 0) {
        return $path;
    }

    return null; // Invalid path format
}

// Clean articleVersions table
echo "Cleaning articleVersions table...\n";
$stmt = $db->query("SELECT id, files FROM articleVersions WHERE files IS NOT NULL AND files != ''");
$versions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($versions as $version) {
    $files = json_decode($version['files'], true);
    if (!is_array($files)) continue;

    $updated = false;
    $validFiles = [];

    foreach ($files as $file) {
        if (!isset($file['path'])) continue;

        $originalPath = $file['path'];
        $cleanPath = cleanFilePath($originalPath);

        if ($cleanPath) {
            $file['path'] = $cleanPath;
            $validFiles[] = $file;

            if ($cleanPath !== $originalPath) {
                echo "articleVersions ID {$version['id']}: {$originalPath} -> {$cleanPath}\n";
                $updated = true;
            }
        } else {
            echo "articleVersions ID {$version['id']}: REMOVED invalid path: {$originalPath}\n";
            $updated = true;
        }
    }

    if ($updated) {
        $updateStmt = $db->prepare("UPDATE articleVersions SET files = ? WHERE id = ?");
        $updateStmt->execute([json_encode($validFiles), $version['id']]);
    }
}

// Clean emails table
echo "\nCleaning emails table...\n";
$stmt = $db->query("SELECT id, files FROM emails WHERE files IS NOT NULL AND files != ''");
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($emails as $email) {
    $files = json_decode($email['files'], true);
    if (!is_array($files)) continue;

    $updated = false;
    $validFiles = [];

    foreach ($files as $file) {
        if (!isset($file['path'])) continue;

        $originalPath = $file['path'];
        $cleanPath = cleanFilePath($originalPath);

        if ($cleanPath) {
            $file['path'] = $cleanPath;
            $validFiles[] = $file;

            if ($cleanPath !== $originalPath) {
                echo "emails ID {$email['id']}: {$originalPath} -> {$cleanPath}\n";
                $updated = true;
            }
        } else {
            echo "emails ID {$email['id']}: REMOVED invalid path: {$originalPath}\n";
            $updated = true;
        }
    }

    if ($updated) {
        $updateStmt = $db->prepare("UPDATE emails SET files = ? WHERE id = ?");
        $updateStmt->execute([json_encode($validFiles), $email['id']]);
    }
}

echo "\nCleanup completed!\n";