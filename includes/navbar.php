<?php
// Determine current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_view = $_GET['view'] ?? '';
?>
<nav class="navbar dual-navbar" id="navbar" style="height:auto; flex-direction:column; padding:0; align-items:stretch; background:transparent; box-shadow:none;">
    
    <!-- TOP ROW: Brand and Mobile Toggle -->
    <div class="navbar-top" style="display:flex; justify-content:center; align-items:center; background:#ffffff; padding:0.5rem 4%; height:75px; box-shadow:0 4px 15px rgba(233, 30, 99, 0.05); position:relative; z-index:1001;">
        <div style="display:flex; justify-content:space-between; align-items:center; width:100%; max-width:1200px;">
            <!-- Animated Heart Logo -->
            <a href="index.php" class="navbar-brand" style="margin:0; padding:0;">
                <div class="brand-logo-circle" style="width:42px; height:42px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; background:var(--primary-light); flex-shrink:0;">
                    <?php if (!empty($brand_logo)): ?>
                        <img src="assets/images/<?php echo $brand_logo; ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <i class="fa-solid fa-heart" style="color:var(--primary); font-size:1.6rem;"></i>
                    <?php endif; ?>
                </div>
                <span style="font-weight:900; font-size:1.4rem; color:var(--primary); white-space:nowrap; letter-spacing:0.02em; font-family:'Cinzel', serif; text-transform:uppercase;"><?php echo $brand_name; ?></span>
            </a>

            <!-- Top Right Group -->
            <div style="display:flex; align-items:center; gap:1.2rem;">
                <?php if (isLoggedIn()): ?>
                    <div class="user-dropdown-wrap nav-desktop-only" id="userDropdownWrap">
                        <button class="user-dropdown-trigger" id="userDropdownTrigger" onclick="toggleUserDropdown()" aria-haspopup="true" aria-expanded="false">
                            <span class="user-avatar"><i class="fas fa-user-circle"></i></span>
                            <span class="user-dropdown-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            <i class="fas fa-chevron-down user-chevron"></i>
                        </button>
                        <ul class="user-dropdown-menu" id="userDropdownMenu" role="menu">
                            <li class="user-dropdown-header">
                                <div class="udrop-avatar-big">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="udrop-info">
                                    <span class="udrop-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                    <div class="udrop-status-pill">
                                        <span class="user-online-dot small"></span>
                                        Online
                                    </div>
                                </div>
                            </li>
                            <li class="udrop-menu-items">
                                <a href="my_bookings.php" class="udrop-link udrop-bookings<?php echo $current_page === 'my_bookings.php' ? ' active' : ''; ?>" role="menuitem">
                                    <i class="fas fa-calendar-check"></i> MY BOOKINGS
                                </a>
                                
                                <?php if (isAdmin()): ?>
                                <a href="admin/dashboard.php" class="udrop-link" role="menuitem">
                                    <i class="fas fa-cog"></i> Admin Panel
                                </a>
                                <?php endif; ?>

                                <a href="logout.php" class="udrop-link udrop-logout" role="menuitem">
                                    <i class="fas fa-sign-out-alt"></i> LOGOUT
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="nav-desktop-only" style="margin-right: 10px;">
                        <a href="login.php" class="btn btn-outline btn-sm <?php echo $current_page === 'login.php' ? 'active' : ''; ?>" style="border-radius:100px; padding: 0.5rem 1.5rem;">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Hamburger for mobile -->
                <div class="navbar-toggler" onclick="document.getElementById('navLinks').classList.toggle('open'); this.classList.toggle('active')">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- NAVIGATION LINKS (Moved outside bottom bar to prevent clipping on mobile) -->
    <ul class="nav-links has-dual-nav" id="navLinks" style="margin:0; justify-content:space-between; width:100%; max-width:1200px; gap:0;">
        <!-- Mobile Close Button (Optional since toggler turns to X, but kept for UX) -->
        <li class="mobile-only" style="display:none; position:absolute; top:20px; right:20px; z-index:20;">
            <div class="close-nav" onclick="document.getElementById('navLinks').classList.remove('open'); document.querySelector('.navbar-toggler').classList.remove('active')" style="width:40px; height:40px; border-radius:50%; background:var(--primary-light); display:flex; align-items:center; justify-content:center; color:var(--primary); cursor:pointer; box-shadow:var(--shadow-sm);"><i class="fas fa-times"></i></div>
        </li>
        <li class="mobile-only" style="display:none; text-align:center; padding:2rem 1rem 1rem;">
            <div class="brand-logo-circle" style="width:60px; height:60px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; background:var(--primary-light); margin: 0 auto 0.8rem;">
                <?php if (!empty($brand_logo)): ?>
                    <img src="assets/images/<?php echo $brand_logo; ?>" style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                    <i class="fa-solid fa-heart" style="color:var(--primary); font-size:2rem;"></i>
                <?php endif; ?>
            </div>
            <div style="font-family:'Cinzel',serif; font-weight:900; font-size:1.1rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em;"><?php echo $brand_name; ?></div>
            <div style="font-size:0.75rem; color:var(--gray); margin-top:0.25rem;">Where Comfort Meets Celebration</div>
            <div style="width:40px; height:3px; background:var(--gradient-primary); margin:1.2rem auto 0.5rem; border-radius:2px; opacity:0.6;"></div>
        </li>
        <li>
            <a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Home
            </a>
        </li>
        <li>
            <a href="about.php" class="<?php echo $current_page === 'about.php' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i> About Us
            </a>
        </li>
        <li>
            <a href="halls.php" class="<?php echo ($current_page === 'halls.php' && $current_view !== 'room_types') ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Services
            </a>
        </li>
        <li>
            <a href="halls.php?view=room_types" class="<?php echo ($current_page === 'halls.php' && $current_view === 'room_types') ? 'active' : ''; ?>">
                <i class="fas fa-bed"></i> Rooms
            </a>
        </li>
        <li>
            <a href="gallery.php" class="<?php echo $current_page === 'gallery.php' ? 'active' : ''; ?>">
                <i class="fas fa-images"></i> Gallery
            </a>
        </li>
        <li>
            <a href="explore.php" class="<?php echo $current_page === 'explore.php' ? 'active' : ''; ?>">
                <i class="fas fa-compass"></i> Explore
            </a>
        </li>
        <li>
            <a href="contact.php" class="<?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i> Contact Us
            </a>
        </li>

        <?php if (isLoggedIn()): ?>
            <li class="nav-mobile-only" style="margin-top:0.5rem; border-top:1px solid #f1f5f9; padding-top:0.5rem;">
                <a href="my_bookings.php" class="<?php echo $current_page === 'my_bookings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check" style="color:var(--primary);"></i> My Bookings
                </a>
            </li>
            <?php if (isAdmin()): ?>
            <li class="nav-mobile-only">
                <a href="admin/dashboard.php">
                    <i class="fas fa-user-shield" style="color:var(--primary);"></i> Admin Panel
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-mobile-only">
                <a href="logout.php" style="color:var(--danger) !important;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        <?php else: ?>
            <li class="nav-mobile-only nav-btn-wrap" style="padding: 1rem 1rem !important;">
                <a href="login.php" class="btn btn-primary" style="width:100%; justify-content:center; border-radius:12px;">
                    <i class="fas fa-sign-in-alt"></i> Login / Register
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <!-- BOTTOM ROW: Container for desktop spacing -->
    <div class="navbar-bottom" style="display:flex; justify-content:center; background:rgba(255, 255, 255, 0.95); backdrop-filter:blur(10px); border-bottom:1px solid #fdf2f8; z-index:1000; position:relative; min-height:55px;">
        <div style="width:100%; max-width:1200px; display:flex;">
            <!-- Dummy spacer or category links could go here if needed to stay in place -->
        </div>
    </div>
</nav>

<style>
/* Show/hide helpers for desktop vs mobile nav */
.nav-desktop-only { display: flex; }   /* shown on desktop */
.nav-links li.nav-mobile-only  { display: none; }   /* hidden on desktop */

@media (max-width: 1150px) {
    .nav-desktop-only { display: none  !important; } /* hide dropdown/top-btns on mobile */
    .nav-links li.nav-mobile-only  { display: flex  !important; } /* show plain links on mobile menu */
}

/* Equal spacing for Dual Nav categories */
.nav-links.has-dual-nav li:not(.mobile-only) {
    flex: 1;
}
.nav-links.has-dual-nav a:not(.btn) {
    width: 100%;
    justify-content: center !important;
}

@media (max-width: 768px) {
    .brand-logo-circle { width: 34px !important; height: 34px !important; }
}
@media (min-width: 321px) and (max-width: 426px){
    .navbar-brand span { font-size: 0.82rem !important; max-width: 220px; line-height: 1.3; }
}
@media (max-width: 320px){
    .navbar-brand span { font-size: 0.82rem !important; white-space: normal !important; max-width: 160px; line-height: 1.3; }
}
    @media (max-width: 1150px) {
    .nav-links {
        background: #ffffff !important;
        padding: 1rem 1.25rem 2rem !important;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        box-shadow: -5px 0 30px rgba(0,0,0,0.1);
        border-right: 1px solid #f1f5f9;
        z-index: 2000 !important;
    }
    .nav-links li.mobile-only { display: block !important; }
    .nav-links a:not(.btn) {
        font-size: 0.8rem !important;
        padding: 0.7rem 1rem !important;
        margin-bottom: 0.2rem !important;
    }
    .nav-links .nav-btn-wrap {
        padding: 0.75rem 0 0 !important;
        margin-top: 0.5rem !important;
        border-top: 1px solid var(--border);
    }
    .nav-links .nav-btn-wrap .btn {
        padding: 0.6rem 1rem !important;
        font-size: 0.8rem !important;
    }
    /* User dropdown mobile */
    .user-dropdown-wrap {
        width: 90%;
        padding: 0.75rem 0 0 !important;
        margin-top: 0.5rem !important;
        border-top: 1px solid var(--border);
    }
    .user-dropdown-trigger {
        width: 100%;
        justify-content: flex-start;
        padding: 0.6rem 1rem !important;
        font-size: 0.8rem !important;
    }
    .user-dropdown-menu {
        position: static !important;
        box-shadow: none !important;
        border: 1px solid var(--border);
        margin-top: 0.5rem;
        transform: none !important;
        opacity: 1 !important;
        pointer-events: auto !important;
    }
}

/* ── User Dropdown ──────────────────────── */
.user-dropdown-wrap {
    position: relative;
    display: flex;
    align-items: stretch;
    height: 100%;
    padding: 0;
}

.user-dropdown-trigger {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: var(--primary);
    border: none;
    border-radius: 100px;
    padding: 0 1.25rem;
    height: 40px;
    align-self: center;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    font-size: 0.85rem;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    transition: var(--transition);
    outline: none;
    white-space: nowrap;
    box-shadow: 0 4px 15px rgba(233, 30, 99, 0.2);
}

.user-dropdown-trigger:hover {
    background: var(--primary-deep);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(233, 30, 99, 0.3);
}

.user-dropdown-trigger:hover .user-chevron,
.user-dropdown-trigger:hover .user-avatar i {
    color: #fff;
}

.user-dropdown-wrap.open .user-dropdown-trigger {
    background: var(--primary-deep);
    border-radius: 100px;
    height: 40px;
}

.user-dropdown-wrap.open .user-chevron {
    transform: rotate(180deg);
    color: #fff;
}

.user-avatar {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: var(--transition);
}

.user-avatar i {
    font-size: 1rem;
    color: #fff;
    line-height: 1;
    transition: var(--transition);
}

.user-online-dot {
    width: 9px;
    height: 9px;
    background: #22c55e;
    border-radius: 50%;
    border: 1.5px solid #fff;
    flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(34,197,94,0.25);
    animation: onlinePulse 2.5s ease-in-out infinite;
}

.user-online-dot.small {
    width: 7px;
    height: 7px;
    display: inline-block;
    vertical-align: middle;
    margin-right: 3px;
}

@keyframes onlinePulse {
    0%, 100% { box-shadow: 0 0 0 2px rgba(34,197,94,0.25); }
    50%       { box-shadow: 0 0 0 5px rgba(34,197,94,0.10); }
}

.user-chevron {
    font-size: 0.7rem;
    color: #fff;
    transition: transform 0.3s ease, color 0.3s ease;
    margin-left: 0.1rem;
}

/* ── User Dropdown Redesign ──────────────── */
.user-dropdown-menu {
    position: absolute;
    top: calc(100% + 15px);
    right: 0;
    min-width: 260px;
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.12);
    border: none;
    padding: 1.5rem;
    opacity: 0;
    transform: translateY(12px);
    pointer-events: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1100;
}

.user-dropdown-wrap.open .user-dropdown-menu {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}

.user-dropdown-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.udrop-avatar-big i {
    font-size: 2.8rem;
    color: var(--primary);
}

.udrop-name {
    display: block;
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    font-size: 1.25rem;
    color: #1a0011;
    line-height: 1.2;
}

.udrop-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(34, 197, 94, 0.08);
    color: #22c55e;
    padding: 3px 10px;
    border-radius: 100px;
    font-size: 0.75rem;
    font-weight: 700;
    margin-top: 4px;
}

.udrop-status-pill .user-online-dot {
    width: 6px;
    height: 6px;
    animation: none;
    box-shadow: none;
    border: none;
}

.udrop-menu-items {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.udrop-bookings.active {
    color: var(--primary-deep) !important;
    background: var(--primary-light);
    border-radius: 12px;
}

.udrop-link {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.75rem 0.5rem;
    color: var(--primary);
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    transition: var(--transition);
}

.udrop-link i {
    font-size: 1.1rem;
    width: 24px;
    display: flex;
    justify-content: center;
}

.udrop-link:hover {
    padding-left: 0.8rem;
    color: var(--primary-deep);
}

.udrop-logout {
    color: var(--danger) !important;
    margin-top: 1rem;
}

.udrop-logout:hover {
    background: #fff5f5;
    color: #c53030 !important;
    border-radius: 12px;
}

body {
    padding-top: 140px !important; /* Offset for the new dual-row navbar */
}

@media (max-width: 1150px) {
    body {
        padding-top: 75px !important; /* On mobile, only the top bar is fixed and bottom nav hides in menu */
    }
    .navbar-bottom {
        display: none !important; /* Hide the bottom row entirely on mobile */
    }
    .navbar.dual-navbar {
        height: 75px !important;
    }
}
</style>

<script>
// Navbar scroll effect
window.addEventListener('scroll', function() {
    const nav = document.getElementById('navbar');
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 50);
});
// Close mobile nav on outside click
document.addEventListener('click', function(e) {
    const nav = document.getElementById('navLinks');
    const toggle = document.querySelector('.navbar-toggler');
    if (nav && toggle && !nav.contains(e.target) && !toggle.contains(e.target)) {
        nav.classList.remove('open');
        toggle.classList.remove('active');
    }
});
// User dropdown toggle
function toggleUserDropdown() {
    const wrap = document.getElementById('userDropdownWrap');
    const trigger = document.getElementById('userDropdownTrigger');
    if (!wrap) return;
    const isOpen = wrap.classList.toggle('open');
    trigger.setAttribute('aria-expanded', isOpen);
}
// Close user dropdown on outside click / Escape
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('userDropdownWrap');
    if (wrap && !wrap.contains(e.target)) {
        wrap.classList.remove('open');
        const trigger = document.getElementById('userDropdownTrigger');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const wrap = document.getElementById('userDropdownWrap');
        if (wrap) {
            wrap.classList.remove('open');
            const trigger = document.getElementById('userDropdownTrigger');
            if (trigger) { trigger.setAttribute('aria-expanded', 'false'); trigger.focus(); }
        }
    }
});
</script>
<?php include_once 'alerts.php'; ?>
