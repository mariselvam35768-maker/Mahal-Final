<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

// ===== INITIALIZE VARIABLES =====
$msg = '';
$error = '';
$edit_id = null;
$edit_data = null;
$explore_dir = '../assets/images/explore/';

if (!is_dir($explore_dir)) {
    mkdir($explore_dir, 0777, true);
}

// ===== AUTO-MIGRATION: Ensure explore columns exist =====
if (isset($pdo) && $pdo) {
    try {
        $pdo->exec("ALTER TABLE explore ADD COLUMN IF NOT EXISTS title VARCHAR(255) AFTER id");
        $pdo->exec("ALTER TABLE explore ADD COLUMN IF NOT EXISTS subtitle VARCHAR(255) AFTER title");
    } catch(Exception $e) {}
}

// Handle Image Upload & Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_explore'])) {
    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $description = trim($_POST['description']);
    $update_id = isset($_POST['edit_id']) && !empty($_POST['edit_id']) ? $_POST['edit_id'] : null;

    if (empty($title)) {
        $error = 'Please enter a title for the explore item.';
    } else {
        $image_uploaded = false;
        $new_filename = '';

        if (isset($_FILES['explore_image']) && $_FILES['explore_image']['error'] === 0) {
            $file_ext = pathinfo($_FILES['explore_image']['name'], PATHINFO_EXTENSION);
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array(strtolower($file_ext), $allowed_ext)) {
                $new_filename = 'explore_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                if (move_uploaded_file($_FILES['explore_image']['tmp_name'], $explore_dir . $new_filename)) {
                    $image_uploaded = true;
                }
            }
        }

        try {
            if ($update_id) {
                if ($image_uploaded) {
                    $stmt = $pdo->prepare("SELECT image_path FROM explore WHERE id = ?");
                    $stmt->execute([$update_id]);
                    $old_image = $stmt->fetchColumn();
                    if ($old_image && file_exists($explore_dir . $old_image)) unlink($explore_dir . $old_image);

                    $stmt = $pdo->prepare("UPDATE explore SET image_path = ?, title = ?, subtitle = ?, description = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$new_filename, $title, $subtitle, $description, $update_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE explore SET title = ?, subtitle = ?, description = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$title, $subtitle, $description, $update_id]);
                }
                $msg = 'Explore item updated successfully!';
            } else {
                if ($image_uploaded) {
                    $stmt = $pdo->prepare("INSERT INTO explore (image_path, title, subtitle, description) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$new_filename, $title, $subtitle, $description]);
                    $msg = 'Explore item added successfully!';
                } else {
                    $error = 'Please select an image for a new explore item.';
                }
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle Description Only Update (when no new image uploaded)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_description_only'])) {
    $update_id = $_POST['edit_id'] ?? null;
    $description = trim($_POST['description']);

    if (!$update_id || empty($description)) {
        $error = 'Invalid request or empty description.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE explore SET description = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$description, $update_id]);
            $msg = 'Description updated successfully!';
        } catch (Exception $e) {
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}

// Handle Image Deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("SELECT image_path FROM explore WHERE id = ?");
        $stmt->execute([$id]);
        $image = $stmt->fetchColumn();

        if ($image) {
            $file_path = $explore_dir . $image;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $delete_stmt = $pdo->prepare("DELETE FROM explore WHERE id = ?");
            $delete_stmt->execute([$id]);
            $_SESSION['success'] = 'Explore item deleted successfully!';
            header('Location: manage_explore.php');
            exit();
        }
    } catch (Exception $e) {
        $error = 'Deletion failed: ' . $e->getMessage();
    }
}

// Handle Edit (load data)
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM explore WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_data = $stmt->fetch();
    } catch (Exception $e) {
        $error = 'Failed to load item: ' . $e->getMessage();
    }
}


// Fetch all explore items
try {
    $stmt = $pdo->query("SELECT * FROM explore ORDER BY created_at DESC");
    $explore_items = $stmt->fetchAll();
} catch (Exception $e) {
    $explore_items = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Explore | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .explore-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .explore-item {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: var(--transition);
            position: relative;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .explore-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .explore-item img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        .explore-actions {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            display: flex;
            gap: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .explore-item:hover .explore-actions {
            opacity: 1;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            transition: var(--transition);
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .action-btn.edit {
            color: var(--primary);
        }

        .action-btn.edit:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .action-btn.delete {
            color: #ef4444;
        }

        .action-btn.delete:hover {
            background: #ef4444;
            color: white;
            transform: scale(1.1);
        }

        .explore-info {
            padding: 0.75rem;
        }

        .explore-desc {
            font-size: 0.8rem;
            color: var(--gray);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .explore-date {
            font-size: 0.7rem;
            color: var(--gray-light);
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .edit-mode-badge {
            position: absolute;
            top: 0;
            left: 0;
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.75rem;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .form-section {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 2px solid var(--border);
            transition: var(--transition);
        }

        .form-section.edit-active {
            border-color: var(--primary);
            background: linear-gradient(135deg, #fff5f8 0%, #ffffff 100%);
        }

        .form-title {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--dark);
        }

        .form-title i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .edit-form-title {
            color: var(--primary);
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <h2 style="font-weight:700; font-size:1.1rem; margin:0; color:var(--dark);">Explore Management</h2>
                <span style="font-size:0.78rem; color:var(--gray); margin-top:0.2rem;">Manage explore content and images</span>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-success animate-fade-in"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger animate-fade-in"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Form Section -->
            <div class="form-section <?php echo $edit_data ? 'edit-active' : ''; ?>">
                <h4 class="form-title <?php echo $edit_data ? 'edit-form-title' : ''; ?>">
                    <i class="fas <?php echo $edit_data ? 'fa-edit' : 'fa-plus-circle'; ?>"></i>
                    <?php echo $edit_data ? 'Edit Explore Item' : 'Add New Explore Item'; ?>
                </h4>

                <form method="POST" enctype="multipart/form-data" id="exploreForm">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                    <?php endif; ?>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
                        <div style="display:flex; flex-direction:column; gap:1.25rem;">
                            <div class="form-group">
                                <label>Explore Item Heading <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="e.g., Traditional Stage Decor" value="<?php echo $edit_data ? htmlspecialchars($edit_data['title']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Explore Subheading / Place name</label>
                                <input type="text" name="subtitle" class="form-control" placeholder="e.g., Main Hall - Ground Floor" value="<?php echo $edit_data ? htmlspecialchars($edit_data['subtitle']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Detailed Description</label>
                                <textarea 
                                    name="description" 
                                    class="form-control" 
                                    rows="3" 
                                    placeholder="Enter additional details..."
                                ><?php echo $edit_data ? htmlspecialchars($edit_data['description']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>
                                <?php echo $edit_data ? 'Change Image (Optional)' : 'Select Image'; ?>
                                <span style="color:#ef4444;"><?php echo !$edit_data ? '*' : ''; ?></span>
                            </label>
                            <div class="file-upload-wrapper" id="exploreUpload">
                                <input 
                                    type="file" 
                                    name="explore_image" 
                                    class="file-upload-input" 
                                    accept="image/*" 
                                    <?php echo !$edit_data ? 'required' : ''; ?>
                                    onchange="handleFileSelect(this)"
                                >
                                <div class="file-upload-design" style="padding: 1.5rem;">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span class="upload-text"><?php echo $edit_data ? 'Change Image' : 'Choose Image'; ?></span>
                                    <span class="upload-subtext">JPG, PNG, GIF, WebP (Max 5MB)</span>
                                </div>
                            </div>
                            <?php if ($edit_data): ?>
                                <div style="margin-top:0.75rem; padding:0.75rem; background:#f0f9ff; border-radius:var(--radius-sm); border:1px solid #bfdbfe;">
                                    <div style="font-size:0.8rem; color:var(--primary); font-weight:600; margin-bottom:0.5rem;">
                                        <i class="fas fa-image"></i> Current Image:
                                    </div>
                                    <img 
                                        src="../assets/images/explore/<?php echo htmlspecialchars($edit_data['image_path']); ?>" 
                                        style="width:100%; max-width:150px; border-radius:var(--radius-sm); border:1px solid #e5e7eb;"
                                        alt="Current image"
                                    >
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="text-align:right; display:flex; gap:1rem; justify-content:flex-end;">
                        <?php if ($edit_data): ?>
                            <a href="manage_explore.php" class="btn" style="background:#f3f4f6; color:var(--dark); border:1px solid var(--border);">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php endif; ?>
                        <?php if ($edit_data): ?>
                            <button type="button" class="btn btn-warning" onclick="updateDescriptionOnly()">
                                <i class="fas fa-save"></i> Update Description Only
                            </button>
                        <?php endif; ?>
                        <button type="submit" name="upload_explore" class="btn btn-primary">
                            <i class="fas <?php echo $edit_data ? 'fa-refresh' : 'fa-upload'; ?>"></i>
                            <?php echo $edit_data ? 'Update Item' : 'Add to Explore'; ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Items Display Grid -->
            <div style="margin-top:3rem;">
                <h4 style="font-weight:700; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.75rem;">
                    <i class="fas fa-th-large" style="color:var(--primary);"></i>
                    Explore Items (<?php echo count($explore_items); ?>)
                </h4>

                <div class="explore-grid">
                    <?php if (empty($explore_items)): ?>
                        <div style="grid-column: 1/-1; text-align:center; padding:4rem 2rem; color:var(--gray-light);">
                            <i class="fas fa-image" style="font-size:3rem; margin-bottom:1rem; opacity:0.3;"></i>
                            <p>No explore items yet. Create one to get started!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($explore_items as $item): ?>
                            <div class="explore-item">
                                <?php if ($edit_data && $edit_data['id'] == $item['id']): ?>
                                    <div class="edit-mode-badge">Currently Editing</div>
                                <?php endif; ?>

                                <img 
                                    src="../assets/images/explore/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                    alt="Explore item"
                                >

                                <div class="explore-actions">
                                    <a href="?edit=<?php echo $item['id']; ?>" class="action-btn edit" title="Edit Item">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $item['id']; ?>" class="action-btn delete" title="Delete Item" onclick="return confirm('Are you sure you want to delete this item?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>

                                <div class="explore-info">
                                    <h5 style="margin:0; font-size:0.9rem; font-weight:700; color:var(--dark);"><?php echo htmlspecialchars($item['title'] ?: 'No Title'); ?></h5>
                                    <?php if ($item['subtitle']): ?>
                                        <p style="margin:0; font-size:0.75rem; color:var(--primary); font-weight:600;"><?php echo htmlspecialchars($item['subtitle']); ?></p>
                                    <?php endif; ?>
                                    <p class="explore-desc" style="margin-top:0.35rem;"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <div class="explore-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo date('d M Y', strtotime($item['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/alerts.php'; ?>

<script>
function handleFileSelect(input) {
    const fileName = input.files[0]?.name || 'No file chosen';
    const wrapper = input.closest('.file-upload-wrapper');
    const uploadText = wrapper.querySelector('.upload-text');
    
    if (input.files.length > 0) {
        uploadText.textContent = 'File: ' + fileName;
    }
}

function updateDescriptionOnly() {
    const form = document.getElementById('exploreForm');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'update_description_only';
    input.value = '1';
    form.appendChild(input);
    
    // Remove file input requirement temporarily
    const fileInput = form.querySelector('input[name="explore_image"]');
    if (fileInput) {
        fileInput.removeAttribute('required');
    }
    
    form.submit();
}
</script>
</body>
</html>
