<?php
require_once 'includes/db.php';
$output = "Halls columns:\n";
$s = $pdo->query("SHOW COLUMNS FROM halls");
while($r = $s->fetch()) {
    $output .= $r['Field'] . "\n";
}
file_put_contents('halls_cols.txt', $output);
?>
