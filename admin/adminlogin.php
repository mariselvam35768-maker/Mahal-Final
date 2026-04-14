<?php
require_once '../includes/auth_functions.php';

if (isLoggedIn() && isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // die($email . ' - ' . $password);

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (loginUser($pdo, $email, $password)) {
        if (isAdmin()) {
            $_SESSION['success'] = "Login successfully";
            header('Location: dashboard.php');
        } else {
            logoutUser();
            $error = 'You do not have admin privileges.';
        }
        exit();
    } else {
        $error = 'Invalid admin credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Sri Lakshmi Residency & Mahal</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--gradient-hero); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .admin-login-card { background: white; border-radius: var(--radius-xl); padding: 3rem; width: 100%; max-width: 420px; box-shadow: var(--shadow-xl); }
        .admin-badge { display: inline-flex; align-items: center; gap: 0.5rem; background: #fef3c7; color: #92400e; padding: 0.35rem 1rem; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="admin-login-card animate-fade-in">
        <div style="text-align:center;margin-bottom:2rem;">
            <div style="width:64px;height:64px;background:var(--gradient-primary);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:white;font-size:1.6rem;">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="admin-badge"><i class="fas fa-lock"></i> Admin Access Only</div>
            <h2 style="font-size:1.6rem;margin-bottom:0.25rem;">Admin Dashboard</h2>
            <p style="color:var(--gray);font-size:0.875rem;">Enter your admin credentials to continue.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Admin Email</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control" placeholder="Admin Email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-icon-wrap" style="position:relative;">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="apwd" class="form-control" placeholder="Admin password" required>
                    <i class="fas fa-eye" id="toggleApwd" onclick="togglePwd()" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--gray-light);left:auto;"></i>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:0.5rem;">
                <i class="fas fa-sign-in-alt"></i> Login as Admin
            </button>
        </form>

        <div style="margin-top:1.5rem;text-align:center;">
            <a href="../index.php" style="color:var(--gray);font-size:0.85rem;"><i class="fas fa-arrow-left"></i> Back to Main Site</a>
        </div>
    </div>

    <script src="../assets/js/validation.js"></script>
    <script>
        function togglePwd() {
            const p = document.getElementById('apwd');
            const i = document.getElementById('toggleApwd');
            p.type = p.type === 'password' ? 'text' : 'password';
            i.classList.toggle('fa-eye');
            i.classList.toggle('fa-eye-slash');
        }
    </script>
    <?php include '../includes/alerts.php'; ?>
</body>
</html>


