<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

// Auto-migration for room_categories table
if (isset($pdo)) {
    try {
        // Check if image column exists
        $check = $pdo->query("SHOW COLUMNS FROM room_categories LIKE 'image'");
        if (!$check->fetch()) {
            $pdo->exec("ALTER TABLE room_categories ADD COLUMN image VARCHAR(255) AFTER icon");
        }
    } catch (Exception $e) {
        // Silently fail if there's any issue with migration
    }
}

$msg = '';
$error = '';
$action = $_GET['action'] ?? '';

// ===== HANDLE ACTIONS =====

// DELETE CATEGORY
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    try {
        // Check if any rooms are using this category
        $in_use = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE category_id = ?");
        $in_use->execute([$del_id]);
        if ($in_use->fetchColumn() > 0) {
            $error = 'Cannot delete this category - it is assigned to existing rooms.';
        } else {
            $pdo->prepare("DELETE FROM room_categories WHERE id = ?")->execute([$del_id]);
            $msg = 'Category deleted successfully.';
        }
    } catch (Exception $e) { $error = 'Error deleting category.'; }
}

// ADD or EDIT CATEGORY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $edit_id     = (int)($_POST['category_id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon        = trim($_POST['icon'] ?? 'fas fa-bed');
    $image       = $_POST['existing_image'] ?? '';

    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $error = 'Only JPG, PNG, and WebP images allowed.';
            } else {
                $img_name = 'cat_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $upload_dir = '../assets/images/categories/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $img_name)) {
                    $image = $img_name;
                }
            }
        }
        try {
            if ($edit_id > 0) {
                $pdo->prepare("UPDATE room_categories SET name=?, description=?, icon=?, image=? WHERE id=?")
                    ->execute([$name, $description, $icon, $image, $edit_id]);
                $msg = 'Category updated successfully!';
            } else {
                $pdo->prepare("INSERT INTO room_categories (name, description, icon, image) VALUES (?,?,?,?)")
                    ->execute([$name, $description, $icon, $image]);
                $msg = 'Category added successfully!';
            }
            $action = '';
        } catch (Exception $e) { $error = 'Database error: ' . $e->getMessage(); }
    }
}

// Fetch category for edit
$edit_cat = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $edit_cat = $pdo->prepare("SELECT * FROM room_categories WHERE id = ?");
    $edit_cat->execute([(int)$_GET['id']]);
    $edit_cat = $edit_cat->fetch();
}

// Fetch all categories
$categories = [];
try {
    $categories = $pdo->query("
        SELECT rc.*, 
               (SELECT COUNT(*) FROM rooms WHERE category_id = rc.id) AS room_count
        FROM room_categories rc 
        ORDER BY rc.name ASC
    ")->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories | Sri Lakshmi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); }
        .cat-card { display: flex; align-items: center; gap: 1.5rem; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: white; }
        .cat-card:hover { background: #fffcfd; transform: scale(1.002); box-shadow: inset 4px 0 0 var(--primary); }
        .cat-card:last-child { border-bottom: none; }
        .cat-image-wrap { width: 90px; height: 65px; border-radius: 12px; border: 1px solid var(--border); overflow: hidden; display: flex; align-items: center; justify-content: center; background: var(--bg); flex-shrink: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .cat-image-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .cat-image-wrap i { font-size: 1.5rem; color: var(--primary); opacity: 0.6; }
        .cat-content { flex: 1; min-width: 0; }
        .cat-title { font-size: 1.05rem; font-weight: 800; color: var(--dark); margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.5rem; }
        .cat-desc { font-size: 0.825rem; color: var(--gray); line-height: 1.5; margin-bottom: 0.6rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 90%; }
        .cat-meta { display: flex; align-items: center; gap: 0.75rem; }
        .cat-badge-mini { font-size: 0.68rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.02em; }
        .cat-actions { display: flex; gap: 0.5rem; }
        .action-circle-btn { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; transition: 0.3s; border: none; cursor: pointer; text-decoration: none; }
        .btn-edit-lite { background: var(--primary-light); color: var(--primary); }
        .btn-edit-lite:hover { background: var(--primary); color: white; transform: rotate(15deg); }
        .btn-delete-lite { background: #fee2e2; color: #ef4444; }
        .btn-delete-lite:hover { background: #ef4444; color: white; transform: rotate(-15deg); }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <h2 style="font-weight:700; font-size:1.1rem; margin:0; color:var(--dark);">Room Categories</h2>
                <span style="font-size:0.78rem; color:var(--gray); margin-top:0.2rem;"><?php echo count($categories); ?> categories active</span>
            </div>
            <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Category</a>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-success animate-fade-in"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger animate-fade-in"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="admin-table-card">
                <div class="admin-table-header">
                    <h4 style="margin:0;"><?php echo $action === 'edit' ? '📝 Edit Category' : '➕ Add New Category'; ?></h4>
                    <a href="manage_categories.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div style="padding:1.25rem;">
                    <form method="POST" enctype="multipart/form-data">
                        <?php if ($edit_cat): ?>
                            <input type="hidden" name="category_id" value="<?php echo $edit_cat['id']; ?>">
                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($edit_cat['image'] ?? ''); ?>">
                        <?php endif; ?>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem;">
                            <div class="form-group">
                                <label>Category Name <span style="color:var(--danger)">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g., VIP Suite" required value="<?php echo htmlspecialchars($edit_cat['name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Icon (FontAwesome Class)</label>
                                <div style="display:flex; gap:0.5rem;">
                                    <input type="text" name="icon" id="iconInput" class="form-control" placeholder="fas fa-crown" value="<?php echo htmlspecialchars($edit_cat['icon'] ?? 'fas fa-bed'); ?>">
                                    <div id="iconPreview" class="cat-icon-circle" style="flex-shrink:0;"><i class="<?php echo ($edit_cat['icon'] ?? 'fas fa-bed'); ?>"></i></div>
                                </div>
                                <small style="color:var(--gray-light);">Search icons on FontAwesome website</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Category Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe this category..."><?php echo htmlspecialchars($edit_cat['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label>Category Image</label>
                            <div class="file-upload-wrapper">
                                <input type="file" name="image" class="file-upload-input" accept="image/*" onchange="handleFileSelect(this)">
                                <div class="file-upload-design">
                                    <i class="fas fa-image"></i>
                                    <span class="upload-text">Choose Category Banner Photo</span>
                                    <span class="upload-subtext">JPG, PNG, or WebP</span>
                                </div>
                            </div>
                            <?php if (!empty($edit_cat['image'])): ?>
                                <small style="color:var(--success); margin-top:0.5rem; display:block;"><i class="fas fa-image"></i> Current Image: <?php echo htmlspecialchars($edit_cat['image']); ?></small>
                            <?php endif; ?>
                        </div>

                        <div style="display:flex; gap:1rem;">
                            <button type="submit" name="save_category" class="btn btn-primary"><i class="fas fa-save"></i> Save Category</button>
                            <a href="manage_categories.php" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php else: ?>
            <div class="admin-table-card">
                <div class="admin-table-header">
                    <h4 style="margin:0;">Active Categories</h4>
                </div>
                
                <?php if (empty($categories)): ?>
                    <div style="text-align:center; padding:4rem; color:var(--gray-light);">
                        <p>No categories found. Add one to start organizing your rooms.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($categories as $c): ?>
                        <div class="cat-card">
                            <div class="cat-image-wrap">
                                <?php if (!empty($c['image'])): ?>
                                    <img src="../assets/images/categories/<?php echo htmlspecialchars($c['image']); ?>" alt="">
                                <?php else: ?>
                                    <i class="<?php echo htmlspecialchars($c['icon']); ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="cat-content">
                                <div class="cat-title">
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </div>
                                <div class="cat-desc"><?php echo htmlspecialchars($c['description'] ?: 'No description provided.'); ?></div>
                                <div class="cat-meta">
                                    <span class="cat-badge-mini" style="background:var(--primary-light); color:var(--primary);">
                                        <i class="fas fa-bed"></i> <?php echo $c['room_count']; ?> Rooms Attached
                                    </span>
                                    <span style="font-size: 0.7rem; color: var(--gray-light); font-weight: 600;">
                                        ID: #<?php echo $c['id']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="cat-actions">
                                <a href="?action=edit&id=<?php echo $c['id']; ?>" class="action-circle-btn btn-edit-lite" title="Edit Category">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?php echo $c['id']; ?>" class="action-circle-btn btn-delete-lite" title="Delete Category" onclick="return confirm('Delete this category? This will fail if rooms are assigned to it.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="../assets/js/validation.js"></script>
<script>
    const iconInput = document.getElementById('iconInput');
    const iconPreview = document.getElementById('iconPreview').querySelector('i');
    
    if(iconInput) {
        iconInput.addEventListener('input', (e) => {
            iconPreview.className = e.target.value || 'fas fa-bed';
        });
    }

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
