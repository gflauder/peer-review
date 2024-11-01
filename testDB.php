<?php
$dbFile = 'var/main.db';
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Database connection parameters
    $dsn = 'sqlite:var/main.db';
    $user = null; // SQLite does not require a username
    $pass = null; // SQLite does not require a password

    // Create a new PDO instance
    $db = new PDO($dsn, $user, $pass);

    // Check the connection
    if ($db) {
        echo "Database connection established successfully.\n";
    } else {
        echo "Failed to connect to the database.\n";
    }
} catch (PDOException $e) {
    // Catch any connection errors
    echo "Connection failed: " . $e->getMessage() . "\n";
}



// Check the locking mode
exec("sqlite3 $dbFile 'PRAGMA locking_mode;'", $output, $return_var);

if ($return_var === 0) {
    echo "Locking mode: " . implode("\n", $output) . "\n";
} else {
    echo "Failed to check locking mode.\n";
}

// Check for lock files
$lockFiles = glob(dirname($dbFile) . '/*-journal');
$lockFiles = array_merge($lockFiles, glob(dirname($dbFile) . '/*-wal'));

if (!empty($lockFiles)) {
    echo "Lock files found:\n";
    foreach ($lockFiles as $file) {
        echo $file . "\n";
    }
} else {
    echo "No lock files found.\n";
}
?>
