<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT * FROM gallery LIMIT 1");
$row = $stmt->fetch();
if ($row) {
    file_put_contents('keys_status.txt', "KEYS: " . implode(', ', array_keys($row)));
} else {
    file_put_contents('keys_status.txt', "No rows in gallery");
}
