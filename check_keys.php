<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT * FROM gallery LIMIT 1");
$row = $stmt->fetch();
if ($row) {
    echo "KEYS: " . implode(', ', array_keys($row)) . "\n";
} else {
    echo "No rows in gallery\n";
}
