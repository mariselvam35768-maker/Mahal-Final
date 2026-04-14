<?php
require_once 'includes/auth_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | <?php echo $brand_name; ?></title>
    <meta name="description" content="Learn about Sri Lakshmi Residency & Mahal | Tamil Nadu's trusted online hall booking platform.">
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { padding-top: 75px; }
        .team-card { background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 2rem; text-align: center; transition: var(--transition); }
        .team-card:hover { box-shadow: var(--shadow-md); transform: translateY(-4px); }
        .team-avatar { width: 80px; height: 80px; border-radius: 50%; background: var(--gradient-primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem; color: white; font-family:'Poppins',sans-serif; font-weight: 800; }
        .value-card { padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border); background: white; transition: var(--transition); }
        .value-card:hover { box-shadow: var(--shadow-md); border-color: var(--primary); }
        .milestone { display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem; background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); }
        .milestone-icon { width: 54px; height: 54px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
        .milestones-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .events-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; }
        .rooms-accommodation-grid {display: grid; grid-template-columns: repeat(3,1fr); gap: 2rem; }
        @media (max-width: 768px) {
            .milestones-grid { grid-template-columns: 1fr; }
            .events-grid { grid-template-columns: 1fr; }
            .rooms-accommodation-grid { grid-template-columns: 1fr; }
            .cta-banner { padding: 2.5rem 1.5rem !important; }
            .cta-banner h2 { font-size: 1.3rem !important; }
            .cta-banner p { font-size: 0.875rem !important; }
            .cta-banner .btn-lg { padding: 0.65rem 1.25rem !important; font-size: 0.82rem !important; }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- HERO -->
    <!-- <div class="page-header" style="text-align:center;">
        <div class="container" style="position:relative;z-index:1;">
            <div class="section-label" style="display:inline-flex;margin-bottom:1rem;"><i class="fas fa-building-columns"></i> Our Story</div>
            <h1 style="color:white;font-size:3rem;">About <span style="color:#a78bfa;"><?php echo $brand_name; ?></span></h1>
            <p style="color:rgba(255,255,255,0.75);max-width:560px;margin:0 auto;font-size:1.05rem;">Tamil Nadu's most trusted online platform for booking premium marriage halls and event venues.</p>
        </div>
    </div> -->


    <!-- WHO WE ARE -->
    <section class="section" style="background:white;">
        <div class="container">
            <div class="grid-2" style="align-items:center;">
                <div>
                    <div class="section-label"><i class="fas fa-info-circle"></i> Who We Are</div>
                    <h2 class="section-heading">Where Comfort Meets <span>Celebration</span></h2>
                    <p style="color:var(--gray);line-height:1.8;margin-bottom:1.5rem;">
                        We take pride in offering well-maintained air-conditioned rooms along with a fully air-conditioned Mahal suitable for all kinds of functions. Our focus is on cleanliness, comfort, and customer satisfaction.
                    </p>
                    <div style="margin: 2rem 0; text-align: center;">
                        <img src="assets/images/wedding_illust.svg" alt="Wedding Celebration" style="width: 100%; max-width: 400px; filter: drop-shadow(0 15px 30px rgba(0,0,0,0.05));">
                    </div>
                    <div style="display:flex;gap:2rem;flex-wrap:wrap;">
                        <?php foreach ([['50+','Premium Halls'],['500+','Happy Events'],['100%','Verified Venues'],['24/7','Support']] as [$n,$l]): ?>
                            <div>
                                <div style="font-size:1.75rem;font-weight:800;font-family:'Poppins',sans-serif;color:var(--primary);"><?php echo $n; ?></div>
                                <div style="font-size:0.78rem;color:var(--gray);text-transform:uppercase;letter-spacing:0.05em;"><?php echo $l; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="milestones-grid">
                    <?php
                    $milestones = [
                        ['fas fa-bed',       '#e91e63','#ede9fe','43 Air-Conditioned Rooms',    'With complimentary breakfast for all guests'],
                        ['fas fa-utensils',   '#e91e63','#ede9fe','Fully AC Dining Hall',         'Centrally air-conditioned and spacious dining area'],
                        ['fas fa-users',      '#e91e63','#ede9fe','Mahal for 300 Guests',         'Fully air-conditioned Mahal for grand celebrations'],
                        ['fas fa-parking',    '#e91e63','#ede9fe','Spacious Parking Facility',    'Ample parking for guests and visitors'],
                        ['fas fa-wifi',       '#e91e63','#ede9fe','Free Wi-Fi',                   'High-speed internet throughout the property'],
                        ['fas fa-tint',       '#e91e63','#ede9fe','24/7 Water Supply',            'Uninterrupted water supply round the clock'],
                        ['fas fa-clock',      '#e91e63','#ede9fe','Complimentary Breakfast',    'Available daily from 9:00 AM to 11:00 AM'],
                        ['fas fa-hotel',      '#e91e63','#ede9fe','Mini Hall for 100 Guests',    'Perfect for intimate gatherings and small functions'],
                    ];

                    foreach ($milestones as [$icon,$col,$baccol,$title,$desc]): ?>
                        <div style="background:#f8fafc;border-radius:var(--radius-lg);padding:1.5rem;border:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white';this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc';this.style.boxShadow='none'">
                            <div style="width:46px;height:46px;border-radius:var(--radius);background:var(--primary-light);display:flex;align-items:center;justify-content:center;margin-bottom:0.75rem;">
                                <i class="<?php echo $icon; ?>" style="color:var(--primary);font-size:1.1rem;"></i>
                            </div>
                            <div style="font-weight:700;font-size:0.9rem;margin-bottom:0.3rem;"><?php echo $title; ?></div>
                            <div style="font-size:0.78rem;color:var(--gray);line-height:1.5;"><?php echo $desc; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- OUR VALUES -->
    <section class="section">
        <div class="container">
            <div class="text-center" style="margin-bottom:3rem;">
                <div class="section-label"><i class="fas fa-heart"></i> What We Stand For</div>
                <h2 class="section-heading">Our Core <span>Values</span></h2>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1.5rem;">
                <?php
                $values = [
                    ['fas fa-star','#e91e63','#ede9fe','Clean & Professional','We prioritize cleanliness, comfort, and professional management for all our guests.'],
                    ['fas fa-location-arrow','#e91e63','#ede9fe','Prime Location','Located in the heart of Srivilliputhur, making us the ideal choice for stays and events.'],
                    ['fas fa-handshake','#e91e63','#ede9fe','Customer First','Friendly service and warm hospitality are at the core of everything we do.'],
                    ['fas fa-shield-alt','#e91e63','#ede9fe','Aesthetically Built','Modern architecture with premium interiors designed for elegance and convenience.'],
                ];
                foreach ($values as [$icon,$col,$baccol,$title,$desc]): ?>
                    <div class="value-card">
                        <div style="width:56px;height:56px;border-radius:var(--radius);background:var(--primary-light);display:flex;align-items:center;justify-content:center;margin-bottom:1.25rem;">
                            <i class="<?php echo $icon; ?>" style="color:var(--primary);font-size:1.3rem;"></i>
                        </div>
                        <h4 style="margin-bottom:0.6rem;"><?php echo $title; ?></h4>
                        <p style="color:var(--gray);font-size:0.875rem;line-height:1.7;"><?php echo $desc; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- WHAT WE OFFER -->
    <section class="section" style="background:white;">
        <div class="container">
            <div class="text-center" style="margin-bottom:3rem;">
                <div class="section-label"><i class="fas fa-star"></i> Our Services</div>
                <h2 class="section-heading">Everything Under <span>One Roof</span></h2>
            </div>

            <!-- 4 Event Service Divs -->
            <div class="events-grid">

                <div style="text-align:center;padding:2rem 1.5rem;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc'">
                    <div style="width:56px;height:56px;border-radius:50%;background:#fce7f3;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="fas fa-heart" style="color:#e91e63;font-size:1.3rem;"></i>
                    </div>
                    <h4 style="font-size:0.95rem;margin:0;">Weddings &amp; Receptions</h4>
                </div>

                <div style="text-align:center;padding:2rem 1.5rem;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc'">
                    <div style="width:56px;height:56px;border-radius:50%;background:#fce7f3;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="fas fa-ring" style="color:#e91e63;font-size:1.3rem;"></i>
                    </div>
                    <h4 style="font-size:0.95rem;margin:0;">Engagements</h4>
                </div>

                <div style="text-align:center;padding:2rem 1.5rem;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc'">
                    <div style="width:56px;height:56px;border-radius:50%;background:#fce7f3;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="fas fa-birthday-cake" style="color:#e91e63;font-size:1.3rem;"></i>
                    </div>
                    <h4 style="font-size:0.95rem;margin:0;">Birthday &amp; Family Functions</h4>
                </div>

                <div style="text-align:center;padding:2rem 1.5rem;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc'">
                    <div style="width:56px;height:56px;border-radius:50%;background:#fce7f3;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="fas fa-briefcase" style="color:#e91e63;font-size:1.3rem;"></i>
                    </div>
                    <h4 style="font-size:0.95rem;margin:0;">Corporate Meetings</h4>
                </div>

            </div>

            <!-- Rooms & Accommodation -->
            <div class="text-center" style="margin-top:10rem;margin-bottom:2rem;">
                <h3 style="font-size:1.4rem;font-weight:800;margin-bottom:0.75rem;">Rooms &amp; Accommodation</h3>
                <p style="color:var(--gray);font-size:0.95rem;max-width:560px;margin:0 auto;line-height:1.8;">
                    All rooms are fully air-conditioned, clean, and designed for maximum comfort. Complimentary breakfast is included with every stay.
                </p>
            </div>
            <div class="rooms-accommodation-grid">

                <div style="text-align:center;padding:2.5rem 2rem;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc'">
                    <div style="width:60px;height:60px;border-radius:50%;background:#ede9fe;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                        <i class="fas fa-bed" style="color:#e91e63;font-size:1.4rem;"></i>
                    </div>
                    <h4 style="margin-bottom:0.5rem;font-size:1rem;">Deluxe Room – AC</h4>
                    <p style="color:var(--gray);font-size:0.85rem;line-height:1.7;margin:0;">Comfortable stay with essential amenities</p>
                </div>

                <div style="text-align:center;padding:2.5rem 2rem;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc'">
                    <div style="width:60px;height:60px;border-radius:50%;background:#ede9fe;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                        <i class="fas fa-star" style="color:#e91e63;font-size:1.4rem;"></i>
                    </div>
                    <h4 style="margin-bottom:0.5rem;font-size:1rem;">Super Deluxe Room – AC</h4>
                    <p style="color:var(--gray);font-size:0.85rem;line-height:1.7;margin:0;">Enhanced comfort with premium interiors</p>
                </div>

                <div style="text-align:center;padding:2.5rem 2rem;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc'">
                    <div style="width:60px;height:60px;border-radius:50%;background:#ede9fe;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                        <i class="fas fa-crown" style="color:#e91e63;font-size:1.4rem;"></i>
                    </div>
                    <h4 style="margin-bottom:0.5rem;font-size:1rem;">VIP Room – AC</h4>
                    <p style="color:var(--gray);font-size:0.85rem;line-height:1.7;margin:0;">Spacious luxury room for a premium stay</p>
                </div>

            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="section">
        <div class="container">
            <div class="cta-banner" style="background:var(--gradient-primary);border-radius:var(--radius-xl);padding:4rem 3rem;text-align:center;position:relative;overflow:hidden;">
                <div style="position:relative;z-index:1;">
                    <h2 style="color:white;font-size:2.25rem;margin-bottom:0.75rem;">Ready to Book Your Venue?</h2>
                    <p style="color:rgba(255,255,255,0.8);margin-bottom:2rem;font-size:1.05rem;">Browse our collection of verified halls and make your event unforgettable.</p>
                    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                        <a href="halls.php" class="btn btn-white btn-lg"><i class="fas fa-building"></i> Browse Halls</a>
                        <a href="contact.php" class="btn btn-lg" style="background:rgba(255,255,255,0.15);color:white;border:2px solid rgba(255,255,255,0.3);"><i class="fas fa-phone"></i> Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <?php include 'includes/footer.php'; ?>

    <?php include 'includes/modals.php'; ?>
    <?php include 'includes/chatbot.php'; ?>
</body>
</html>
