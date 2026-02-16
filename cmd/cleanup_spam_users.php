<?php
/**
 * One-time cleanup script to remove spam user accounts.
 * Targets inactive users with URLs stuffed into name/profession/employer fields.
 *
 * Usage: php cmd/cleanup_spam_users.php
 */

$dbPath = __DIR__ . '/../var/main.db';

if (!file_exists($dbPath)) {
    echo "Database not found at: $dbPath\n";
    exit(1);
}

$db = new SQLite3($dbPath);
$db->busyTimeout(5000);

// Find spam users: inactive accounts with URLs in text fields
$query = "SELECT id, email, name, profession, employer FROM users
    WHERE active = 0
    AND (name LIKE '%http%' OR profession LIKE '%http%' OR employer LIKE '%http%')";

$result = $db->query($query);
$spamIds = [];

echo "Spam users found:\n";
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $spamIds[] = $row['id'];
    echo "  ID {$row['id']}: {$row['email']} | name: {$row['name']}\n";
}

if (empty($spamIds)) {
    echo "No spam users found.\n";
    exit(0);
}

echo "\nTotal: " . count($spamIds) . " spam users\n";

// Delete ACL entries for these users
$placeholders = implode(',', array_fill(0, count($spamIds), '?'));

$stmt = $db->prepare("DELETE FROM acl WHERE user_id IN ($placeholders)");
foreach ($spamIds as $i => $id) {
    $stmt->bindValue($i + 1, $id, SQLITE3_INTEGER);
}
$stmt->execute();
$aclDeleted = $db->changes();
echo "Deleted $aclDeleted ACL entries.\n";

// Delete the spam users
$stmt = $db->prepare("DELETE FROM users WHERE id IN ($placeholders)");
foreach ($spamIds as $i => $id) {
    $stmt->bindValue($i + 1, $id, SQLITE3_INTEGER);
}
$stmt->execute();
$usersDeleted = $db->changes();
echo "Deleted $usersDeleted spam users.\n";

$db->close();
echo "Done.\n";
