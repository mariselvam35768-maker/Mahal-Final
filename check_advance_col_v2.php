<?php
require_once 'includes/db.php';
$output = "";
$tables = ['halls', 'rooms', 'bookings'];
foreach ($tables as $table) {
    $output .= "Columns in '$table':\n";
    try {
        $stmt = $pdo->query("DESCRIBE `$table` ");
        while ($row = $stmt->fetch()) {
            $output .= "- " . $row['Field'] . "\n";
        }
    } catch (Exception $e) {
        $output .= "Error: " . $e->getMessage() . "\n";
    }
    $output .= "\n";
}
file_put_contents('db_columns.txt', $output);
?>
