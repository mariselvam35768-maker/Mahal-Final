<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

$msg = '';
$error = '';

// Toggle user role
if (isset($_GET['toggle_role'])) {
    $uid = (int)$_GET['toggle_role'];
    if ($uid !== (int)$_SESSION['user_id']) { // Don't toggle own role
        try {
            $curr = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $curr->execute([$uid]);
            $role_now = $curr->fetchColumn();
            $new_role = ($role_now === 'admin') ? 'user' : 'admin';
            $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$new_role, $uid]);
            $msg = "User role changed to " . ucfirst($new_role) . ".";
        } catch (Exception $e) { $error = 'Failed to update role.'; }
    } else {
        $error = 'You cannot change your own role.';
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid === (int)$_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        try {
            $has_bookings = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status IN ('pending','confirmed')");
            $has_bookings->execute([$uid]);
            if ($has_bookings->fetchColumn() > 0) {
                $error = 'Cannot delete user   they have active bookings.';
            } else {
                $pdo->prepare("DELETE FROM bookings WHERE user_id = ?")->execute([$uid]);
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
                $msg = 'User deleted successfully.';
            }
        } catch (Exception $e) { $error = 'Error deleting user: ' . $e->getMessage(); }
    }
}

// Search
$search = trim($_GET['search'] ?? '');
$filter_role = $_GET['role'] ?? '';

$query = "SELECT u.*, (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) AS booking_count
          FROM users u WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($filter_role) {
    $query .= " AND u.role = ?";
    $params[] = $filter_role;
}

$query .= " ORDER BY u.id DESC";

$users = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (Exception $e) {}

$total_users = count($users);
$admin_count = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$user_count  = $total_users - $admin_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Sri Lakshmi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); }
        .avatar { width: 38px; height: 38px; border-radius: 50%; background: var(--gradient-primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9rem; flex-shrink: 0; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <h2 style="font-weight:700; font-size:1.1rem; margin:0; color:var(--dark);">Manage Users</h2>
                <span style="font-size:0.78rem; color:var(--gray); margin-top:0.2rem;"><?php echo $total_users; ?> users registered</span>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background:#fce7f3;color:#e91e63;width:44px;height:44px;font-size:1.1rem;"><i class="fas fa-users"></i></div>
                    <div>
                        <div style="font-size:0.7rem;color:var(--gray);text-transform:uppercase;">Total Users</div>
                        <div style="font-size:1.5rem;font-weight:800;font-family:'Poppins',sans-serif;"><?php echo $total_users; ?></div>
                    </div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background:#d1fae5;color:#10b981;width:44px;height:44px;font-size:1.1rem;"><i class="fas fa-user"></i></div>
                    <div>
                        <div style="font-size:0.7rem;color:var(--gray);text-transform:uppercase;">Regular Users</div>
                        <div style="font-size:1.5rem;font-weight:800;font-family:'Poppins',sans-serif;"><?php echo $user_count; ?></div>
                    </div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background:#fef3c7;color:#f59e0b;width:44px;height:44px;font-size:1.1rem;"><i class="fas fa-user-shield"></i></div>
                    <div>
                        <div style="font-size:0.7rem;color:var(--gray);text-transform:uppercase;">Admins</div>
                        <div style="font-size:1.5rem;font-weight:800;font-family:'Poppins',sans-serif;"><?php echo $admin_count; ?></div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="search-section" style="margin-bottom:1.5rem;padding:1.25rem;">
                <form method="GET">
                    <div style="display:grid;grid-template-columns:2fr 1fr auto;gap:1rem;">
                        <div class="input-icon-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <select name="role" class="form-control">
                            <option value="">All Roles</option>
                            <option value="user" <?php echo $filter_role==='user' ? 'selected' : ''; ?>>Users Only</option>
                            <option value="admin" <?php echo $filter_role==='admin' ? 'selected' : ''; ?>>Admins Only</option>
                        </select>
                        <div style="display:flex;gap:0.5rem;">
                            <button type="submit" class="btn btn-primary" style="height:46px;padding:0 1rem;"><i class="fas fa-filter"></i></button>
                            <a href="manage_users.php" class="btn btn-outline" style="height:46px;padding:0 1rem;"><i class="fas fa-times"></i></a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="admin-table-card">
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Bookings</th>

                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr><td colspan="8" style="text-align:center;color:var(--gray-light);padding:4rem;">No users found.</td></tr>
                            <?php else: foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:0.75rem;">
                                            <div class="avatar"><?php echo strtoupper(substr($u['name'], 0, 1)); ?></div>
                                            <div>
                                                <div style="font-weight:700;font-size:0.875rem;"><?php echo htmlspecialchars($u['name']); ?></div>
                                                <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                                    <div style="font-size:0.68rem;color:var(--primary);font-weight:600;">You</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-size:0.825rem;color:var(--gray);"><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td style="font-size:0.825rem;"><?php echo htmlspecialchars($u['phone']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $u['role'] === 'admin' ? 'warning' : 'info'; ?>">
                                            <i class="fas fa-<?php echo $u['role'] === 'admin' ? 'shield-alt' : 'user'; ?>"></i>
                                            <?php echo ucfirst($u['role']); ?>
                                        </span>
                                    </td>
                                    <td style="font-weight:700;text-align:center;"><?php echo $u['booking_count']; ?></td>

                                    <td style="font-size:0.78rem;color:var(--gray);">
                                        <?php echo isset($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : ' '; ?>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:0.35rem;flex-wrap:wrap;">
                                            <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                                <a href="?toggle_role=<?php echo $u['id']; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $filter_role; ?>"
                                                   class="btn btn-sm" style="background:var(--primary-light);color:var(--primary);"
                                                   title="<?php echo $u['role'] === 'admin' ? 'Remove Admin' : 'Make Admin'; ?>"
                                                   onclick="return confirm('Change this user\'s role?')">
                                                    <i class="fas fa-<?php echo $u['role'] === 'admin' ? 'user-minus' : 'user-shield'; ?>"></i>
                                                </a>
                                                <a href="?delete=<?php echo $u['id']; ?>"
                                                   class="btn btn-danger btn-sm"
                                                   title="Delete User"
                                                   onclick="return confirm('Delete this user? This cannot be undone.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span style="font-size:0.72rem;color:var(--gray-light);">Current admin</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>


