<?php
require_once 'includes/db.php';
$output = "DB: OK\n";
try {
    $stmt = $pdo->query("SELECT * FROM rooms");
    $rooms = $stmt->fetchAll();
    $output .= "ROOM COUNT: " . count($rooms) . "\n";
    foreach($rooms as $r) {
        $output .= "- ID: {$r['id']}, NAME: {$r['name']}, CAT_ID: {$r['category_id']}\n";
    }
    
    $stmt = $pdo->query("SELECT * FROM room_categories");
    $cats = $stmt->fetchAll();
    $output .= "CAT COUNT: " . count($cats) . "\n";
    foreach($cats as $c) {
        $output .= "- ID: {$c['id']}, NAME: {$c['name']}\n";
    }
} catch (Exception $e) {
    $output .= "ERROR: " . $e->getMessage() . "\n";
}
file_put_contents('diag_output.txt', $output);
echo "Diagnostics written to diag_output.txt\n";
