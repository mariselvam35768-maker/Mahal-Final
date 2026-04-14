<?php
require_once 'includes/db.php';
$stmt = $pdo->query('SHOW COLUMNS FROM rooms');
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
file_put_contents('actual_cols.txt', implode("\n", $cols));
