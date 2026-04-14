<?php
require_once 'includes/db.php';

$keys = ['brand_name', 'brand_logo', 'footer_phone', 'footer_email', 'footer_address', 'social_facebook', 'social_instagram', 'social_youtube', 'social_whatsapp', 'google_maps_iframe'];

echo "Checking/Creating settings keys...\n";

foreach ($keys as $key) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
    $check->execute([$key]);
    if ($check->fetchColumn() == 0) {
        $insert = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, '')");
        $insert->execute([$key]);
        echo "Created key: $key\n";
    } else {
        echo "Key exists: $key\n";
    }
}
echo "Done.\n";
