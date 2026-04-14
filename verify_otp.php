<?php
require_once 'includes/auth_functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
    header('Location: forgot_password.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp'] ?? '');

    if (empty($entered_otp)) {
        $error = 'Please enter the OTP sent to your email.';
    } elseif (time() > $_SESSION['reset_otp_expiry']) {
        $error = 'OTP has expired. Please request a new one.';
    } elseif ($entered_otp !== $_SESSION['reset_otp']) {
        $error = 'Invalid OTP. Please try again.';
    } else {
        $_SESSION['reset_otp_verified'] = true;
        // Don't expire the session yet until reset is over
        $redirect_reset = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | <?php echo $brand_name; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); min-height: 100vh; display: flex; align-items:center; justify-content:center; }
        .auth-form-wrap { width: 100%; max-width: 450px; padding: 2.5rem; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="auth-form-wrap reveal active">
        <h1 style="font-size:1.8rem;margin-bottom:0.5rem;">Verify OTP</h1>
        <p style="color:var(--gray);margin-bottom:2rem;">Enter the 6-digit OTP sent to <br><strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Enter OTP</label>
                <div class="input-icon-wrap" style="position:relative;">
                    <i class="fas fa-key"></i>
                    <input type="text" name="otp" class="form-control" placeholder="123456" maxlength="6" required style="letter-spacing: 5px; font-weight: bold; text-align: center;">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:0.5rem;">
                <i class="fas fa-check-circle"></i> Verify
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (isset($redirect_reset) && $redirect_reset): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'OTP Verified!',
            text: 'You can now reset your password.',
            timer: 1500,
            showConfirmButton: false,
            timerProgressBar: true,
            backdrop: 'rgba(0,0,0,0.85)'
        }).then(() => {
            window.location.href = 'reset_password.php';
        });
    </script>
    <?php endif; ?>
</body>
</html>
