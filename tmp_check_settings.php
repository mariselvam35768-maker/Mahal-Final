<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$out = "SETTINGS TABLE:\n";
foreach($rows as $row) {
    $out .= "- " . $row['setting_key'] . ": " . (strlen($row['setting_value'] ?? '') > 50 ? substr($row['setting_value'], 0, 50) . "..." : ($row['setting_value'] ?? 'NULL')) . "\n";
}
file_put_contents('tmp_output.txt', $out);
echo "Wrote to tmp_output.txt\n";
