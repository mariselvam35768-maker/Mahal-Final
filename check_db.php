<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE gallery");
$out = "COLUMNS:\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $out .= "- " . $row['Field'] . "\n";
}
file_put_contents('db_status.txt', $out);
echo "Wrote to db_status.txt\n";
