<?php
/**
 * Migration: Add slot pricing columns to halls table.
 * Run once, then delete this file.
 */
require_once 'includes/db.php';

$queries = [
    "ALTER TABLE halls ADD COLUMN IF NOT EXISTS morning_slot_price DECIMAL(10,2) DEFAULT 0",
    "ALTER TABLE halls ADD COLUMN IF NOT EXISTS evening_slot_price DECIMAL(10,2) DEFAULT 0",
    "ALTER TABLE halls ADD COLUMN IF NOT EXISTS advance_amount DECIMAL(10,2) DEFAULT 0",
];

echo "<h3>Running Hall Pricing Migration...</h3>";
foreach ($queries as $q) {
    try {
        $pdo->exec($q);
        echo "<p style='color:green;'>✅ OK: " . htmlspecialchars($q) . "</p>";
    } catch (Exception $e) {
        // Check if it's a duplicate column error (already exists)
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color:orange;'>⚠️ Column already exists, skipping.</p>";
        } else {
            echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
        }
    }
}

// Set defaults for existing halls that have 0 values
try {
    $pdo->exec("UPDATE halls SET morning_slot_price = ROUND(price_per_day * 0.55) WHERE morning_slot_price = 0 AND price_per_day > 0");
    $pdo->exec("UPDATE halls SET evening_slot_price = ROUND(price_per_day * 0.55) WHERE evening_slot_price = 0 AND price_per_day > 0");
    $pdo->exec("UPDATE halls SET advance_amount = ROUND(price_per_day * 0.30) WHERE advance_amount = 0 AND price_per_day > 0");
    echo "<p style='color:green;'>✅ Default pricing applied to existing halls.</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error applying defaults: " . $e->getMessage() . "</p>";
}

echo "<h3>Migration Complete! ✅</h3>";
echo "<p><a href='admin/manage_halls.php'>Go to Manage Halls →</a></p>";
