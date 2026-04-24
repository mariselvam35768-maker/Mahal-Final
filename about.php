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
        body { padding-top: 175px; }
        .team-card { background: white; border-radius: var(--radius-sm); border: 1px solid var(--border); padding: 2.5rem; text-align: center; transition: var(--transition); }
        .team-card:hover { box-shadow: var(--shadow-md); transform: translateY(-5px); border-color: var(--primary); }
        .value-card { padding: 2.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border); background: white; transition: var(--transition); }
        .value-card:hover { box-shadow: var(--shadow-md); border-color: var(--primary); transform: translateY(-5px); }
        .milestone { display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem; background: white; border-radius: var(--radius-sm); border: 1px solid var(--border); }
        .milestone-icon { width: 54px; height: 54px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
        .milestones-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .events-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; }
        .rooms-accommodation-grid {display: grid; grid-template-columns: repeat(3,1fr); gap: 2rem; }
        @media (max-width: 992px) {
            .events-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .milestones-grid { grid-template-columns: 1fr; }
            .events-grid { grid-template-columns: 1fr; }
            .rooms-accommodation-grid { grid-template-columns: 1fr; }
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
            <div class="grid-2" style="align-items:center; gap:4rem;">
                <div>
                    <div class="section-label">Our Legacy</div>
                    <h2 class="section-heading" style="font-family:'Playfair Display', serif;">Crafting Timeless <span>Memories</span></h2>
                    <p style="color:var(--gray);line-height:1.8;margin-bottom:2rem; font-size:1.1rem;">
                        Since our inception, Sri Lakshmi Residency & Mahal has been a symbol of hospitality and elegance in Srivilliputhur. We combine modern luxury with traditional warmth to create a perfect setting for your life's most precious moments.
                    </p>
                    <div style="display:flex;gap:3rem;flex-wrap:wrap; margin-top:3rem;">
                        <div>
                            <div style="font-size:2.5rem;font-weight:700;font-family:'Playfair Display',serif;color:var(--primary); line-height:1;">15+</div>
                            <div style="font-size:0.8rem;color:var(--gray);text-transform:uppercase;letter-spacing:0.1em; margin-top:0.5rem;">Years of Service</div>
                        </div>
                        <div>
                            <div style="font-size:2.5rem;font-weight:700;font-family:'Playfair Display',serif;color:var(--primary); line-height:1;">1000+</div>
                            <div style="font-size:0.8rem;color:var(--gray);text-transform:uppercase;letter-spacing:0.1em; margin-top:0.5rem;">Events Hosted</div>
                        </div>
                    </div>
                </div>
                <div class="milestones-grid">
                    <?php
                    $milestones = [
                        ['fas fa-bed',       'var(--primary)','var(--primary-light)','43 Air-Conditioned Rooms',    'With complimentary breakfast for all guests'],
                        ['fas fa-utensils',   'var(--primary)','var(--primary-light)','Fully AC Dining Hall',         'Centrally air-conditioned and spacious dining area'],
                        ['fas fa-users',      'var(--primary)','var(--primary-light)','Mahal for 300 Guests',         'Fully air-conditioned Mahal for grand celebrations'],
                        ['fas fa-parking',    'var(--primary)','var(--primary-light)','Spacious Parking Facility',    'Ample parking for guests and visitors'],
                        ['fas fa-wifi',       'var(--primary)','var(--primary-light)','Free Wi-Fi',                   'High-speed internet throughout the property'],
                        ['fas fa-tint',       'var(--primary)','var(--primary-light)','24/7 Water Supply',            'Uninterrupted water supply round the clock'],
                        ['fas fa-clock',      'var(--primary)','var(--primary-light)','Complimentary Breakfast',    'Available daily from 9:00 AM to 11:00 AM'],
                        ['fas fa-hotel',      'var(--primary)','var(--primary-light)','Mini Hall for 100 Guests',    'Perfect for intimate gatherings and small functions'],
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
            <div class="text-center" style="margin-bottom:4rem;">
                <div class="section-label">Our Philosophy</div>
                <h2 class="section-heading" style="font-family:'Playfair Display', serif;">Our Core <span>Values</span></h2>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:2rem;">
                <?php
                $values = [
                    ['fas fa-gem','Excellence','We strive for perfection in every detail, ensuring your event is nothing short of extraordinary.'],
                    ['fas fa-hand-holding-heart','Hospitality','Warm, personalized service is at the heart of our residency, making every guest feel at home.'],
                    ['fas fa-shield-check','Integrity','Transparent pricing and honest communication are the foundations of our relationship with you.'],
                    ['fas fa-sparkles','Cleanliness','We maintain the highest standards of hygiene and maintenance across all our premises.'],
                ];
                foreach ($values as [$icon,$title,$desc]): ?>
                    <div class="value-card">
                        <div style="width:60px;height:60px;border-radius:var(--radius-sm);background:var(--primary-light);display:flex;align-items:center;justify-content:center;margin-bottom:1.5rem;">
                            <i class="<?php echo $icon; ?>" style="color:var(--primary);font-size:1.5rem;"></i>
                        </div>
                        <h4 style="margin-bottom:1rem; font-family:'Playfair Display', serif; font-size:1.2rem;"><?php echo $title; ?></h4>
                        <p style="color:var(--gray);font-size:0.95rem;line-height:1.7;"><?php echo $desc; ?></p>
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
                    <div style="width:56px;height:56px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="fas fa-heart" style="color:var(--primary);font-size:1.3rem;"></i>
                    </div>
                    <h4 style="font-size:0.95rem;margin:0;">Weddings &amp; Receptions</h4>
                </div>

                <div style="text-align:center;padding:2rem 1.5rem;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc'">
                    <div style="width:56px;height:56px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="fas fa-ring" style="color:var(--primary);font-size:1.3rem;"></i>
                    </div>
                    <h4 style="font-size:0.95rem;margin:0;">Engagements</h4>
                </div>

                <div style="text-align:center;padding:2rem 1.5rem;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc'">
                    <div style="width:56px;height:56px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="fas fa-birthday-cake" style="color:var(--primary);font-size:1.3rem;"></i>
                    </div>
                    <h4 style="font-size:0.95rem;margin:0;">Birthday &amp; Family Functions</h4>
                </div>

                <div style="text-align:center;padding:2rem 1.5rem;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc'">
                    <div style="width:56px;height:56px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="fas fa-briefcase" style="color:var(--primary);font-size:1.3rem;"></i>
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
                    <div style="width:60px;height:60px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                        <i class="fas fa-bed" style="color:var(--primary);font-size:1.4rem;"></i>
                    </div>
                    <h4 style="margin-bottom:0.5rem;font-size:1rem;">Deluxe Room – AC</h4>
                    <p style="color:var(--gray);font-size:0.85rem;line-height:1.7;margin:0;">Comfortable stay with essential amenities</p>
                </div>

                <div style="text-align:center;padding:2.5rem 2rem;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc'">
                    <div style="width:60px;height:60px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                        <i class="fas fa-star" style="color:var(--primary);font-size:1.4rem;"></i>
                    </div>
                    <h4 style="margin-bottom:0.5rem;font-size:1rem;">Super Deluxe Room – AC</h4>
                    <p style="color:var(--gray);font-size:0.85rem;line-height:1.7;margin:0;">Enhanced comfort with premium interiors</p>
                </div>

                <div style="text-align:center;padding:2.5rem 2rem;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc'">
                    <div style="width:60px;height:60px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                        <i class="fas fa-crown" style="color:var(--primary);font-size:1.4rem;"></i>
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
            <div class="cta-banner" style="background:var(--primary-deep);border-radius:var(--radius-md);padding:6rem 3rem;text-align:center;position:relative;overflow:hidden;">
                <div style="position:relative;z-index:1;">
                    <h2 style="color:white;font-size:2.5rem;margin-bottom:1rem; font-family:'Playfair Display', serif;">Join Our Journey</h2>
                    <p style="color:rgba(255,255,255,0.6);margin-bottom:3rem;font-size:1.1rem; max-width:600px; margin-left:auto; margin-right:auto;">Experience the perfect blend of tradition and modern luxury for your next event.</p>
                    <div style="display:flex;gap:1.5rem;justify-content:center;flex-wrap:wrap;">
                        <a href="halls.php" class="btn btn-primary btn-lg">Browse Venues</a>
                        <a href="contact.php" class="btn btn-outline btn-lg" style="border-color:white; color:white !important; background:transparent;">Talk to Us</a>
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
