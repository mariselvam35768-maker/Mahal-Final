<?php
require_once 'c:/xampp/htdocs/Hall-Alocation/includes/db.php';
if (!isset($pdo)) {
    echo "PDO not defined. Error: " . ($error_msg ?? 'Unknown');
    exit;
}
try {
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
    
    $stmt = $pdo->query('DESCRIBE bookings');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Bookings Table Columns:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
