<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("SELECT * FROM rooms");
    $rooms = $stmt->fetchAll();
    echo "COUNT: " . count($rooms) . "\n";
    foreach($rooms as $r) {
        echo "- {$r['name']} (ID: {$r['id']})\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
