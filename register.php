<?php
require_once 'includes/auth_functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!preg_match('/^[A-Za-z\s\.\'\-]+$/', $name)) {
        $error = 'Please enter a valid name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/', $email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $error = 'Please enter a valid 10-digit phone number.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || preg_match_all('/[0-9]/', $password) < 2 || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = 'Password must contain at least 1 uppercase letter, 1 lowercase letter, 2 numbers, and 1 special character.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'This email is already registered. Please login instead.';
        } elseif (registerUser($pdo, $name, $email, $phone, $password)) {
            $register_success = true;
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?php echo $brand_name; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('assets/images/halls/Banner-2.webp') no-repeat center center/cover;
            min-height: 100vh;
            display: flex;
        }

        .auth-left {
            display: none;
        }

        .auth-right {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            overflow-y: auto;
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 650px;
            background: rgba(255, 255, 255, 0.6);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(255, 255, 255, 0.2);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem !important;
        }

        .form-group label {
            margin-bottom: 0.35rem !important;
            font-size: 0.85rem !important;
        }

        .form-control {
            padding: 0.7rem 1rem 0.7rem 2.8rem !important;
            font-size: 0.9rem !important;
            min-height: auto !important;
        }

        @media(max-width:1150px) {
            .auth-right {
                width: 100%;
            }
        }

        @media(max-width:600px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .auth-right {
                padding: 1rem;
            }

            .auth-form-wrap {
                max-width: 100%;
                padding: 1.5rem !important;
            }

            h1 {
                font-size: 1.5rem !important;
            }
        }

        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: var(--border);
            margin-top: 0.4rem;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s, background 0.3s;
        }

        .is-invalid {
            border-color: #ef4444 !important;
        }
    </style>
</head>

<body>
    <!-- LEFT -->
    <div class="auth-left reveal">
        <div style="position:absolute;width:300px;height:300px;background:var(--primary);opacity:0.15;border-radius:50%;filter:blur(60px);top:-80px;right:-80px;"></div>
        <div style="position:absolute;width:200px;height:200px;background:var(--secondary);opacity:0.15;border-radius:50%;filter:blur(60px);bottom:-60px;left:-40px;"></div>

        <div style="position:relative;z-index:2;text-align:center;width:100%;max-width:380px;">
            <div style="width:70px;height:70px;background:rgba(255,255,255,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;border:1px solid rgba(255,255,255,0.2);">
                <i class="fas fa-building-columns" style="font-size:1.8rem;color:var(--secondary);"></i>
            </div>

            <div style="margin-bottom: 1.5rem; text-align: center;">
                <img src="assets/images/wedding_illust.svg" alt="Join Us" style="width: 100%; max-width: 220px; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));">
            </div>

            <h2 style="color:white;font-size:1.6rem;margin-bottom:0.75rem;">Join <?php echo $brand_name; ?></h2>
            <p style="color:rgba(255,255,255,0.7);font-size:0.85rem;line-height:1.6;margin-bottom:1.5rem;">Create your free account and start booking premium halls for your special events today.</p>

            <div style="display:flex;flex-direction:column;gap:1rem;text-align:left;">
                <?php foreach (
                    [
                        ['fa-check-circle', '#10b981', 'Free registration, no hidden fees'],
                        ['fa-calendar-check', 'var(--secondary)', 'Instant booking confirmation'],
                        ['fa-shield-alt', '#f59e0b', 'Secure and protected bookings'],
                    ] as [$ic, $col, $txt]
                ): ?>
                    <div style="display:flex;align-items:center;gap:0.75rem;background:rgba(255,255,255,0.07);padding:0.75rem 1rem;border-radius:10px;">
                        <i class="fas <?php echo $ic; ?>" style="color:<?php echo $col; ?>;"></i>
                        <span style="color:rgba(255,255,255,0.85);font-size:0.875rem;"><?php echo $txt; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT -->
    <div class="auth-right reveal delay-100">
        <div class="auth-form-wrap">
            <a href="index.php" style="display:inline-flex;align-items:center;gap:0.5rem;color:#000;font-size:0.85rem;margin-bottom:1.2rem;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>

            <h1 style="font-size:1.75rem;margin-bottom:0.2rem;">Create Account</h1>
            <p style="color:#000;margin-bottom:1.2rem;font-size:0.9rem;">Register to book your perfect hall.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" id="regForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span style="color:var(--danger)">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-user"></i>
                            <input type="text" name="name" id="name" class="form-control" placeholder="Your full name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" onchange="validateName()" oninput="validateName()">
                        </div>
                        <div id="nameError" style="font-size:0.75rem;margin-top:0.35rem;"></div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number <span style="color:var(--danger)">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-phone"></i>
                            <input type="number" name="phone" id="phone" class="form-control" placeholder="10-digit mobile" maxlength="10" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" onchange="validatePhone()" oninput="validatePhone()">
                        </div>
                        <div id="phoneError" style="font-size:0.75rem;margin-top:0.35rem;"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Address <span style="color:var(--danger)">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" id="email" class="form-control" placeholder="your@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" onchange="validateEmail()" oninput="validateEmail()">
                    </div>
                    <div id="emailError" style="font-size:0.75rem;margin-top:0.35rem;"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span style="color:var(--danger)">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="pwd" class="form-control" placeholder="Min. 6 characters" oninput="checkStrength(this.value); validatePassword()" onchange="validatePassword()">
                        </div>
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill" style="width:0%;background:transparent;"></div>
                        </div>
                        <div id="strengthText" style="font-size:0.75rem;color:var(--gray-light);margin-top:0.3rem;"></div>
                        <div id="passwordError" style="font-size:0.75rem;margin-top:0.3rem;"></div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span style="color:var(--danger)">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" id="cpwd" class="form-control" placeholder="Re-enter password" oninput="validatePassword()" onchange="validatePassword()">
                        </div>
                        <div id="matchText" style="font-size:0.75rem;margin-top:0.35rem;"></div>
                    </div>
                </div>


                <div style="display:flex;align-items:flex-start;gap:0.75rem;margin-bottom:1.2rem;padding:0.85rem;background:#f8fafc;border-radius:var(--radius);border:1px solid var(--border);">
                    <input type="checkbox" id="agreeTerms" style="margin-top:3px;width:16px;height:16px;accent-color:var(--primary);flex-shrink:0;">
                    <label for="agreeTerms" style="font-size:0.85rem;color:var(--gray);cursor:pointer;">I agree to the <a href="#" style="color:var(--primary);">Terms of Service</a> and <a href="#" style="color:var(--primary);">Privacy Policy</a></label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;padding:0.75rem 1.5rem;">
                    <i class="fas fa-user-plus"></i> Create Free Account
                </button>
            </form>

            <p style="text-align:center;margin-top:1.2rem;color:#000;font-size:0.875rem;">
                Already have an account? <a href="login.php" style="color:var(--primary);font-weight:600;">Login here -></a>
            </p>
        </div>
    </div>

    <script>
        // Reveal trigger
        window.addEventListener('load', () => {
            document.querySelectorAll('.reveal').forEach(el => el.classList.add('active'));
        });

        function checkStrength(val) {
            const bar = document.getElementById('strengthFill');
            const txt = document.getElementById('strengthText');
            let score = 0;
            if (val.length >= 6) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [{
                    label: '',
                    color: 'transparent',
                    width: '0%'
                },
                {
                    label: 'Weak',
                    color: '#ef4444',
                    width: '25%'
                },
                {
                    label: 'Fair',
                    color: '#f59e0b',
                    width: '50%'
                },
                {
                    label: 'Good',
                    color: '#3b82f6',
                    width: '75%'
                },
                {
                    label: 'Strong',
                    color: '#10b981',
                    width: '100%'
                },
            ];
            const level = levels[score];
            bar.style.width = level.width;
            bar.style.background = level.color;
            txt.textContent = score > 0 ? level.label : '';
            txt.style.color = level.color;
        }

        // New validation functions
        function validateName() {
            const name = document.getElementById('name');
            const err = document.getElementById('nameError');
            const value = name.value.trim();
            if (!value) {
                err.textContent = 'Name is required.';
                err.style.color = '#ef4444';
                name.classList.add('is-invalid');
                return false;
            }
            const namePattern = /^[A-Za-z\s\.\'\-]+$/;
            if (!namePattern.test(value)) {
                err.textContent = 'Please enter a valid name.';
                err.style.color = '#ef4444';
                name.classList.add('is-invalid');
                return false;
            }
            err.textContent = '';
            name.classList.remove('is-invalid');
            return true;
        }

        function validateEmail() {
            const email = document.getElementById('email');
            const err = document.getElementById('emailError');
            const value = email.value.trim();
            const regex = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;
            if (!value) {
                err.textContent = 'Email is required.';
                err.style.color = '#ef4444';
                email.classList.add('is-invalid');
                return false;
            }
            if (!regex.test(value)) {
                err.textContent = 'Please enter a valid email address.';
                err.style.color = '#ef4444';
                email.classList.add('is-invalid');
                return false;
            }
            err.textContent = '';
            email.classList.remove('is-invalid');
            return true;
        }

        function validatePhone() {
            const phone = document.getElementById('phone');
            const err = document.getElementById('phoneError');
            const value = phone.value.trim();

            if (!value) {
                err.textContent = 'Phone number is required.';
                err.style.color = '#ef4444';
                phone.classList.add('is-invalid');
                return false;
            }

            if (!/^\d{10}$/.test(value)) {
                err.textContent = 'Please enter a valid 10-digit phone number.';
                err.style.color = '#ef4444';
                phone.classList.add('is-invalid');
                return false;
            }

            err.textContent = '';
            phone.classList.remove('is-invalid');
            return true;
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

        // Form submit validation
        document.getElementById('regForm').addEventListener('submit', function(e) {
            const nameOK = validateName();
            const emailOK = validateEmail();
            const phoneOK = validatePhone();
            const pwdOK = validatePassword();
            const termsChecked = document.getElementById('agreeTerms').checked;

            if (!nameOK || !emailOK || !phoneOK || !pwdOK) {
                e.preventDefault();
                return false;
            }

            if (!termsChecked) {
                alert('Please agree to the Terms of Service and Privacy Policy');
                e.preventDefault();
                return false;
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (isset($register_success) && $register_success): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Registration Successful!',
            text: 'You will be redirected to the login page.',
            timer: 2000,
            showConfirmButton: false,
            timerProgressBar: true,
            backdrop: 'rgba(0,0,0,0.85)'
        }).then(() => {
            window.location.href = 'login.php?registered=1';
        });
    </script>
    <?php endif; ?>

    <?php include 'includes/alerts.php'; ?>
</body>

</html>