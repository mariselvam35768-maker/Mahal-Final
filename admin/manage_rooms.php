<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

// ===== AUTO-MIGRATION: Entire Schema Integrity =====
if (isset($pdo) && $pdo !== null) {
    // ===== QUICK-FIX MIGRATION: Ensure all rooms columns exist =====
    if (isset($pdo) && $pdo) {
        try {
            $pdo->exec("ALTER TABLE rooms DROP COLUMN IF EXISTS morning_slot_price");
        } catch (Exception $e) {
        }
        try {
            $pdo->exec("ALTER TABLE rooms DROP COLUMN IF EXISTS evening_slot_price");
        } catch (Exception $e) {
        }
        try {
            $pdo->exec("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS total_rooms INT DEFAULT 1 AFTER capacity");
        } catch (Exception $e) {
        }
        try {
            $pdo->exec("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS category_id INT AFTER id");
        } catch (Exception $e) {
        }

        // Add some sample rooms if the table is empty
        try {
            $cnt = (int) $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
            if ($cnt == 0) {
                $cats_stmt = $pdo->query("SELECT id FROM room_categories LIMIT 2");
                $cat_ids = $cats_stmt->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($cat_ids)) {
                    $c1 = $cat_ids[0];
                    $c2 = $cat_ids[1] ?? $cat_ids[0];
                    $pdo->exec("
                    INSERT INTO rooms (category_id, name, location, capacity, total_rooms, price_per_day, description, facilities, created_at) 
                    VALUES 
                    ($c1, 'Luxury VIP Suite 101', 'First Floor', 2, 5, 2500, 'Premium room.', 'AC,TV,WiFi', NOW()),
                    ($c2, 'Deluxe Guest Room 201', 'Second Floor', 2, 10, 1500, 'Comfortable room.', 'WiFi,Bath', NOW())
                ");
                }
            }
        } catch (Exception $e) {
        }
    }
    // =====================================================
}
// =====================================================

$msg = '';
$error = '';
$action = $_GET['action'] ?? '';

// ===== HANDLE ACTIONS =====

// DELETE ROOM
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    try {
        // Check no active bookings
        $active = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status IN ('pending','confirmed')");
        $active->execute([$del_id]);
        if ($active->fetchColumn() > 0) {
            $error = 'Cannot delete this room - it has active bookings.';
        } else {
            $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$del_id]);
            $msg = 'Room deleted successfully.';
        }
    } catch (Exception $e) {
        $error = 'Error deleting room.';
    }
}

// ADD or EDIT ROOM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_room'])) {
    $edit_id = (int) ($_POST['room_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $capacity = (int) ($_POST['capacity'] ?? 0);
    $price_per_day = (float) ($_POST['price_per_day'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $facilities = trim($_POST['facilities'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $total_rooms = (int) ($_POST['total_rooms'] ?? 1);

    if (empty($name) || empty($location) || $capacity <= 0 || $price_per_day <= 0) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        // Handle image upload
        $main_image = $_POST['existing_image'] ?? '';
        if (!empty($_FILES['main_image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $error = 'Only JPG, PNG, and WebP images allowed.';
            } else {
                $img_name = 'room_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $upload_dir = '../assets/images/rooms/';
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0755, true);
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_dir . $img_name)) {
                    $main_image = $img_name;
                }
            }
        }

        if (empty($error)) {
            try {
                if ($edit_id > 0) {
                    $pdo->prepare("
                        UPDATE rooms SET category_id=?, name=?, location=?, capacity=?, total_rooms=?, price_per_day=?, description=?, facilities=?, main_image=? WHERE id=?
                    ")->execute([$category_id, $name, $location, $capacity, $total_rooms, $price_per_day, $description, $facilities, $main_image, $edit_id]);
                    $msg = 'Room updated successfully!';
                } else {
                    $pdo->prepare("
                        INSERT INTO rooms (category_id, name, location, capacity, total_rooms, price_per_day, description, facilities, main_image, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())
                    ")->execute([$category_id, $name, $location, $capacity, $total_rooms, $price_per_day, $description, $facilities, $main_image]);
                    $msg = 'Room added successfully!';
                }
                $action = ''; // Go back to list
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch room for edit
$edit_room = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $edit_room = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $edit_room->execute([(int) $_GET['id']]);
    $edit_room = $edit_room->fetch();
}

// Fetch all rooms for listing
$search_query = trim($_GET['search'] ?? '');
$filter_cat = (int) ($_GET['category'] ?? 0);
$rooms = [];
try {
    $sql = "SELECT r.*, rc.name as category_name, rc.icon as category_icon,
            (SELECT COUNT(*) FROM bookings b WHERE b.room_id = r.id AND b.status IN ('confirmed','pending') AND b.event_date >= CURDATE()) as booked_now
            FROM rooms r 
            LEFT JOIN room_categories rc ON r.category_id = rc.id 
            WHERE 1=1";
    $params = [];
    if ($filter_cat > 0) {
        $sql .= " AND r.category_id = ?";
        $params[] = $filter_cat;
    }
    if ($search_query !== '') {
        $sql .= " AND (r.name LIKE ? OR r.location LIKE ? OR r.facilities LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }
    $sql .= " ORDER BY r.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Query failed: " . $e->getMessage();
}

// Fetch categories with room counts for the tabs
$category_stats = [];
try {
    $category_stats = $pdo->query("
        SELECT rc.*, 
        (SELECT COUNT(*) FROM rooms r WHERE r.category_id = rc.id) as type_count,
        (SELECT SUM(total_rooms) FROM rooms r WHERE r.category_id = rc.id) as total_inv
        FROM room_categories rc ORDER BY rc.name ASC
    ")->fetchAll();
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms | Sri Lakshmi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); }
        .room-admin-card { display: grid; grid-template-columns: 100px 1fr auto; gap: 1.5rem; align-items: center; padding: 1.5rem; border-bottom: 1px solid var(--border); transition: 0.3s; background: white; }
        .room-admin-card:hover { background: #fffcfd; }
        .room-admin-card:last-child { border-bottom: none; }
        .room-thumb { width: 100px; height: 75px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .inv-badge { padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem; }
        .inv-total { background: #f1f5f9; color: #475569; }
        .inv-booked { background: #fff1f2; color: #e11d48; }
        .inv-avail { background: #f0fdf4; color: #16a34a; }
        .stat-pill { padding: 0.4rem 0.9rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 700; border: 1.5px solid var(--border); color: var(--gray); text-decoration: none; transition: 0.2s; white-space: nowrap; }
        .stat-pill:hover { border-color: var(--primary); color: var(--primary); transform: translateY(-2px); }
        .stat-pill.active { background: var(--primary); border-color: var(--primary); color: white; box-shadow: 0 4px 12px rgba(233, 30, 99, 0.25); }
        .search-container { position: relative; width: 100%; max-width: 350px; }
        .search-container i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--gray-light); }
        .search-container input { padding-left: 2.5rem; border-radius: var(--radius-full); }
    </style>
</head>

<body>
    <div class="admin-layout">
        <?php include '_sidebar.php'; ?>
        <div class="admin-main">
            <div class="admin-content" style="padding:0;">
                <?php if ($action === 'add' || $action === 'edit'): ?>
                    <!-- Full Width Header for Add/Edit -->
                    <div class="admin-topbar">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <h2 style="font-weight:700; font-size:1.1rem; margin:0; color:var(--dark);">Room Configuration</h2>
                        </div>
                        <a href="manage_rooms.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Inventory</a>
                    </div>
                <?php else: ?>
                    <!-- Inventory List Topbar -->
                    <div class="admin-topbar">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <h2 style="font-weight:700; font-size:1.1rem; margin:0; color:var(--dark);">Room Inventory</h2>
                            <span style="font-size:0.78rem; color:var(--gray); margin-top:0.2rem;"><?php echo count($rooms); ?> Variants Found</span>
                        </div>
                        
                        <!-- Search Component -->
                        <div class="search-container">
                            <form method="GET" style="margin:0;">
                                <?php if($filter_cat > 0): ?><input type="hidden" name="category" value="<?php echo $filter_cat; ?>"><?php endif; ?>
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" class="form-control" placeholder="Search Room Name..." value="<?php echo htmlspecialchars($search_query); ?>">
                            </form>
                        </div>

                        <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Room Type</a>
                    </div>
                <?php endif; ?>

                <div style="padding:1.5rem;">
                <?php if ($msg): ?>
                    <div class="alert alert-success animate-fade-in"><i class="fas fa-check-circle"></i> <?php echo $msg; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger animate-fade-in"><i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($action === 'add' || $action === 'edit'): ?>
                    <!-- ADD/EDIT FORM -->
                    <div class="admin-table-card" style="overflow:hidden; border:none; background:white;">
                        <div style="display:flex; flex-direction:column; gap:0;">
                            <!-- Branding Banner (Full Width) -->
                            <div style="background:linear-gradient(to right, #fffcfd, #fff0f5); border-bottom:1px solid var(--border); padding:2rem; display:flex; gap:2rem; align-items:center;">
                                <div style="width:250px; border-radius:12px; overflow:hidden; box-shadow:0 8px 25px rgba(233, 30, 99, 0.1); height:140px; border:1px solid var(--border); flex-shrink:0;">
                                    <?php if (!empty($rooms_explore_image)): ?>
                                        <img src="../assets/images/<?php echo htmlspecialchars($rooms_explore_image); ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <div style="background:var(--gradient-primary); width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.2); font-size:3rem;">
                                            <i class="fas fa-bed"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3 style="font-size:1.4rem; color:var(--primary); font-weight:800; margin-bottom:0.5rem;"><?php echo htmlspecialchars($rooms_explore_title ?? 'Luxury Guest Rooms'); ?></h3>
                                    <p style="font-size:0.85rem; color:var(--gray); line-height:1.6; max-width:600px;">Managing your dynamic inventory for the front-end display. All changes saved here will reflect instantly on the user booking page.</p>
                                </div>
                            </div>

                            <div style="padding:2.5rem; max-width:1000px; margin:0 auto; width:100%;">
                                <form method="POST" enctype="multipart/form-data">
                                <?php if ($edit_room): ?>
                                    <input type="hidden" name="room_id" value="<?php echo $edit_room['id']; ?>">
                                    <input type="hidden" name="existing_image"
                                        value="<?php echo htmlspecialchars($edit_room['main_image'] ?? ''); ?>">
                                <?php endif; ?>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Room Category <span style="color:var(--danger)">*</span></label>
                                        <select name="category_id" class="form-control" required>
                                            <option value="">- Select Category -</option>
                                            <?php foreach ($category_stats as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo (isset($edit_room['category_id']) && $edit_room['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Room Name / No. <span style="color:var(--danger)">*</span></label>
                                        <input type="text" name="name" data-validate="name" class="form-control"
                                            placeholder="e.g., Deluxe Room 101" required
                                            value="<?php echo htmlspecialchars($edit_room['name'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Location / Floor <span style="color:var(--danger)">*</span></label>
                                        <input type="text" name="location" class="form-control"
                                            placeholder="e.g., 1st Floor" required
                                            value="<?php echo htmlspecialchars($edit_room['location'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Day Rent (Rs.) <span style="color:var(--danger)">*</span></label>
                                        <input type="number" name="price_per_day" data-validate="number"
                                            class="form-control" placeholder="e.g., 1500" required min="0" step="10"
                                            value="<?php echo htmlspecialchars($edit_room['price_per_day'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Capacity (guests) <span style="color:var(--danger)">*</span></label>
                                        <input type="number" name="capacity" data-validate="number" class="form-control"
                                            placeholder="e.g., 2" required min="1"
                                            value="<?php echo htmlspecialchars($edit_room['capacity'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>No. of Rooms Available <span style="color:var(--danger)">*</span></label>
                                        <input type="number" name="total_rooms" class="form-control" placeholder="e.g., 10"
                                            required min="1"
                                            value="<?php echo htmlspecialchars($edit_room['total_rooms'] ?? '1'); ?>">
                                        <small style="color:var(--gray-light);">How many identical rooms of this type do you
                                            have?</small>
                                    </div>
                                </div>



                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Main Room Image</label>
                                        <div class="file-upload-wrapper">
                                            <input type="file" name="main_image" class="file-upload-input" accept="image/*"
                                                onchange="handleFileSelect(this)">
                                            <div class="file-upload-design">
                                                <i class="fas fa-bed"></i>
                                                <span class="upload-text">Choose Room Main Photo</span>
                                                <span class="upload-subtext">JPG, PNG, or WebP</span>
                                            </div>
                                        </div>
                                        <?php if (!empty($edit_room['main_image'])): ?>
                                            <small style="color:var(--success); margin-top:0.5rem; display:block;"><i
                                                    class="fas fa-image"></i> Current:
                                                <?php echo htmlspecialchars($edit_room['main_image']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Services / Amenities</label>
                                    <input type="text" name="facilities" class="form-control"
                                        placeholder="e.g., AC, WiFi, Attached Bath, TV, Room Service"
                                        value="<?php echo htmlspecialchars($edit_room['facilities'] ?? ''); ?>">
                                    <small style="color:var(--gray-light);font-size:0.75rem;">Separate amenities with
                                        commas</small>
                                </div>

                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="4"
                                        placeholder="Describe the room, its features, and comfort level..."><?php echo htmlspecialchars($edit_room['description'] ?? ''); ?></textarea>
                                </div>

                                <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                                    <button type="submit" name="save_room" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        <?php echo $action === 'edit' ? 'Update Room' : 'Add Room'; ?>
                                    </button>
                                    <a href="manage_rooms.php" class="btn btn-outline">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <?php else: ?>
               <!-- ROOM LIST -->
            <div class="admin-table-card">
                <div class="admin-table-header" style="flex-direction:column; align-items:flex-start; gap:1.25rem; border-bottom:1px solid var(--border);">
                    <div>
                        <h4 style="margin:0;">Inventory Overview</h4>
                        <p style="margin:0;font-size:0.75rem;color:var(--gray);">Track availability across all room categories</p>
                    </div>
                    
                    <!-- Category Filter Tabs with Counts -->
                    <div style="display:flex; gap:0.75rem; flex-wrap:wrap; width:100%;">
                        <a href="manage_rooms.php" class="stat-pill <?php echo $filter_cat == 0 ? 'active' : ''; ?>">
                            All Types
                        </a>
                        <?php foreach ($category_stats as $cat): ?>
                            <a href="manage_rooms.php?category=<?php echo $cat['id']; ?>" class="stat-pill <?php echo $filter_cat == $cat['id'] ? 'active' : ''; ?>">
                                <i class="<?php echo $cat['icon']; ?>" style="margin-right:0.3rem;"></i> 
                                <?php echo htmlspecialchars($cat['name']); ?> 
                                <span style="opacity:0.6; margin-left:0.4rem; font-size:0.65rem; padding-left:0.4rem; border-left:1px solid rgba(255,255,255,0.3);">
                                    <?php echo $cat['total_inv'] ?: 0; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (empty($rooms)): ?>
                    <div style="text-align:center;padding:5rem;color:var(--gray-light);">
                        <i class="fas fa-search" style="font-size:3.5rem;margin-bottom:1rem; opacity:0.1;"></i>
                        <p>No rooms found matching your criteria.</p>
                        <a href="manage_rooms.php" style="color:var(--primary); font-weight:700;">Clear All Filters</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($rooms as $r): ?>
                        <div class="room-admin-card">
                            <div class="room-thumb">
                                <?php if ($r['main_image']): ?>
                                    <img src="../assets/images/rooms/<?php echo htmlspecialchars($r['main_image']); ?>" style="width:100%;height:100%;object-fit:cover;">
                                <?php else: ?>
                                    <div class="placeholder" style="background:var(--primary-light); color:var(--primary); height:100%; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                                        <i class="fas fa-bed"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.4rem;">
                                    <span style="font-weight:800; font-size:1.05rem; color:var(--dark);"><?php echo htmlspecialchars($r['name']); ?></span>
                                    <span class="badge" style="background:#f1f5f9; color:#475569; font-size:0.65rem; border-radius:4px;"><?php echo htmlspecialchars($r['location']); ?></span>
                                </div>
                                
                                <div style="display:flex; gap:0.75rem; align-items:center; margin-bottom:0.75rem;">
                                    <div class="inv-badge inv-total" title="Total Inventory">
                                        <i class="fas fa-boxes"></i> <?php echo $r['total_rooms']; ?> Total
                                    </div>
                                    <div class="inv-badge inv-booked" title="Currently Booked">
                                        <i class="fas fa-calendar-check"></i> <?php echo $r['booked_now']; ?> Booked
                                    </div>
                                    <div class="inv-badge inv-avail" title="Actually Available">
                                        <i class="fas fa-check-circle"></i> <?php echo max(0, $r['total_rooms'] - $r['booked_now']); ?> Available
                                    </div>
                                </div>

                                <div style="display:flex; gap:1.25rem; font-size:0.75rem; color:var(--gray); font-weight:600;">
                                    <span><i class="fas fa-users" style="color:var(--primary);"></i> up to <?php echo $r['capacity']; ?> Guests</span>
                                    <span><i class="fas fa-rupee-sign" style="color:var(--primary);"></i> ₹<?php echo number_format($r['price_per_day']); ?>/day</span>
                                    <span style="color:var(--primary);"><i class="<?php echo $r['category_icon']; ?>"></i> <?php echo strtoupper($r['category_name']); ?></span>
                                </div>
                            </div>
                            <div style="display:flex; gap:0.5rem; flex-shrink:0;">
                                <a href="?action=edit&id=<?php echo $r['id']; ?>" class="action-circle-btn btn-edit-lite" title="Edit Room">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?php echo $r['id']; ?>" class="action-circle-btn btn-delete-lite" title="Delete" onclick="return confirm('Delete this room?')">
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