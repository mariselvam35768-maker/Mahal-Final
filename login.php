<?php
require_once 'includes/auth_functions.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'index.php'));
    exit();
}

$error = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = 'Account created successfully! Please login.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_id) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (loginUser($pdo, $login_id, $password)) {
        $login_success = true;
        $redirect_url = isAdmin() ? 'admin/dashboard.php' : 'index.php';
    } else {
        $error = 'Invalid email/phone or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo $brand_name; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(rgba(46,37,30,0.7), rgba(46,37,30,0.7)), url('assets/images/halls/Banner-1.webp') no-repeat center center/cover; 
            min-height: 100vh; 
            display: flex; 
            font-family: 'Inter', sans-serif;
        }
        .auth-right {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
        }
        .auth-form-wrap { 
            width: 100%; 
            max-width: 480px; 
            background: rgba(255, 255, 255, 0.95); 
            padding: 3.5rem; 
            border-radius: var(--radius-sm); 
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
        }
        h1 { font-family: 'Playfair Display', serif; font-weight: 700; color: var(--dark); }
        .form-label { font-weight: 600; color: var(--dark-2); font-size: 0.9rem; }
        .divider { display: flex; align-items: center; gap: 1rem; margin: 1.5rem 0; color: var(--gray); font-size: 0.8rem; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
        @media(max-width:480px) {
            .auth-form-wrap { padding: 2.5rem 1.5rem; }
        }
    </style>
</head>
<body>
    <!-- LEFT PANEL -->
    <div class="auth-left reveal">
        <div class="auth-orb" style="width:350px;height:350px;background:var(--primary);top:-100px;left:-100px;opacity:0.2;"></div>
        <div class="auth-orb" style="width:250px;height:250px;background:var(--secondary);bottom:-80px;right:-60px;opacity:0.15;"></div>

        <div style="position:relative;z-index:2;width:100%;max-width:380px;">
            <div class="auth-brand">
                <?php if (!empty($brand_logo)): ?>
                    <img src="assets/images/<?php echo $brand_logo; ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; margin-right:0.5rem; border:2px solid var(--secondary);">
                <?php else: ?>
                    <i class="fas fa-building-columns"></i>
                <?php endif; ?>
                <?php echo $brand_name; ?>
            </div>
            
            <div style="margin: 1.5rem 0; text-align: center;">
                <img src="assets/images/wedding_illust.svg" alt="Welcome" style="width: 100%; max-width: 250px; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));">
            </div>

            <p class="auth-quote">"Book your dream venue in minutes   Real availability, transparent pricing, instant confirmation."</p>

            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-calendar-check"></i></div>
                <p>Real-time availability checking for all halls</p>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-shield-alt"></i></div>
                <p>100% verified venues with secure bookings</p>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-headset"></i></div>
                <p>24/7 AI chatbot support for instant help</p>
            </div>
        </div>
    </div>

    <!-- RIGHT FORM PANEL -->
    <div class="auth-right reveal">
        <div class="auth-form-wrap">
            <a href="index.php" style="display:inline-flex;align-items:center;gap:0.5rem;color:var(--gray);font-size:0.85rem;margin-bottom:2.5rem;transition:var(--transition); text-decoration:none;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>

            <h1 style="font-size:2rem;margin-bottom:0.5rem;">Welcome Back!</h1>
            <p style="color:#000;margin-bottom:2rem;">Login to manage your hall bookings.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label>Email or Phone Number</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" name="login_id" id="login_id" class="form-control" placeholder="Email Address or Phone Number" value="<?php echo htmlspecialchars($_POST['login_id'] ?? ''); ?>" oninput="validateLoginID()" onchange="validateLoginID()">
                    </div>
                    <div id="loginError" style="font-size:0.75rem;margin-top:0.35rem;"></div>
                </div>
                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                        <label style="margin-bottom:0;">Password</label>
                        <a href="forgot_password.php" style="font-size:0.85rem; color:var(--primary); font-weight:600; text-decoration:none;">Forgot Password?</a>
                    </div>
                    <div class="input-icon-wrap" style="position:relative;">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" oninput="validatePassword()" onchange="validatePassword()">
                        <i class="fas fa-eye" id="togglePwd" onclick="togglePwd()" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--gray-light);left:auto;"></i>
                    </div>
                    <div id="passwordError" style="font-size:0.75rem;margin-top:0.35rem;"></div>
                </div>


                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:0.5rem;">
                    <i class="fas fa-sign-in-alt"></i> Login to Account
                </button>
            </form>



            <p style="text-align:center;margin-top:1.5rem;color:#000;font-size:0.875rem;">
                Don't have an account? <a href="register.php" style="color:var(--primary);font-weight:600;">Register here -></a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Reveal trigger
        window.addEventListener('load', () => {
            document.querySelectorAll('.reveal').forEach(el => el.classList.add('active'));
        });

        function validateLoginID() {
            const el = document.getElementById('login_id');
            const err = document.getElementById('loginError');
            const val = el.value.trim();
            if(!val) {
                err.textContent = 'Email or Phone is required.';
                err.style.color = '#ef4444';
                el.style.borderColor = '#ef4444';
                return false;
            }
            const isEmail = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/.test(val);
            const isPhone = /^\d{10}$/.test(val);
            if(!isEmail && !isPhone) {
                err.textContent = 'Enter a valid 10-digit phone or email.';
                err.style.color = '#ef4444';
                el.style.borderColor = '#ef4444';
                return false;
            }
            err.textContent = '';
            el.style.borderColor = 'var(--success)';
            return true;
        }

        function validatePassword() {
            const el = document.getElementById('password');
            const err = document.getElementById('passwordError');
            const val = el.value.trim();
            if(!val) {
                err.textContent = 'Password is required.';
                err.style.color = '#ef4444';
                el.style.borderColor = '#ef4444';
                return false;
            }
            if(val.length < 6) {
                err.textContent = 'Password must be at least 6 characters.';
                err.style.color = '#ef4444';
                el.style.borderColor = '#ef4444';
                return false;
            }
            if (!/[A-Z]/.test(val) || !/[a-z]/.test(val) || (val.match(/[0-9]/g) || []).length < 2 || !/[^A-Za-z0-9]/.test(val)) {
                err.textContent = 'Must contain 1 uppercase, 1 lowercase, 2 numbers & 1 special char.';
                err.style.color = '#ef4444';
                el.style.borderColor = '#ef4444';
                return false;
            }
            err.textContent = '';
            el.style.borderColor = 'var(--success)';
            return true;
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginOK = validateLoginID();
            const pwdOK = validatePassword();
            if(!loginOK || !pwdOK) {
                e.preventDefault();
            }
        });

        function togglePwd() {
            const p = document.getElementById('password');
            const i = document.getElementById('togglePwd');
            if (p.type === 'password') { p.type = 'text'; i.classList.replace('fa-eye','fa-eye-slash'); }
            else { p.type = 'password'; i.classList.replace('fa-eye-slash','fa-eye'); }
        }

        <?php if (isset($login_success) && $login_success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Login Successful',
            text: 'Welcome back!',
            timer: 1500,
            showConfirmButton: false,
            timerProgressBar: true,
            backdrop: 'rgba(0,0,0,0.85)'
        }).then(() => {
            window.location.href = '<?php echo $redirect_url; ?>';
        });
        <?php endif; ?>
    </script>

    <?php include 'includes/alerts.php'; ?>
</body>
</html>


