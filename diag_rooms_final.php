<?php
require_once 'includes/db.php';
echo "DB Connection Status: " . (isset($pdo) ? 'OK' : 'FAILED') . "\n";
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM rooms");
        $all = $stmt->fetchAll();
        echo "ROOM COUNT: " . count($all) . "\n";
        foreach($all as $r) {
            echo "ID: {$r['id']} NAME: {$r['name']} CAT: " . ($r['category_id'] ?? 'NONE') . "\n";
        }
    } catch(Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
echo "END\n";
