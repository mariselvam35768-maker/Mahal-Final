<?php
require_once 'includes/db.php';
$tables = ['halls', 'rooms', 'bookings'];
foreach ($tables as $table) {
    echo "Columns in '$table':\n";
    try {
        $stmt = $pdo->query("DESCRIBE `$table` ");
        while ($row = $stmt->fetch()) {
            echo "- " . $row['Field'] . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
?>
