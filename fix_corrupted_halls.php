<?php
require_once 'includes/db.php';

try {
    // 1. Try to drop the corrupted table
    echo "Attempting to drop corrupted table 'halls'...\n";
    $pdo->exec("DROP TABLE IF EXISTS halls");
    echo "Table dropped (or confirmed non-existent).\n";

    // 2. Re-create the table from schema
    echo "Re-creating table 'halls'...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            location VARCHAR(255) NOT NULL,
            capacity INT NOT NULL,
            price_per_day DECIMAL(10, 2) NOT NULL,
            description TEXT,
            facilities TEXT,
            main_image VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");
    echo "Table 'halls' re-created successfully.\n";

    // 3. Add back some sample data just in case
    $pdo->exec("
        INSERT INTO halls (name, location, capacity, price_per_day, description, facilities) VALUES 
        ('Grand Royal Ballroom', 'T. Nagar, Chennai', 500, 150000.00, 'A premium ballroom for grand weddings.', 'AC, Dining Hall, Parking, Generator, Decoration')
    ");
    echo "Sample hall data added.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "You might need to manually delete the 'halls.ibd' file from your MySQL data folder and restart MySQL if this persists.\n";
}
