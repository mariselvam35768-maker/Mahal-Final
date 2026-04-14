<?php
require_once 'includes/db.php';
try {
    // List of columns to ensure
    $cols = [
        ['is_full_day', 'TINYINT(1) DEFAULT 0'],
        ['guest_count', 'INT DEFAULT 0'],
        ['advance_amount', 'DECIMAL(10,2) DEFAULT 0.00'],
        ['status', "VARCHAR(20) DEFAULT 'pending'"]
    ];

    foreach ($cols as $col) {
        $name = $col[0];
        $def = $col[1];
        try {
            $pdo->exec("ALTER TABLE bookings ADD COLUMN $name $def");
            echo "Added $name column.\n";
        } catch (PDOException $e) {
            echo "$name column might already exist.\n";
        }
    }
    echo "Database sync complete.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
