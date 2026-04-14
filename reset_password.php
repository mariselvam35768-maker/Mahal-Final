<?php
require_once 'includes/auth_functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp_verified']) || $_SESSION['reset_otp_verified'] !== true) {
    header('Location: forgot_password.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm)) {
        $error = 'Please fill in both fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || preg_match_all('/[0-9]/', $password) < 2 || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = 'Password must contain at least 1 uppercase letter, 1 lowercase letter, 2 numbers, and 1 special character.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update in users table
        $stmtUsers = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $usersUpdated = $stmtUsers->execute([$hashedPassword, $_SESSION['reset_email']]);

        // Update in admin table
        $stmtAdmin = $pdo->prepare("UPDATE admin SET password = ? WHERE email = ?");
        $adminUpdated = $stmtAdmin->execute([$hashedPassword, $_SESSION['reset_email']]);

        if ($usersUpdated || $adminUpdated) {
            // Clear session variables
            unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_otp_expiry'], $_SESSION['reset_otp_verified']);
            $redirect_login = true;
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | <?php echo $brand_name; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); min-height: 100vh; display: flex; align-items:center; justify-content:center; }
        .auth-form-wrap { width: 100%; max-width: 450px; padding: 2.5rem; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .strength-bar { height: 4px; border-radius: 2px; background: var(--border); margin-top: 0.4rem; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 2px; transition: width 0.3s, background 0.3s; }
        .is-invalid { border-color: #ef4444 !important; }
    </style>
</head>
<body>
    <div class="auth-form-wrap reveal active">
        <a href="index.php" style="display:inline-flex;align-items:center;gap:0.5rem;color:var(--gray);font-size:0.85rem;margin-bottom:2rem;transition:var(--transition);" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--gray)'">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <h1 style="font-size:1.8rem;margin-bottom:0.5rem;">Reset Password</h1>
        <p style="color:var(--gray);margin-bottom:2rem;">Enter your new password below.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" id="resetForm">
            <div class="form-group">
                <label>New Password <span style="color:var(--danger)">*</span></label>
                <div class="input-icon-wrap" style="position:relative;">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="pwd" class="form-control" placeholder="Min. 6 characters" oninput="checkStrength(this.value); validatePassword()" onchange="validatePassword()">
                    <i class="fas fa-eye" id="togglePwd1" onclick="togglePwd('pwd', 'togglePwd1')" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--gray-light);left:auto;"></i>
                </div>
                <div class="strength-bar">
                    <div class="strength-fill" id="strengthFill" style="width:0%;background:transparent;"></div>
                </div>
                <div id="strengthText" style="font-size:0.75rem;color:var(--gray-light);margin-top:0.3rem;"></div>
                <div id="passwordError" style="font-size:0.75rem;margin-top:0.3rem;"></div>
            </div>

            <div class="form-group">
                <label>Confirm Password <span style="color:var(--danger)">*</span></label>
                <div class="input-icon-wrap" style="position:relative;">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" id="cpwd" class="form-control" placeholder="Re-enter password" oninput="validatePassword()" onchange="validatePassword()">
                    <i class="fas fa-eye" id="togglePwd2" onclick="togglePwd('cpwd', 'togglePwd2')" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--gray-light);left:auto;"></i>
                </div>
                <div id="matchText" style="font-size:0.75rem;margin-top:0.3rem;"></div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:0.5rem;">
                <i class="fas fa-save"></i> Save New Password
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function togglePwd(id, iconId) {
            const p = document.getElementById(id);
            const i = document.getElementById(iconId);
            if (p.type === 'password') { p.type = 'text'; i.classList.replace('fa-eye','fa-eye-slash'); }
            else { p.type = 'password'; i.classList.replace('fa-eye-slash','fa-eye'); }
        }

        function checkStrength(val) {
            const bar = document.getElementById('strengthFill');
            const txt = document.getElementById('strengthText');
            let score = 0;
            if (val.length >= 6) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [{ label: '', color: 'transparent', width: '0%' },
                { label: 'Weak', color: '#ef4444', width: '25%' },
                { label: 'Fair', color: '#f59e0b', width: '50%' },
                { label: 'Good', color: '#3b82f6', width: '75%' },
                { label: 'Strong', color: '#10b981', width: '100%' }];
            const level = levels[score];
            bar.style.width = level.width;
            bar.style.background = level.color;
            txt.textContent = score > 0 ? level.label : '';
            txt.style.color = level.color;
        }

        function validatePassword() {
            const pwd = document.getElementById('pwd');
            const cpwd = document.getElementById('cpwd');
            const err = document.getElementById('passwordError');
            const matchText = document.getElementById('matchText');
            let valid = true;

            if (!pwd.value) {
                err.textContent = 'Password is required.';
                err.style.color = '#ef4444';
                pwd.classList.add('is-invalid');
                valid = false;
            } else if (pwd.value.length < 6) {
                err.textContent = 'Password must be at least 6 characters long.';
                err.style.color = '#ef4444';
                pwd.classList.add('is-invalid');
                valid = false;
            } else if (!/[A-Z]/.test(pwd.value) || !/[a-z]/.test(pwd.value) || (pwd.value.match(/[0-9]/g) || []).length < 2 || !/[^A-Za-z0-9]/.test(pwd.value)) {
                err.textContent = 'Must contain 1 uppercase, 1 lowercase, 2 numbers & 1 special char.';
                err.style.color = '#ef4444';
                pwd.classList.add('is-invalid');
                valid = false;
            } else {
                err.textContent = '';
                pwd.classList.remove('is-invalid');
            }

            if (!cpwd.value) {
                matchText.textContent = 'Please confirm your password.';
                matchText.style.color = '#ef4444';
                cpwd.classList.add('is-invalid');
                valid = false;
            } else if (pwd.value === cpwd.value) {
                matchText.textContent = '✓ Passwords match';
                matchText.style.color = '#10b981';
                cpwd.classList.remove('is-invalid');
            } else {
                matchText.textContent = '✕ Passwords do not match';
                matchText.style.color = '#ef4444';
                cpwd.classList.add('is-invalid');
                valid = false;
            }

            return valid;
        }

        document.getElementById('resetForm').addEventListener('submit', function(e) {
            if (!validatePassword()) {
                e.preventDefault();
                return false;
            }
        });
    </script>

    <?php if (isset($redirect_login) && $redirect_login): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Password Updated!',
            text: 'You will be redirected to the login page.',
            timer: 2000,
            showConfirmButton: false,
            timerProgressBar: true,
            backdrop: 'rgba(0,0,0,0.85)'
        }).then(() => {
            window.location.href = 'login.php';
        });
    </script>
    <?php endif; ?>
</body>
</html>
