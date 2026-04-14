<?php
require_once 'includes/db.php';
$output = "";
$output .= "Tables in database:\n";
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $output .= "- " . $row[0] . "\n";
}

$output .= "\nColumns in 'users' table:\n";
try {
    $stmt = $pdo->query("DESCRIBE users");
    while ($row = $stmt->fetch()) {
        $output .= "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    $output .= "Users table check failed: " . $e->getMessage() . "\n";
}

$output .= "\nColumns in 'admin' table:\n";
try {
    $stmt = $pdo->query("DESCRIBE admin");
    while ($row = $stmt->fetch()) {
        $output .= "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    $output .= "Admin table check failed: " . $e->getMessage() . "\n";
}

file_put_contents('debug_auth_log.txt', $output);
echo "Output written to debug_auth_log.txt";
?>
