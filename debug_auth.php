<?php
require_once 'includes/db.php';

echo "Tables in database:\n";
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    echo "- " . $row[0] . "\n";
}

echo "\nColumns in 'users' table:\n";
$stmt = $pdo->query("DESCRIBE users");
while ($row = $stmt->fetch()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\nColumns in 'admin' table:\n";
try {
    $stmt = $pdo->query("DESCRIBE admin");
    while ($row = $stmt->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Admin table check failed: " . $e->getMessage() . "\n";
}

echo "\nChecking a few users:\n";
$stmt = $pdo->query("SELECT id, name, email, phone, role FROM users LIMIT 5");
while ($row = $stmt->fetch()) {
    print_r($row);
}

echo "\nChecking a few admins:\n";
try {
    $stmt = $pdo->query("SELECT id, name, email, role FROM admin LIMIT 5");
    while ($row = $stmt->fetch()) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Admin records check failed: " . $e->getMessage() . "\n";
}
?>
