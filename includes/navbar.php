<?php
// Determine current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_view = $_GET['view'] ?? '';
?>
<nav class="navbar" id="navbar">
    <!-- Top Bar: Brand & Actions -->
    <div class="navbar-top">
        <div class="container navbar-inner">
            <a href="index.php" class="navbar-brand">
                <?php if (!empty($brand_logo)): ?>
                    <img src="assets/images/<?php echo $brand_logo; ?>" alt="Logo">
                <?php endif; ?>
                <span><?php echo $brand_name; ?></span>
            </a>

            <div class="nav-actions">
                <?php if (isLoggedIn()): ?>
                    <div class="user-dropdown-wrap" id="userDropdownWrap">
                        <button class="user-dropdown-trigger" id="userDropdownTrigger" onclick="toggleUserDropdown()">
                            <i class="fas fa-user-circle"></i>
                            <span class="user-name-text"><?php echo explode(' ', $_SESSION['user_name'])[0]; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <ul class="user-dropdown-menu">
                            <li><a href="my_bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                            <?php if (isAdmin()): ?>
                                <li><a href="admin/dashboard.php"><i class="fas fa-user-shield"></i> Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr></li>
                            <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary nav-desktop-only">Book Now</a>
                <?php endif; ?>

                <button class="navbar-toggler" onclick="toggleMobileMenu()">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Bottom Bar: Navigation Links -->
    <div class="navbar-bottom">
        <div class="container">
            <ul class="nav-links" id="navLinks">
                <li class="mobile-menu-header">
                    <span class="brand-name-mobile"><?php echo $brand_name; ?></span>
                    <button class="close-nav" onclick="toggleMobileMenu()">&times;</button>
                </li>
                <li><a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="about.php" class="<?php echo $current_page === 'about.php' ? 'active' : ''; ?>">About Us</a></li>
                <li><a href="halls.php" class="<?php echo ($current_page === 'halls.php' && $current_view !== 'room_types') ? 'active' : ''; ?>">Halls</a></li>
                <li><a href="halls.php?view=room_types" class="<?php echo ($current_page === 'halls.php' && $current_view === 'room_types') ? 'active' : ''; ?>">Rooms</a></li>
                <li><a href="gallery.php" class="<?php echo $current_page === 'gallery.php' ? 'active' : ''; ?>">Gallery</a></li>
                <li><a href="explore.php" class="<?php echo $current_page === 'explore.php' ? 'active' : ''; ?>">Explore</a></li>
                <li><a href="contact.php" class="<?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
                
                <?php if (isLoggedIn()): ?>
                    <li class="nav-mobile-only"><a href="my_bookings.php">My Bookings</a></li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-mobile-only"><a href="admin/dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li class="nav-mobile-only"><a href="logout.php" style="color:var(--danger) !important;">Logout</a></li>
                <?php else: ?>
                    <li class="nav-mobile-only"><a href="login.php" class="btn btn-primary">Login / Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>



<script>
function toggleMobileMenu() {
    const nav = document.getElementById('navLinks');
    nav.classList.toggle('open');
    document.body.classList.toggle('menu-open');
}

function toggleUserDropdown() {
    const wrap = document.getElementById('userDropdownWrap');
    wrap.classList.toggle('open');
}

window.addEventListener('scroll', function() {
    const nav = document.getElementById('navbar');
    if (window.scrollY > 50) {
        nav.classList.add('scrolled');
    } else {
        nav.classList.remove('scrolled');
    }
});

// Close dropdown on outside click
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('userDropdownWrap');
    const trigger = document.getElementById('userDropdownTrigger');
    if (wrap && !wrap.contains(e.target) && !trigger.contains(e.target)) {
        wrap.classList.remove('open');
    }
});
</script>
<?php include_once 'alerts.php'; ?>
