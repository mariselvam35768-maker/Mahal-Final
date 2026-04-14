<?php
require_once 'includes/db.php';

// All required keys
$keys = [
    'brand_name', 'brand_logo', 'footer_phone', 'footer_email', 'footer_address', 
    'social_facebook', 'social_instagram', 'social_youtube', 'social_whatsapp', 'google_maps_iframe'
];

echo "REPAIRING SETTINGS TABLE...\n";

foreach ($keys as $key) {
    if (isset($pdo)) {
        try {
            $check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
            $check->execute([$key]);
            if ($check->fetchColumn() == 0) {
                // Determine a sensible default
                $default = '';
                if ($key == 'brand_name') $default = 'Sri Lakshmi Residency & Mahal';
                if ($key == 'footer_phone') $default = '+91 98765 43210';
                if ($key == 'footer_email') $default = 'slr@gmail.com';
                
                $insert = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $insert->execute([$key, $default]);
                echo "[FIXED] Created key: $key\n";
            } else {
                echo "[OK] Key exists: $key\n";
            }
        } catch (Exception $e) {
            echo "[ERROR] Failed for $key: " . $e->getMessage() . "\n";
        }
    }
}
echo "REPAIR COMPLETE.\n";
