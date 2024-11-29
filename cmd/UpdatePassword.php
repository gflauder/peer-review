<?php
// Ensure that the script is run from the command line with two arguments
if ($argc !== 3) {
    echo "Usage: php update_password.php <user_id> <new_password>" . PHP_EOL;
    exit(1);
}

// Retrieve arguments
$userId = $argv[1];
$newPassword = $argv[2];

// Hash the provided new password
$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

// Database file
$dbFile = dirname(__DIR__) . '/var/main.db';
echo "Database file: {$dbFile}" . PHP_EOL;
try {
    // Open a connection to the SQLite database
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Update password in the database for the specified user ID
    $statement = $pdo->prepare("UPDATE users SET passwordHash = :password WHERE id = :id");
    $statement->bindParam(':password', $hashedPassword);
    $statement->bindParam(':id', $userId, PDO::PARAM_INT);
    $statement->execute();

    echo "Password updated successfully for user ID {$userId}." . PHP_EOL;
} catch (PDOException $e) {
    echo "An error occurred: " . $e->getMessage() . PHP_EOL;
}