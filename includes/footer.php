<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div class="footer-col">
                <a href="index.php" class="footer-brand">
                    <?php if (!empty($brand_logo)): ?>
                        <img src="assets/images/<?php echo $brand_logo; ?>" alt="Logo">
                    <?php endif; ?>
                    <span><?php echo $brand_name; ?></span>
                </a>
                <p class="footer-desc">
                    Experience luxury and comfort at Sri Lakshmi Residency & Mahal. We provide the perfect setting for your most cherished celebrations and stays.
                </p>
                <div class="footer-socials">
                    <?php if(!empty($social_facebook)): ?>
                        <a href="<?php echo htmlspecialchars($social_facebook); ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($social_instagram)): ?>
                        <a href="<?php echo htmlspecialchars($social_instagram); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($social_youtube)): ?>
                        <a href="<?php echo htmlspecialchars($social_youtube); ?>" target="_blank"><i class="fab fa-youtube"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-col">
                <h4 class="footer-title">Navigation</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="halls.php">Halls & Rooms</a></li>
                    <li><a href="gallery.php">Our Gallery</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="footer-col">
                <h4 class="footer-title">Contact Us</h4>
                <ul class="footer-contact-list">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($footer_address); ?></span>
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($footer_phone); ?></span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($footer_email); ?></span>
                    </li>
                </ul>
            </div>

            <!-- Map/Location -->
            <div class="footer-col">
                <h4 class="footer-title">Find Us</h4>
                <div class="footer-map-wrap">
                    <?php if($google_maps_iframe): ?>
                        <iframe src="<?php echo htmlspecialchars($google_maps_iframe); ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    <?php else: ?>
                        <div class="map-placeholder">Map not available</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo $brand_name; ?>. All Rights Reserved.</p>
            <p class="developed-by">Designed & Developed by <a href="https://anjanainfotech.in/" target="_blank">Anjana Infotech</a></p>
        </div>
    </div>
</footer>

<?php include_once 'alerts.php'; ?>
