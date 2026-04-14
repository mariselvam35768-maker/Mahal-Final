<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

$msg = '';
$error = '';
$action = $_GET['action'] ?? '';

// ===== HANDLE ACTIONS =====

// DELETE HALL
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    try {
        // Check no active bookings
        $active = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE hall_id = ? AND status IN ('pending','confirmed')");
        $active->execute([$del_id]);
        if ($active->fetchColumn() > 0) {
            $error = 'Cannot delete this hall - it has active bookings.';
        } else {
            $pdo->prepare("DELETE FROM halls WHERE id = ?")->execute([$del_id]);
            $msg = 'Hall deleted successfully.';
        }
    } catch (Exception $e) { $error = 'Error deleting hall.'; }
}

// ADD or EDIT HALL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_hall'])) {
    $edit_id            = (int)($_POST['hall_id'] ?? 0);
    $name               = trim($_POST['name'] ?? '');
    $location           = trim($_POST['location'] ?? '');
    $capacity           = (int)($_POST['capacity'] ?? 0);
    $price_per_day      = (float)($_POST['price_per_day'] ?? 0);
    $description        = trim($_POST['description'] ?? '');
    $facilities         = trim($_POST['facilities'] ?? '');

    if (empty($name) || empty($location) || $capacity <= 0 || $price_per_day <= 0) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        // Handle image upload
        $main_image = $_POST['existing_image'] ?? '';
        if (!empty($_FILES['main_image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
                $error = 'Only JPG, PNG, and WebP images allowed.';
            } else {
                $img_name = 'hall_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                $upload_dir = '../assets/images/halls/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_dir . $img_name)) {
                    $main_image = $img_name;
                }
            }
        }

        if (empty($error)) {
            try {
                if ($edit_id > 0) {
                    $pdo->prepare("
                        UPDATE halls SET name=?, location=?, capacity=?, price_per_day=?, description=?, facilities=?, main_image=? WHERE id=?
                    ")->execute([$name, $location, $capacity, $price_per_day, $description, $facilities, $main_image, $edit_id]);
                    $msg = 'Hall updated successfully!';
                } else {
                    $pdo->prepare("
                        INSERT INTO halls (name, location, capacity, price_per_day, description, facilities, main_image, created_at) VALUES (?,?,?,?,?,?,?,NOW())
                    ")->execute([$name, $location, $capacity, $price_per_day, $description, $facilities, $main_image]);
                    $msg = 'Hall added successfully!';
                }
                $action = ''; // Go back to list
            } catch (Exception $e) { $error = 'Database error: ' . $e->getMessage(); }
        }
    }
}

// Fetch hall for edit
$edit_hall = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $edit_hall = $pdo->prepare("SELECT * FROM halls WHERE id = ?");
    $edit_hall->execute([(int)$_GET['id']]);
    $edit_hall = $edit_hall->fetch();
}

// Fetch all halls for listing
$halls = [];
try {
    $halls = $pdo->query("
        SELECT h.*, 
               (SELECT COUNT(*) FROM bookings WHERE hall_id = h.id AND status = 'confirmed') AS confirmed_count
        FROM halls h 
        ORDER BY h.created_at DESC
    ")->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Halls | Sri Lakshmi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); }
        .hall-admin-card { display: grid; grid-template-columns: 90px 1fr auto; gap: 1.25rem; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f8fafc; }
        .hall-admin-card:last-child { border-bottom: none; }
        .hall-thumb { width: 90px; height: 64px; border-radius: var(--radius); overflow: hidden; flex-shrink: 0; }
        .hall-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .hall-thumb .placeholder { width: 100%; height: 100%; background: var(--gradient-primary); display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.4); font-size: 1.5rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media(max-width:640px) { .form-grid { grid-template-columns: 1fr; } .hall-admin-card { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <h2 style="font-weight:700; font-size:1.1rem; margin:0; color:var(--dark);">Manage Halls</h2>
                <span style="font-size:0.78rem; color:var(--gray); margin-top:0.2rem;"><?php echo count($halls); ?> halls registered</span>
            </div>
            <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Hall</a>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-success animate-fade-in"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger animate-fade-in"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- ADD/EDIT FORM -->
            <div class="admin-table-card" style="overflow:visible;">
                <div class="admin-table-header">
                    <h4 style="margin:0;"><?php echo $action === 'edit' ? 'âœï¸ Edit Hall' : 'âž• Add New Hall'; ?></h4>
                    <a href="manage_halls.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div style="padding:1.25rem;">
                    <form method="POST" enctype="multipart/form-data">
                        <?php if ($edit_hall): ?>
                            <input type="hidden" name="hall_id" value="<?php echo $edit_hall['id']; ?>">
                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($edit_hall['main_image'] ?? ''); ?>">
                        <?php endif; ?>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Hall Name <span style="color:var(--danger)">*</span></label>
                                <input type="text" name="name" data-validate="name" class="form-control" placeholder="e.g., Sri Murugan Mahal" required value="<?php echo htmlspecialchars($edit_hall['name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Location / City <span style="color:var(--danger)">*</span></label>
                                <input type="text" name="location" class="form-control" placeholder="e.g., Madurai" required value="<?php echo htmlspecialchars($edit_hall['location'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Capacity (guests) <span style="color:var(--danger)">*</span></label>
                                <input type="number" name="capacity" data-validate="number" class="form-control" placeholder="e.g., 500" required min="10" value="<?php echo htmlspecialchars($edit_hall['capacity'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Full Day Rent (Rs.) <span style="color:var(--danger)">*</span></label>
                                <input type="number" name="price_per_day" data-validate="number" class="form-control" placeholder="e.g., 25000" required min="0" step="100" value="<?php echo htmlspecialchars($edit_hall['price_per_day'] ?? ''); ?>">
                            </div>
                        </div>




                        <div class="form-grid">
                            <div class="form-group">
                                <label>Main Hall Image</label>
                                <div class="file-upload-wrapper">
                                    <input type="file" name="main_image" class="file-upload-input" accept="image/*" onchange="handleFileSelect(this)">
                                    <div class="file-upload-design">
                                        <i class="fas fa-building-columns"></i>
                                        <span class="upload-text">Choose Hall Main Photo</span>
                                        <span class="upload-subtext">JPG, PNG, or WebP</span>
                                    </div>
                                </div>
                                <?php if (!empty($edit_hall['main_image'])): ?>
                                    <small style="color:var(--success); margin-top:0.5rem; display:block;"><i class="fas fa-image"></i> Current: <?php echo htmlspecialchars($edit_hall['main_image']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Facilities / Amenities</label>
                            <input type="text" name="facilities" class="form-control" placeholder="e.g., AC, Parking, Stage, Catering, Generator, WiFi" value="<?php echo htmlspecialchars($edit_hall['facilities'] ?? ''); ?>">
                            <small style="color:var(--gray-light);font-size:0.75rem;">Separate amenities with commas</small>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Describe the hall, its atmosphere, and special features..."><?php echo htmlspecialchars($edit_hall['description'] ?? ''); ?></textarea>
                        </div>

                        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                            <button type="submit" name="save_hall" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $action === 'edit' ? 'Update Hall' : 'Add Hall'; ?>
                            </button>
                            <a href="manage_halls.php" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php else: ?>
            <!-- HALL LIST -->
            <div class="admin-table-card">
                <div class="admin-table-header">
                    <div>
                        <h4 style="margin:0;">All Halls</h4>
                        <p style="margin:0;font-size:0.78rem;color:var(--gray);"><?php echo count($halls); ?> venues listed</p>
                    </div>
                </div>

                <?php if (empty($halls)): ?>
                    <div style="text-align:center;padding:4rem;color:var(--gray-light);">
                        <i class="fas fa-building" style="font-size:3rem;margin-bottom:1rem;"></i>
                        <p>No halls added yet. <a href="?action=add" style="color:var(--primary);">Add your first hall -></a></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($halls as $h): ?>
                        <div class="hall-admin-card">
                            <div class="hall-thumb">
                                <?php if ($h['main_image']): ?>
                                    <img src="../assets/images/halls/<?php echo htmlspecialchars($h['main_image']); ?>" alt="">
                                <?php else: ?>
                                    <div class="placeholder"><i class="fas fa-building-columns"></i></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:0.95rem;margin-bottom:0.2rem;"><?php echo htmlspecialchars($h['name']); ?></div>
                                <div style="display:flex;gap:1.25rem;font-size:0.78rem;color:var(--gray);flex-wrap:wrap;">
                                    <span><i class="fas fa-map-marker-alt" style="color:var(--primary);"></i> <?php echo htmlspecialchars($h['location']); ?></span>
                                    <span><i class="fas fa-users" style="color:var(--primary);"></i> <?php echo number_format($h['capacity']); ?> guests</span>
                                    <span style="white-space:nowrap;"><i class="fas fa-rupee-sign" style="color:var(--primary);"></i> Rs. <?php echo number_format($h['price_per_day']); ?>/day</span>
                                    <span><i class="fas fa-calendar-check" style="color:var(--success);"></i> <?php echo $h['confirmed_count']; ?> confirmed</span>
                                </div>
                                <?php if ($h['facilities']): ?>
                                    <div style="display:flex;flex-wrap:wrap;gap:0.3rem;margin-top:0.5rem;">
                                        <?php foreach(array_slice(explode(',', $h['facilities']), 0, 4) as $f): ?>
                                            <span style="background:#f1f5f9;color:var(--gray);font-size:0.7rem;padding:0.15rem 0.5rem;border-radius:20px;"><?php echo trim(htmlspecialchars($f)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex;gap:0.5rem;flex-shrink:0;">
                                <a href="?action=edit&id=<?php echo $h['id']; ?>" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?delete=<?php echo $h['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this hall? This cannot be undone.')"><i class="fas fa-trash"></i></a>
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


