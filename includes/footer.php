<footer class="footer">
    <div class="container">
        <div style="display:grid; grid-template-columns:1.5fr 0.9fr 1.2fr 1.6fr; gap:2.5rem; align-items:flex-start; font-family:'Inter', sans-serif;">
            <div>
                <div class="footer-logo" style="display:flex; align-items:center; gap:0.75rem; font-weight:800; color:white; font-size:1.25rem; margin-bottom:1.25rem; font-family:'Poppins', sans-serif;">
                    <div style="width:40px; height:40px; border-radius:50%; overflow:hidden; border:2px solid var(--primary); display:flex; align-items:center; justify-content:center; background:white; flex-shrink:0;">
                         <?php if (!empty($brand_logo)): ?>
                            <img src="assets/images/<?php echo $brand_logo; ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <i class="fas fa-building-columns" style="color:var(--primary); font-size:1.1rem;"></i>
                        <?php endif; ?>
                    </div>
                    <span><?php echo htmlspecialchars($brand_name); ?></span>
                </div>
                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.6rem;">
                    <li style="font-size:0.92rem; line-height:1.5; color:rgba(255,255,255,0.85);">Premium AC Rooms with Breakfast</li>
                    <li style="font-size:0.92rem; line-height:1.5; color:rgba(255,255,255,0.85);">Fully AC Mahal for Grand Celebrations</li>
                    <li style="font-size:0.92rem; line-height:1.5; color:rgba(255,255,255,0.85);">Trusted Hospitality & Event Services</li>
                </ul>
            </div>
            <div>
                <div style="font-weight:700; color:white; margin-bottom:1.25rem; font-size:0.95rem; font-family:'Poppins', sans-serif; text-transform:uppercase; letter-spacing:0.03em;">Quick Links</div>
                <div class="footer-links" style="gap:0.6rem;">
                    <a href="index.php" style="font-size:0.92rem; color:rgba(255,255,255,0.8);">Home</a>
                    <a href="halls.php" style="font-size:0.92rem; color:rgba(255,255,255,0.8);">Services</a>
                    <a href="contact.php" style="font-size:0.92rem; color:rgba(255,255,255,0.8);">Contact us</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="my_bookings.php" style="font-size:0.92rem; color:rgba(255,255,255,0.8);">My Bookings</a>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <div style="font-weight:700; color:white; margin-bottom:1.25rem; font-size:0.95rem; font-family:'Poppins', sans-serif; text-transform:uppercase; letter-spacing:0.03em;">Contact</div>
                <div style="font-size:0.92rem; display:flex; flex-direction:column; gap:0.75rem; color:rgba(255,255,255,0.8);">
                    <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <i class="fas fa-phone" style="color:var(--primary); width:18px; text-align:center; margin-top:3px;"></i>
                        <span><?php echo htmlspecialchars($footer_phone); ?></span>
                    </div>
                    <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <i class="fas fa-envelope" style="color:var(--primary); width:18px; text-align:center; margin-top:3px;"></i>
                        <span><?php echo htmlspecialchars($footer_email); ?></span>
                    </div>
                    <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <i class="fas fa-location-dot" style="color:var(--primary); width:18px; text-align:center; margin-top:3px;"></i>
                        <span><?php echo htmlspecialchars($footer_address); ?></span>
                    </div>
                </div>
            </div>
            <div>
                <div style="font-weight:700; color:white; margin-bottom:0.25rem; font-size:0.95rem; font-family:'Poppins', sans-serif; text-transform:uppercase; letter-spacing:0.03em;">Location</div>
                <div style="border-radius:var(--radius); overflow:hidden; height:143px; border:1px solid rgba(255,255,255,0.15); box-shadow:0 4px 15px rgba(0,0,0,0.1);">
                    <?php if($google_maps_iframe): ?>
                        <iframe src="<?php echo htmlspecialchars($google_maps_iframe); ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    <?php else: ?>
                        <div style="background:#333; color:white; height:100%; display:flex; align-items:center; justify-content:center; font-size:0.8rem;">Map not configured</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <hr class="footer-divider" style="margin-top:2rem; margin-bottom:2rem; border-color:rgba(255,255,255,0.1);">

        <div class="footer-bottom-grid" style="display:grid; grid-template-columns:1.2fr auto 1.2fr; align-items:center; gap:2rem; font-size:0.88rem;">
            <div style="text-align:left; color:white; font-family:'Poppins', sans-serif; font-weight:700; font-size:0.85rem; letter-spacing:0.03em;">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($brand_name); ?>.
            </div>
            <div style="text-align:center;">
                <a href="https://anjanainfotech.in/" target="_blank" style="color:rgba(255,255,255,0.9); transition:0.3s; font-weight:600; text-decoration:none;">Developed by Anjana Infotech</a>
            </div>
            <div style="text-align:right; display:flex; align-items:center; justify-content:flex-end; gap:1.25rem; margin-right:5%;">
                <span style="font-weight:700; color:white; letter-spacing:0.05em; text-transform:uppercase; font-size:0.78rem; font-family:'Poppins', sans-serif;">Follow Us</span>

                <div style="display:flex; gap:1rem; align-items:center;">
                    <?php if(!empty($social_facebook)): ?>
                        <a href="<?php echo htmlspecialchars($social_facebook); ?>" target="_blank" class="footer-social-link-lg" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                    
                    <?php if(!empty($social_instagram)): ?>
                        <a href="<?php echo htmlspecialchars($social_instagram); ?>" target="_blank" class="footer-social-link-lg" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                    
                    <?php if(!empty($social_youtube)): ?>
                        <a href="<?php echo htmlspecialchars($social_youtube); ?>" target="_blank" class="footer-social-link-lg" title="YouTube"><i class="fab fa-youtube"></i></a>
                    <?php endif; ?>
                    
                    <?php if(!empty($social_whatsapp)): ?>
                        <a href="<?php echo htmlspecialchars($social_whatsapp); ?>" target="_blank" class="footer-social-link-lg" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <style>
            .footer-social-link-lg {
                color: white;
                font-size: 1.25rem;
                transition: 0.3s;
                opacity: 0.9;
            }
            .footer-social-link-lg:hover {
                color: var(--primary);
                transform: scale(1.2);
                opacity: 1;
            }
            @media (max-width: 992px) {
                .footer-bottom-grid {
                    grid-template-columns: 1fr !important;
                    text-align: center !important;
                    gap: 1.25rem;
                }
                .footer-bottom-grid > div {
                    text-align: center !important;
                    justify-content: center !important;
                }
            }
        </style>
    </div>
</footer>



<style>
@media (max-width: 900px) {
    .footer .container > div:first-child {
        grid-template-columns: 1fr 1fr !important;
    }
}
@media (max-width: 576px) {
    .footer .container > div:first-child {
        grid-template-columns: 1fr !important;
    }
}
</style>
<?php include_once 'alerts.php'; ?>
