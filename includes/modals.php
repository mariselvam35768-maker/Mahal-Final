<!-- About Modal -->
<div id="aboutModal" class="modal-overlay" onclick="if(event.target===this)closeModal('aboutModal')">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('aboutModal')"><i class="fas fa-times"></i></button>
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
            <div style="width:52px;height:52px;background:var(--gradient-primary);border-radius:var(--radius);display:flex;align-items:center;justify-content:center;color:white;font-size:1.4rem;flex-shrink:0;">
                <i class="fas fa-building-columns"></i>
            </div>
            <div>
                <h3 style="margin:0;">About Sri Lakshmi Residency & Mahal</h3>
                <p style="color:var(--gray);font-size:0.8rem;margin:0;">Tamil Nadu's Premier Hall Booking Platform</p>
            </div>
        </div>
        <p style="color:var(--gray);line-height:1.7;margin-bottom:1.25rem;">Sri Lakshmi Residency & Mahal is a trusted online platform for booking premium marriage halls, party venues, and event spaces across Tamil Nadu. We make the booking process simple, transparent, and reliable.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
            <?php foreach ([
                ['fas fa-calendar-check','#7c3aed','Real-Time Availability'],
                ['fas fa-shield-alt','#10b981','100% Verified Halls'],
                ['fas fa-rupee-sign','#f59e0b','Transparent Pricing'],
                ['fas fa-headset','#3b82f6','24/7 Support'],
            ] as [$ic,$col,$lbl]): ?>
                <div style="display:flex;align-items:center;gap:0.6rem;font-size:0.82rem;color:var(--dark-2);">
                    <i class="<?php echo $ic; ?>" style="color:<?php echo $col; ?>;width:16px;text-align:center;"></i> <?php echo $lbl; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--border);text-align:center;">
            <a href="halls.php" class="btn btn-primary" onclick="closeModal('aboutModal')">Browse Halls</a>
        </div>
    </div>
</div>

<!-- Contact Modal -->
<div id="contactModal" class="modal-overlay" onclick="if(event.target===this)closeModal('contactModal')">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('contactModal')"><i class="fas fa-times"></i></button>
        <h3 style="margin-bottom:0.5rem;">Contact Us</h3>
        <p style="color:var(--gray);font-size:0.875rem;margin-bottom:1.75rem;">Get in touch with our support team.</p>

        <div style="display:flex;flex-direction:column;gap:1rem;">
            <?php foreach ([
                ['fas fa-phone','#7c3aed','Call Us','+91 98765 43210','Available Mon–Sat, 9am–6pm'],
                ['fas fa-envelope','#ec4899','Email Us','support@srilakshmimahal.com','We reply within 24 hours'],
                ['fas fa-map-marker-alt','#f59e0b','Visit Us','Tamil Nadu, India','Offices across major cities'],
            ] as [$ic,$col,$lbl,$val,$note]): ?>
                <div style="display:flex;align-items:center;gap:1rem;padding:1rem;background:#f8fafc;border-radius:var(--radius);border:1px solid var(--border);">
                    <div style="width:44px;height:44px;background:<?php echo $col; ?>15;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="<?php echo $ic; ?>" style="color:<?php echo $col; ?>;"></i>
                    </div>
                    <div>
                        <div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--gray-light);font-weight:600;"><?php echo $lbl; ?></div>
                        <div style="font-weight:700;font-size:0.95rem;color:var(--dark);"><?php echo $val; ?></div>
                        <div style="font-size:0.75rem;color:var(--gray);"><?php echo $note; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => {
            m.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

// Navbar scroll
window.addEventListener('scroll', () => {
    const nav = document.getElementById('navbar');
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 50);
});
</script>
<?php include 'includes/alerts.php'; ?>
