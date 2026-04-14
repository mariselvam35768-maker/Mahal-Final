<?php
require_once '../includes/auth_functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    // For admin registration, we hardcode the role to 'admin'
    if (registerUser($pdo, $name, $email, $phone, $password, 'admin')) {
        header('Location: adminlogin.php?registered=1');
        exit();
    } else {
        $error = 'Email already exists or registration failed.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Registration | Sri Lakshmi Residency & Mahal</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem 0;">
    <div class="card" style="width: 100%; max-width: 450px; text-align: center;">
        <h2 style="margin-bottom: 0.5rem; font-weight: 800; color: var(--primary);">Admin Registration</h2>
        <p style="color: #64748b; margin-bottom: 1.5rem;">Create a new administrator account</p>
        
        <?php if ($error): ?>
            <p style="color: #ef4444; margin-bottom: 1rem;"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <form action="adminregistration.php" method="POST">
            <div style="margin-bottom: 1rem; text-align: left;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Full Name</label>
                <input type="text" name="name" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 10px;">
            </div>
            <div style="margin-bottom: 1rem; text-align: left;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email Address</label>
                <input type="email" name="email" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 10px;">
            </div>
            <div style="margin-bottom: 1rem; text-align: left;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Phone Number</label>
                <input type="text" name="phone" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 10px;">
            </div>
            <div style="margin-bottom: 1.5rem; text-align: left;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Password</label>
                <input type="password" name="password" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 10px;">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; background: var(--primary);">Register Admin</button>
        </form>
        <p style="margin-top: 1.5rem;">Already an admin? <a href="adminlogin.php" style="color: var(--secondary); font-weight: 600; text-decoration: none;">Admin Login</a></p>
    </div>
</body>
</html>


