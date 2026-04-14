<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT COUNT(*) FROM rooms");
$cnt = $stmt->fetchColumn();
echo "ACTUAL ROOM COUNT: " . $cnt . "\n";
file_put_contents('actual_count.txt', "ACTUAL ROOM COUNT: " . $cnt);
