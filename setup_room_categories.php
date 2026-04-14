<?php
require_once __DIR__ . '/includes/auth_functions.php';

try {
    // 1. Create room_categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS room_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            icon VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 2. Add category_id to rooms table
    $pdo->exec("
        ALTER TABLE rooms 
        ADD COLUMN category_id INT AFTER id,
        ADD FOREIGN KEY (category_id) REFERENCES room_categories(id);
    ");

    // 3. Pre-populate categories
    $categories = [
        ['VIP Suite', 'Premium rooms with luxury amenities and best views.', 'fas fa-crown'],
        ['Deluxe Room', 'Spacious rooms with modern facilities.', 'fas fa-award'],
        ['Standard Room', 'Comfortable rooms for a pleasant stay.', 'fas fa-door-open'],
        ['Suite Rooms', 'Large suites with living areas.', 'fas fa-star'],
        ['Guest Rooms', 'Rooms for event guests and families.', 'fas fa-users'],
        ['Bridal Room', 'Special rooms for the bride and groom.', 'fas fa-heart'],
        ['Economy Room', 'Budget-friendly rooms for shorter stays.', 'fas fa-wallet']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO room_categories (name, description, icon) VALUES (?, ?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }

    echo "Room categories system initialized successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
