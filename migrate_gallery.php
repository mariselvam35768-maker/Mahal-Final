<?php
require_once 'includes/auth_functions.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_path VARCHAR(255) NOT NULL,
        title VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Gallery table created successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
