<?php
require_once '../includes/auth_functions.php';
require_once '../includes/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

$error = '';
$success = '';

// ðŸ”¹ Fetch admin data
$stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->execute([1]);
$admin = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $error = 'Email is required.';
    } else {

        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE admin SET email = ?, password = ? WHERE id = ?");
            $stmt->execute([$email, $hashedPassword, 1]);
        } else {
            $stmt = $pdo->prepare("UPDATE admin SET email = ? WHERE id = ?");
            $stmt->execute([$email, 1]);
        }

        $_SESSION['success'] = "Credentials updated successfully";
        header("Location: dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Credentials</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    
<div class='admin-layout'>

<?php include '_sidebar.php'; ?>

<div class="admin-main">

    <div class="container" style="max-width:600px;margin:auto;">
        
        <h2 style="margin-bottom:1rem; text-align: center;">Update Admin Credentials</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="card" style="padding:2rem;">
            
            <!-- Email -->
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control"
                    value="<?php echo htmlspecialchars($admin['email']); ?>" required>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" class="form-control"
                    placeholder="password">
                <span style="font-size:13px;">(Leave it Empty , if you don't want to change the password)</span>
            </div>

            <button type="submit" class="btn btn-primary">
                Update Credentials
            </button>

        </form>

    </div>

</div>

</div>

</body>
</html>