<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

$msg = '';
$error = '';

// Fetch current banner
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'home_banner'");
    $stmt->execute();
    $current_banner = $stmt->fetchColumn() ?: 'hall image1.webp';
} catch (Exception $e) {
    $current_banner = 'hall image1.webp';
}

// Normalize to array for multi-banner support
$current_banners = [];
$decoded = json_decode($current_banner, true);
if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
    $current_banners = $decoded;
} elseif (!empty($current_banner)) {
    $current_banners = [$current_banner];
}

// ===== HANDLE BANNER DELETION =====
if (isset($_GET['delete_banner'])) {
    $to_delete = $_GET['delete_banner'];
    $key = array_search($to_delete, $current_banners);
    
    if ($key !== false) {
        // Remove from array
        unset($current_banners[$key]);
        $current_banners = array_values($current_banners); // re-index
        
        // At least one banner must remain? (Optional, let's allow it but check index.php)
        // If empty, let's keep one default or just save empty array
        $stored_value = count($current_banners) > 0 ? (count($current_banners) > 1 ? json_encode($current_banners) : $current_banners[0]) : 'hall image1.webp';
        
        try {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'home_banner'");
            if ($stmt->execute([$stored_value])) {
                // Delete actual file if it is in banners folder
                if (strpos($to_delete, 'home_banner_') !== false) {
                    $file_path = '../assets/images/banners/' . $to_delete;
                    if (file_exists($file_path)) unlink($file_path);
                }
                $msg = "Banner image deleted successfully!";
                // Refresh $current_banners for display
                $current_banners = count($current_banners) > 0 ? $current_banners : ['hall image1.webp'];
            }
        } catch (Exception $e) { $error = "Delete failed: " . $e->getMessage(); }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['banner_images']) && is_array($_FILES['banner_images']['name'])) {
        $upload_dir = '../assets/images/banners/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $new_banners = [];
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($_FILES['banner_images']['name'] as $index => $name) {
            if (empty($name) || $_FILES['banner_images']['error'][$index] !== UPLOAD_ERR_OK) {
                continue;
            }

            $file_ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($file_ext, $allowed_ext)) {
                continue;
            }

            $new_filename = 'home_banner_' . time() . '_' . $index . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['banner_images']['tmp_name'][$index], $upload_path)) {
                $new_banners[] = $new_filename;
            }
        }

        if (!empty($new_banners)) {
            $all_banners = array_values(array_unique(array_merge($current_banners, $new_banners)));
            $stored_value = count($all_banners) > 1 ? json_encode($all_banners) : $all_banners[0];

            try {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'home_banner'");
                $stmt->execute([$stored_value]);
                $current_banners = $all_banners;
                $current_banner = $current_banners[0];
                $msg = "Banner images updated successfully!";
                $current_banners = $all_banners;
                $current_banner = $current_banners[0];
                // Remove the old alert script to use the new modal system
                // echo "<script>alert('Banner images updated successfully!');
                //         window.location.href = 'banner_settings.php';
                // </script>";
            } catch (Exception $e) {
                $error = 'Database update failed: ' . $e->getMessage();
            }
        } else {
            $error = 'Please select at least one valid image file (jpg, jpeg, png, webp).';
        }
    } else {
        $error = 'Please select at least one image file.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banner Settings | Sri Lakshmi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <h2 style="font-weight:700; font-size:1.1rem; margin:0; color:var(--dark);">Banner Settings</h2>
                <span style="font-size:0.78rem; color:var(--gray); margin-top:0.2rem;">Manage homepage appearance</span>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="admin-table-card" style="padding:2rem;">
                <h4 style="margin-bottom:1.5rem;"><i class="fas fa-image"></i> Homepage Banner Image</h4>
                
                <?php
                    $display_banners = !empty($current_banners) ? $current_banners : ['hall image1.webp'];
                    $main_banner = $display_banners[0];
                    $main_image_path = (strpos($main_banner, 'hall image') !== false) ? '../' . $main_banner : '../assets/images/banners/' . $main_banner;
                ?>
                <div style="margin-bottom:2rem; background: #f8fafc; padding: 1rem; border-radius: var(--radius-lg); border: 1px solid var(--border);">
                    <small style="display:block; color:var(--gray); margin-bottom:0.5rem; text-transform:uppercase; font-size:0.7rem; letter-spacing:0.05em;">Current Banner Preview</small>
                    <div style="width:100%; height:250px; border-radius:var(--radius); overflow:hidden; background:#eee;">
                        <img src="<?php echo htmlspecialchars($main_image_path); ?>" alt="Banner Preview" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <div style="margin-top:1.2rem; display:flex; gap:1.2rem; flex-wrap:wrap;">
                        <?php foreach ($display_banners as $banner_img):
                            $preview = (strpos($banner_img, 'hall image') !== false) ? '../' . $banner_img : '../assets/images/banners/' . $banner_img;
                        ?>
                            <div style="position:relative; group;">
                                <img src="<?php echo htmlspecialchars($preview); ?>" alt="Banner Thumb" style="width:120px; height:68px; object-fit:cover; border:2px solid var(--border); border-radius:8px; transition:0.3s;">
                                <?php if (count($display_banners) > 1 || $banner_img !== 'hall image1.webp'): ?>
                                    <a href="?delete_banner=<?php echo urlencode($banner_img); ?>" 
                                       onclick="return confirm('Sure to delete this banner image?')"
                                       style="position:absolute; top:-8px; right:-8px; background:#ef4444; color:white; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.7rem; border:2px solid white; box-shadow:0 2px 5px rgba(0,0,0,0.2); text-decoration:none; transition:0.3s;"
                                       onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div id="bannerInputs" class="form-group" style="margin-bottom:1rem;">
                        <label>Upload New Banner Images</label>
                        <div class="banner-input-row" style="margin-bottom:0.75rem; display:flex; align-items:center; gap:0.75rem;">
                            <input type="file" name="banner_images[]" accept="image/*" required style="padding:0.5rem; border:1px solid var(--border); border-radius:var(--radius); flex:1;">
                            <button type="button" class="btn btn-outline btn-sm remove-btn" onclick="removeBannerInput(this)" title="Remove this input" style="display:none;"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addBannerInput()" style="margin-bottom:1rem;"><i class="fas fa-plus"></i> Add another input</button>
                    <button type="submit" class="btn btn-primary" style="display:block;"><i class="fas fa-upload"></i> Upload & Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
    <script>
        function addBannerInput() {
            const container = document.getElementById('bannerInputs');
            const newRow = document.createElement('div');
            newRow.className = 'banner-input-row';
            newRow.style.marginBottom = '0.75rem';
            newRow.style.display = 'flex';
            newRow.style.alignItems = 'center';
            newRow.style.gap = '0.75rem';

            newRow.innerHTML = `
                <input type="file" name="banner_images[]" accept="image/*" required style="padding:0.5rem; border:1px solid var(--border); border-radius:var(--radius); flex:1;">
                <button type="button" class="btn btn-outline btn-sm" onclick="removeBannerInput(this)" title="Remove this input"><i class="fas fa-trash-alt"></i></button>
            `;

            container.appendChild(newRow);
            refreshRemoveButtons();
        }

        function removeBannerInput(button) {
            const row = button.closest('.banner-input-row');
            if (!row) return;

            const allRows = document.querySelectorAll('#bannerInputs .banner-input-row');
            if (allRows.length <= 1) {
                // Keep at least one row
                return;
            }

            row.remove();
            refreshRemoveButtons();
        }

        function refreshRemoveButtons() {
            const rows = document.querySelectorAll('#bannerInputs .banner-input-row');
            rows.forEach((row, index) => {
                const btn = row.querySelector('button.btn-outline');
                if (btn) {
                    btn.style.display = (rows.length === 1 && index === 0) ? 'none' : 'inline-flex';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            refreshRemoveButtons();
        });
    </script>
</body>
</html>
