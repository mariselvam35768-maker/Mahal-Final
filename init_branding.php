<?php
require_once 'c:/xampp/htdocs/Hall-Alocation/includes/db.php';

try {
    // Ensure rows exist for branding
    $defaults = [
        'brand_name' => 'Sri Lakshmi Residency & Mahal',
        'brand_logo' => '',
        'footer_address' => '123, Main Road, Srivilliputhur, Tamil Nadu'
    ];

    foreach ($defaults as $key => $val) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if ($stmt->fetchColumn() == 0) {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)")->execute([$key, $val]);
        }
    }
    echo "Branding settings initialized successfully.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
