<?php
// Determine active page for sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-logo" style="display:flex; align-items:center; gap:0.8rem; padding:1.5rem 1.25rem;">
        <div style="width:40px; height:40px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; background:white; flex-shrink:0; box-shadow:0 2px 5px rgba(0,0,0,0.2);">
            <?php if (!empty($brand_logo)): ?>
                <img src="../assets/images/<?php echo htmlspecialchars($brand_logo); ?>" style="width:100%; height:100%; object-fit:cover;">
            <?php else: ?>
                <i class="fa-solid fa-heart" style="color:var(--primary); font-size:1.2rem;"></i>
            <?php endif; ?>
        </div>
        <div style="font-weight:800; font-size:1.25rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:200px; line-height:1.2; color: white;">
            <?php echo htmlspecialchars($brand_name); ?>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-label">Main</div>
        <a href="dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <div class="sidebar-label">
            Management
        </div>
        <div class="sidebar-submenu">
            <div style="padding-left:0.5rem;">
                <a href="manage_halls.php" class="sidebar-link <?php echo $current_page === 'manage_halls.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-building" style="font-size:0.9rem;"></i> Manage Halls
                </a>
                <a href="manage_rooms.php" class="sidebar-link <?php echo $current_page === 'manage_rooms.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-bed" style="font-size:0.9rem;"></i> Manage Rooms
                </a>
                <a href="manage_categories.php" class="sidebar-link <?php echo $current_page === 'manage_categories.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-list" style="font-size:0.9rem;"></i> Manage Categories
                </a>
                <a href="manage_bookings.php" class="sidebar-link <?php echo $current_page === 'manage_bookings.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-calendar-check" style="font-size:0.9rem;"></i> Manage Bookings
                </a>
                <a href="manage_users.php" class="sidebar-link <?php echo $current_page === 'manage_users.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-users" style="font-size:0.9rem;"></i> Manage Users
                </a>
                <a href="contact_inquiries.php" class="sidebar-link <?php echo $current_page === 'contact_inquiries.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-envelope" style="font-size:0.9rem;"></i> Contact Enquiries
                </a>
                <a href="reports.php" class="sidebar-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-chart-line" style="font-size:0.9rem;"></i> Reports
                </a>
            </div>
        </div>

        <div class="sidebar-label">
            Venue Models (Categories)
        </div>
        <div class="sidebar-submenu">
            <div class="sidebar-models-scroll" style="padding-left:0.5rem;">
                <?php 
                try {
                    $sidebar_halls = $pdo->query("SELECT id, name FROM halls ORDER BY name ASC")->fetchAll();
                    foreach ($sidebar_halls as $shall): 
                        $is_h_active = (isset($_GET['id']) && $_GET['id'] == $shall['id'] && $current_page === 'manage_halls.php');
                ?>
                    <a href="manage_halls.php?id=<?php echo $shall['id']; ?>" class="sidebar-link <?php echo $is_h_active ? 'active' : ''; ?>" style="padding: 0.5rem 1rem; font-size: 0.82rem; margin-bottom: 0;">
                        <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-hotel" style="font-size:0.8rem; opacity: 0.7;"></i> <?php echo htmlspecialchars($shall['name']); ?>
                    </a>
                <?php 
                    endforeach; 
                } catch (Exception $e) {} 
                ?>
            </div>
        </div>

        <div class="sidebar-label">
            Site
        </div>
        <div class="sidebar-submenu">
            <div style="padding-left:0.5rem;">
                <a href="banner_settings.php" class="sidebar-link <?php echo $current_page === 'banner_settings.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-image" style="font-size:0.9rem;"></i> Banner Settings
                </a>
                <a href="manage_gallery.php" class="sidebar-link <?php echo $current_page === 'manage_gallery.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-images" style="font-size:0.9rem;"></i> Manage Gallery
                </a>
                <a href="manage_explore.php" class="sidebar-link <?php echo $current_page === 'manage_explore.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-compass" style="font-size:0.9rem;"></i> Manage Explore
                </a>
                <a href="site_settings.php" class="sidebar-link <?php echo $current_page === 'site_settings.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-cog" style="font-size:0.9rem;"></i> Site Settings
                </a>
                <a href="update_credentials.php" class="sidebar-link <?php echo $current_page === 'update_credentials.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-cog" style="font-size:0.9rem;"></i> Update Credentials
                </a>

            </div>
        </div>
    </nav>

    <div style="padding: 1rem; text-align: center;">
        <div style="font-size: 0.65rem; color: rgba(255,255,255,0.8); letter-spacing: 0.05em; font-weight: 600;"><?php echo $brand_name; ?> Admin</div>
    </div>

    <div class="sidebar-footer">
        <a href="logout.php" onclick="return confirm('Are you sure to Logout?')" class="sidebar-link" style="background:rgba(239,68,68,0.1);color:#fca5a5;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>
<?php include '../includes/alerts.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const topbar = document.querySelector('.admin-topbar');
    if (topbar && !document.querySelector('.admin-menu-toggle')) {
        const firstDiv = topbar.querySelector('div');
        if (firstDiv) {
            firstDiv.style.display = 'flex';
            firstDiv.style.alignItems = 'center';
            
            const btn = document.createElement('button');
            btn.className = 'admin-menu-toggle';
            btn.innerHTML = '<i class="fas fa-bars"></i>';
            btn.style.cssText = 'background:transparent; border:none; font-size:1.4rem; color:var(--dark); cursor:pointer; margin-right:1rem; padding:0.1rem 0.5rem; transition:0.3s; outline:none;';
            
            const toggleVisibility = () => {
                btn.style.display = window.innerWidth <= 1150 ? 'block' : 'none';
            };
            window.addEventListener('resize', toggleVisibility);
            toggleVisibility();

            firstDiv.insertBefore(btn, firstDiv.firstChild);
            
            const sidebar = document.getElementById('adminSidebar');
            
            // Create overlay
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            Object.assign(overlay.style, {
                position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', 
                zIndex: 1050, display: 'none', opacity: 0, transition: '0.3s'
            });
            document.body.appendChild(overlay);

            btn.addEventListener('click', function() {
                sidebar.classList.add('open');
                overlay.style.display = 'block';
                setTimeout(() => overlay.style.opacity = 1, 10);
            });
            
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                overlay.style.opacity = 0;
                setTimeout(() => overlay.style.display = 'none', 300);
            });
        }
    }
});
</script>


