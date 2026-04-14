<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT name FROM halls");
echo "HALLS:\n";
while($row = $stmt->fetch()) {
    echo "- " . $row['name'] . "\n";
}
$stmt2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'brand_name'");
echo "BRAND: " . $stmt2->fetchColumn() . "\n";
