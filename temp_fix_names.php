<?php
require_once 'includes/db.php';

try {
    // 1. Rename the hall in the database
    $stmt = $pdo->prepare("UPDATE halls SET name = ? WHERE name LIKE ?");
    $stmt->execute(['SLR & Mahal', '%Subha Mahal%']);
    $count = $stmt->rowCount();
    echo "Halls updated: $count\n";

    // 2. Update the brand name in settings table
    $stmt2 = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'brand_name'");
    $stmt2->execute(['SLR & Mahal']);
    echo "Brand name updated in settings\n";

    // 3. Verify current halls
    $stmt3 = $pdo->query("SELECT name FROM halls");
    echo "Current Halls:\n";
    while($row = $stmt3->fetch()) {
        echo "- " . $row['name'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
