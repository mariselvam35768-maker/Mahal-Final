<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

$msg = '';
$error = '';

// Fetch current settings
try {
    $keys = ['brand_name', 'brand_logo', 'footer_phone', 'footer_email', 'footer_address', 'social_facebook', 'social_instagram', 'social_youtube', 'social_whatsapp', 'google_maps_iframe'];
    $placeholders = str_repeat('?,', count($keys) - 1) . '?';
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)");
    $stmt->execute($keys);
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $current_name = $settings['brand_name'] ?? 'Sri Lakshmi Residency & Mahal';
    $current_logo = $settings['brand_logo'] ?? '';
    $f_phone = $settings['footer_phone'] ?? '+91 98765 43210';
    $f_email = $settings['footer_email'] ?? 'slr@gmail.com';
    $f_address = $settings['footer_address'] ?? '123, Main Road, Srivilliputhur, Tamil Nadu';
    $s_fb = $settings['social_facebook'] ?? '#';
    $s_ig = $settings['social_instagram'] ?? '#';
    $s_yt = $settings['social_youtube'] ?? '#';
    $s_wa = $settings['social_whatsapp'] ?? 'https://wa.me/919876543210';
    $s_map = $settings['google_maps_iframe'] ?? '';
    $r_title = $settings['rooms_explore_title'] ?? 'Luxury Guest Rooms';
    $r_image = $settings['rooms_explore_image'] ?? '';
} catch (Exception $e) {
    $current_name = 'Sri Lakshmi Residency & Mahal';
    $current_logo = '';
    // others fall back to defaults
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success_count = 0;
    
    // Handle Simple Settings
    $text_settings = [
        'brand_name' => 'Brand name',
        'footer_phone' => 'Phone number',
        'footer_email' => 'Email address',
        'footer_address' => 'Street Address',
        'social_facebook' => 'Facebook link',
        'social_instagram' => 'Instagram link',
        'social_youtube' => 'YouTube link',
        'social_whatsapp' => 'WhatsApp link',
        'google_maps_iframe' => 'Maps Iframe',
        'rooms_explore_title' => 'Rooms Section Title'
    ];

    foreach ($text_settings as $key => $label) {
        if (isset($_POST[$key])) {
            $val = trim($_POST[$key]);
            
            // Auto-extract src if user pastes whole iframe for Google Maps
            if ($key === 'google_maps_iframe') {
                if (preg_match('/src="([^"]+)"/', $val, $matches)) {
                    $val = $matches[1];
                } elseif (preg_match('/src=\'([^\']+)\'/', $val, $matches)) {
                    $val = $matches[1];
                }
                $_POST[$key] = $val; // Update so the refresh loop below gets the clean URL
            }

            try {
                $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, $val]);
                $success_count++;
            } catch (Exception $e) {
                $error = "Failed to update $label: " . $e->getMessage();
            }
        }
    }

    if ($success_count > 0) {
        $msg = 'Settings updated successfully!';
        // Refresh values after update
        foreach($text_settings as $key => $l) {
            if(isset($_POST[$key])) {
                if($key == 'brand_name') $current_name = $_POST[$key];
                if($key == 'footer_phone') $f_phone = $_POST[$key];
                if($key == 'footer_email') $f_email = $_POST[$key];
                if($key == 'footer_address') $f_address = $_POST[$key];
                if($key == 'social_facebook') $s_fb = $_POST[$key];
                if($key == 'social_instagram') $s_ig = $_POST[$key];
                if($key == 'social_youtube') $s_yt = $_POST[$key];
                if($key == 'social_whatsapp') $s_wa = $_POST[$key];
                if($key == 'google_maps_iframe') $s_map = $_POST[$key];
                if($key == 'rooms_explore_title') $r_title = $_POST[$key];
            }
        }
    }

    // Handle Logo Upload
    if (isset($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] === 0) {
        $upload_dir = '../assets/images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES['brand_logo']['name'], PATHINFO_EXTENSION);
        $new_logo_filename = 'logo_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_logo_filename;

        if (move_uploaded_file($_FILES['brand_logo']['tmp_name'], $upload_path)) {
            try {
                $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('brand_logo', ?)");
                $stmt->execute([$new_logo_filename]);
                $current_logo = $new_logo_filename;
                $msg = 'Brand logo updated successfully!';
            } catch (Exception $e) {
                $error = 'Database update failed: ' . $e->getMessage();
            }
        } else {
            $error = 'Failed to move uploaded logo.';
        }
    }
    // Handle Rooms Explore Image Upload
    if (isset($_FILES['rooms_explore_image']) && $_FILES['rooms_explore_image']['error'] === 0) {
        $upload_dir = '../assets/images/';
        $file_ext = pathinfo($_FILES['rooms_explore_image']['name'], PATHINFO_EXTENSION);
        $new_img_filename = 'rooms_promo_' . time() . '.' . $file_ext;
        if (move_uploaded_file($_FILES['rooms_explore_image']['tmp_name'], $upload_dir . $new_img_filename)) {
            try {
                $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('rooms_explore_image', ?)")->execute([$new_img_filename]);
                $r_image = $new_img_filename;
                $msg = 'Rooms section image updated successfully!';
            } catch (Exception $e) {}
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Branding Settings | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .logo-preview-round {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid var(--primary);
            overflow: hidden;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-md);
        }
        .logo-preview-round img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .logo-preview-round i {
            font-size: 3rem;
            color: var(--gray-light);
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <h2 style="font-weight:700; font-size:1.1rem; margin:0; color:var(--dark);">Dynamic Branding</h2>
                <span style="font-size:0.78rem; color:var(--gray); margin-top:0.2rem;">Manage brand name and logo</span>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Brand Name Section -->
                <div class="admin-table-card" style="padding:2rem;">
                    <h4 style="margin-bottom:1.5rem;"><i class="fas fa-font"></i> Brand Name</h4>
                    <form method="POST">
                        <div class="form-group" style="margin-bottom:1.5rem;">
                            <label>Website / Hall Name</label>
                            <input type="text" name="brand_name" data-validate="brand" class="form-control" value="<?php echo htmlspecialchars($current_name); ?>" required>
                            <small style="color:var(--gray); font-size:0.75rem;">This name will appear across the entire website, page titles, and chatbot.</small>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Brand Name</button>
                    </form>
                </div>

                <!-- Brand Logo Section -->
                <div class="admin-table-card" style="padding:2rem;">
                    <h4 style="margin-bottom:1.5rem;"><i class="fas fa-image"></i> Brand Logo</h4>
                    
                    <div style="display:flex; flex-direction:column; align-items:center; margin-bottom:2rem;">
                        <small style="margin-bottom:0.5rem; text-transform:uppercase; font-size:0.7rem; color:var(--gray); font-weight:700;">Current Round Logo Preview</small>
                        <div class="logo-preview-round">
                            <?php if ($current_logo): ?>
                                <img src="../assets/images/<?php echo htmlspecialchars($current_logo); ?>" alt="Brand Logo">
                            <?php else: ?>
                                <i class="fas fa-image"></i>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group" style="margin-bottom:1.5rem;">
                            <label>Upload New Logo</label>
                            <div class="file-upload-wrapper">
                                <input type="file" name="brand_logo" class="file-upload-input" accept="image/*" required onchange="handleFileSelect(this)">
                                <div class="file-upload-design">
                                    <i class="fas fa-image"></i>
                                    <span class="upload-text">Choose Brand Logo</span>
                                    <span class="upload-subtext">Square JPG or PNG</span>
                                </div>
                            </div>
                            <small style="color:var(--gray); font-size:0.75rem; margin-top:0.5rem; display:block;">Square images work best (e.g., 512x512px). It will be automatically cropped to a circle.</small>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Update Logo</button>
                    </form>
                </div>
            </div>

            <!-- Rooms Section Promotion Settings -->
            <div class="admin-table-card" style="padding:2rem; margin-top:2rem;">
                <h4 style="margin-bottom:1.5rem;"><i class="fas fa-bed" style="color:var(--primary);"></i> Homepage Rooms Promotion Setting</h4>
                <form method="POST" enctype="multipart/form-data">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem;">
                        <div>
                            <div class="form-group">
                                <label>Rooms Section Title</label>
                                <input type="text" name="rooms_explore_title" class="form-control" value="<?php echo htmlspecialchars($r_title); ?>" placeholder="e.g., Luxury Guest Rooms">
                                <small style="color:var(--gray); font-size:0.75rem;">The heading for the rooms promotional card on the main browse page.</small>
                            </div>
                            <div style="margin-top:1.5rem;">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Rooms Content</button>
                            </div>
                        </div>

                        <div>
                            <div class="form-group">
                                <label>Rooms Promotional Image (Banner)</label>
                                <div class="file-upload-wrapper">
                                    <input type="file" name="rooms_explore_image" class="file-upload-input" accept="image/*" onchange="handleFileSelect(this)">
                                    <div class="file-upload-design">
                                        <i class="fas fa-image"></i>
                                        <span class="upload-text">Choose Rooms Banner</span>
                                        <span class="upload-subtext">JPG, PNG, or WebP</span>
                                    </div>
                                </div>
                                <?php if ($r_image): ?>
                                    <div style="margin-top:1rem; border-radius:10px; overflow:hidden; border:1px solid var(--border); height:120px;">
                                        <img src="../assets/images/<?php echo htmlspecialchars($r_image); ?>" style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                    <small style="color:var(--success); margin-top:0.5rem; display:block;"><i class="fas fa-image"></i> Current Active Banner</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer & Social Media Settings -->
            <div class="admin-table-card" style="padding:2rem; margin-top:2rem;">
                <h4 style="margin-bottom:1.5rem;"><i class="fas fa-bullhorn" style="color:var(--primary);"></i> Footer & Social Media Settings</h4>
                <form method="POST">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem;">
                        <!-- Contact Info Group -->
                        <div>
                            <div style="font-weight:700; font-size:0.85rem; margin-bottom:1rem; text-transform:uppercase; color:var(--gray); border-bottom:1px solid var(--border); padding-bottom:0.5rem;">
                                <i class="fas fa-address-book"></i> Contact Information
                            </div>
                            <div class="form-group">
                                <label>Footer Phone Number</label>
                                <input type="text" name="footer_phone" class="form-control" value="<?php echo htmlspecialchars($f_phone); ?>" placeholder="+91 98765 43210">
                            </div>
                            <div class="form-group">
                                <label>Footer Email Address</label>
                                <input type="email" name="footer_email" class="form-control" value="<?php echo htmlspecialchars($f_email); ?>" placeholder="info@example.com">
                            </div>
                            <div class="form-group">
                                <label>Footer Street Address</label>
                                <textarea name="footer_address" class="form-control" style="height:80px;" placeholder="123, Main Road, City, State"><?php echo htmlspecialchars($f_address); ?></textarea>
                            </div>
                        </div>

                        <!-- Social Links Group -->
                        <div>
                            <div style="font-weight:700; font-size:0.85rem; margin-bottom:1rem; text-transform:uppercase; color:var(--gray); border-bottom:1px solid var(--border); padding-bottom:0.5rem;">
                                <i class="fas fa-share-nodes"></i> Social Media Links
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                                <div class="form-group">
                                    <label><i class="fab fa-facebook-f" style="color:#1877f2;"></i> Facebook</label>
                                    <input type="text" name="social_facebook" class="form-control" value="<?php echo htmlspecialchars($s_fb); ?>" placeholder="https://facebook.com/yourpage">
                                </div>
                                <div class="form-group">
                                    <label><i class="fab fa-instagram" style="color:#e4405f;"></i> Instagram</label>
                                    <input type="text" name="social_instagram" class="form-control" value="<?php echo htmlspecialchars($s_ig); ?>" placeholder="https://instagram.com/yourprofile">
                                </div>
                                <div class="form-group">
                                    <label><i class="fab fa-youtube" style="color:#ff0000;"></i> YouTube</label>
                                    <input type="text" name="social_youtube" class="form-control" value="<?php echo htmlspecialchars($s_yt); ?>" placeholder="https://youtube.com/@yourchannel">
                                </div>
                                <div class="form-group">
                                    <label><i class="fab fa-whatsapp" style="color:#25d366;"></i> WhatsApp Link</label>
                                    <input type="text" name="social_whatsapp" class="form-control" value="<?php echo htmlspecialchars($s_wa); ?>" placeholder="https://wa.me/91...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:2rem;">
                        <div style="font-weight:700; font-size:0.85rem; margin-bottom:1rem; text-transform:uppercase; color:var(--gray); border-bottom:1px solid var(--border); padding-bottom:0.5rem;">
                            <i class="fas fa-map-location-dot"></i> Google Maps Integration
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem;">
                            <div class="form-group">
                                <label>Maps Iframe / URL</label>
                                <textarea name="google_maps_iframe" class="form-control" style="height:120px; font-family:monospace; font-size:0.8rem;" placeholder="Paste the <iframe src='...'> or direct embed URL here"><?php echo htmlspecialchars($s_map); ?></textarea>
                                <small style="display:block; margin-top:0.5rem; color:var(--gray);">Go to Google Maps → Share → Embed a map → Copy the <b>src="..."</b> URL only.</small>
                            </div>
                            <div>
                                <label>Current Map Preview</label>
                                <div style="height:120px; background:#f1f5f9; border-radius:var(--radius); border:1px solid var(--border); overflow:hidden; position:relative;">
                                    <?php if($s_map): ?>
                                        <iframe src="<?php echo htmlspecialchars($s_map); ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                                    <?php else: ?>
                                        <div style="display:flex; align-items:center; justify-content:center; height:100%; color:var(--gray-light);">No map configured</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:2.5rem; border-top:1px solid var(--border); padding-top:2rem; text-align:right;">
                        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.8rem 2.5rem;"><i class="fas fa-cloud-upload-alt"></i> Save All Footer Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
    <script src="../assets/js/validation.js"></script>
    <script>
        function handleFileSelect(input) {
            const wrapper = input.closest('.file-upload-wrapper');
            const placeholder = wrapper.querySelector('.upload-text');
            const subtext = wrapper.querySelector('.upload-subtext');
            const icon = wrapper.querySelector('i');

            if (input.files && input.files[0]) {
                const fileName = input.files[0].name;
                placeholder.textContent = fileName;
                placeholder.style.color = 'var(--secondary)';
                subtext.textContent = 'File selected successfully';
                icon.className = 'fas fa-check-circle';
                wrapper.classList.add('has-file');
            }
        }
    </script>
</body>
</html>

