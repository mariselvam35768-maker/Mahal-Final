<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("SELECT * FROM rooms");
    $all = $stmt->fetchAll();
    echo "Found " . count($all) . " rooms in database.\n";
    foreach($all as $r) {
        echo "- " . $r['name'] . " (Cat ID: " . ($r['category_id'] ?? 'NULL') . ")\n";
    }
} catch (Exception $e) { echo "ERROR: " . $e->getMessage(); }
