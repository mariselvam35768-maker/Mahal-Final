<?php
require_once 'includes/auth_functions.php';
require_once 'includes/send_mail.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // If not in users, check admin
        if (!$user) {
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        }

        if ($user) {
            $otp = sprintf("%06d", mt_rand(100000, 999999));
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_otp_expiry'] = time() + 500; // 5 mins
            
            if (sendOTPMail($email, $user['name'], $otp)) {
                $redirect_otp = true;
            } else {
                $error = 'Failed to send OTP email. Please try again later.';
            }
        } else {
            $error = 'No account found with that email address.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | <?php echo $brand_name; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); min-height: 100vh; display: flex; align-items:center; justify-content:center; }
        .auth-form-wrap { width: 100%; max-width: 450px; padding: 2.5rem; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="auth-form-wrap reveal active">
        <a href="login.php" style="display:inline-flex;align-items:center;gap:0.5rem;color:var(--gray);font-size:0.85rem;margin-bottom:2rem;">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>

        <h1 style="font-size:1.8rem;margin-bottom:0.5rem;">Forgot Password</h1>
        <p style="color:var(--gray);margin-bottom:2rem;">Enter your email to receive an OTP.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" id="forgotForm">
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" class="form-control" placeholder="your@email.com" oninput="validateEmail()" onchange="validateEmail()">
                </div>
                <div id="emailError" style="font-size:0.75rem;margin-top:0.35rem;"></div>
            </div>

            <button type="submit" id="submitBtn" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:0.5rem;">
                <i class="fas fa-paper-plane" id="btnIcon"></i> <span id="btnText">Send OTP</span>
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function validateEmail() {
            const el = document.getElementById('email');
            const err = document.getElementById('emailError');
            const val = el.value.trim();
            const regex = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;
            if (!val) {
                err.textContent = 'Email is required.';
                err.style.color = '#ef4444';
                el.style.borderColor = '#ef4444';
                return false;
            }
            if (!regex.test(val)) {
                err.textContent = 'Please enter a valid email address.';
                err.style.color = '#ef4444';
                el.style.borderColor = '#ef4444';
                return false;
            }
            err.textContent = '';
            el.style.borderColor = 'var(--success)';
            return true;
        }

        document.getElementById('forgotForm').addEventListener('submit', function(e) {
            if (!validateEmail()) {
                e.preventDefault();
                return false;
            }

            const btn = document.getElementById('submitBtn');
            const icon = document.getElementById('btnIcon');
            const text = document.getElementById('btnText');
            
            btn.disabled = true;
            btn.style.opacity = '0.7';
            btn.style.cursor = 'not-allowed';
            icon.className = 'fas fa-spinner fa-spin';
            text.textContent = 'Sending OTP... Please wait';
        });
    </script>
    <?php if (isset($redirect_otp) && $redirect_otp): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'OTP Sent!',
            text: 'Please check your email for the OTP.',
            timer: 2000,
            showConfirmButton: false,
            timerProgressBar: true,
            backdrop: 'rgba(0,0,0,0.85)'
        }).then(() => {
            window.location.href = 'verify_otp.php';
        });
    </script>
    <?php endif; ?>
</body>
</html>
