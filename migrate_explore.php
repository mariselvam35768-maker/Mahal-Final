<?php
require_once 'includes/auth_functions.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS explore (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_path VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "Explore table created successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
