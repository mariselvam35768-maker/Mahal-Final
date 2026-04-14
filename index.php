<?php
require_once 'includes/auth_functions.php';

// Fetch featured halls for homepage showcase
$featured_halls = [];
try {
    $featured_stmt = $pdo->query("SELECT * FROM halls ORDER BY created_at DESC LIMIT 6");
    $featured_halls = $featured_stmt->fetchAll();
} catch (Exception $e) {}

// Stats
$total_halls_count = 0;
$total_bookings_count = 0;
try {
    $total_halls_count = $pdo->query("SELECT COUNT(*) FROM halls")->fetchColumn();
    $total_bookings_count = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn();
    
    // Fetch Banner
    $banner_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'home_banner'");
    $banner_stmt->execute();
    $home_banner_raw = $banner_stmt->fetchColumn() ?: 'hall image1.webp';

    $home_banner_items = [];
    $decoded_banner = json_decode($home_banner_raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_banner)) {
        $home_banner_items = $decoded_banner;
    } elseif (!empty($home_banner_raw)) {
        $home_banner_items = [$home_banner_raw];
    }
    if (empty($home_banner_items)) {
        $home_banner_items = ['hall image1.webp'];
    }

    $home_banner_paths = array_map(function ($item) {
        if (strpos($item, 'hall image') !== false) {
            return $item;
        }
        return 'assets/images/banners/' . $item;
    }, $home_banner_items);

    // Calculate slider animation timing
    $image_count = count($home_banner_paths);
    $display_time = 3; // seconds per image
    $transition_time = 0.2; // seconds for quick slide transition (reduced for faster movement)
    $total_duration = ($image_count * $display_time) + ($image_count * $transition_time);
    $display_percentage = ($display_time / $total_duration) * 100;
    $transition_percentage = ($transition_time / $total_duration) * 100;
    $slide_percentage = 100 / $image_count;

    // Fetch Gallery Preview (Room images only)
    $gallery_stmt = $pdo->query("SELECT * FROM gallery WHERE category = 'room' ORDER BY created_at DESC LIMIT 8");
    $gallery_preview = $gallery_stmt->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $brand_name; ?> | Find & Book Your Dream Venue</title>
    <meta name="description" content="Book premium marriage halls and event venues online. Simple, fast, and reliable hall booking system.">
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { padding-top: 0; }
        .hero { min-height: 100vh; display: flex; align-items: center; position: relative; overflow: hidden; }
        .hero-content { position: relative; z-index: 3; padding: 15vh 5% 0; width: 100%; }
        .hero-tag { display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: rgba(255,255,255,0.9); padding: 0.4rem 1rem; border-radius: var(--radius-full); font-size: 0.8rem; font-weight: 600; margin-bottom: 1.5rem; backdrop-filter: blur(4px); }
        .hero h1 { font-size: clamp(2.5rem, 5vw, 4.5rem); color: white; font-weight: 900; line-height: 1.05; letter-spacing: -0.03em; margin-bottom: 1.5rem; }
        .hero h1 span { background: linear-gradient(120deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero p { font-size: 1.15rem; color: rgba(255,255,255,0.75); max-width: 520px; margin-bottom: 2.5rem; line-height: 1.7; animation: fadeInUp 0.8s ease backwards; animation-delay: 0.2s; }
        .hero-ctas { display: flex; gap: 1rem; flex-wrap: wrap; }
        .hero-stats { display: flex; gap: 3rem; margin-top: 4rem; padding-top: 3rem; border-top: 1px solid rgba(255,255,255,0.1); flex-wrap: wrap; }
        .hero-stat-value { font-family: 'Poppins', sans-serif; font-size: 2rem; font-weight: 800; color: white; }
        .hero-stat-label { color: rgba(255,255,255,0.5); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; }

        /* Hero Background Slider */
        .hero-slider { position: absolute; inset: 0; z-index: 1; }
        .hero-slides { 
            display: flex; 
            width: <?php echo $image_count * 100; ?>%; 
            animation: slideShow <?php echo $total_duration; ?>s infinite; 
        }
        .hero-slide { 
            flex: 0 0 <?php echo $slide_percentage; ?>%; 
            height: 100%; 
        }
        .hero-slide img { width: 100%; height: 100%; object-fit: cover; opacity: 0.35; }
        @keyframes slideShow {
            <?php
            $keyframes = '';
            $current_time = 0;
            
            for ($i = 0; $i < $image_count; $i++) {
                $start_pos = $i * $slide_percentage;
                
                // Display period
                $keyframes .= $current_time . '% { transform: translateX(-' . $start_pos . '%); }' . "\n            ";
                $current_time += $display_percentage;
                
                // Transition period (if not the last image)
                if ($i < $image_count - 1) {
                    $next_pos = ($i + 1) * $slide_percentage;
                    $keyframes .= $current_time . '% { transform: translateX(-' . $next_pos . '%); }' . "\n            ";
                    $current_time += $transition_percentage;
                }
            }
            
            // Loop back to first image
            $keyframes .= '100% { transform: translateX(0); }';
            echo $keyframes;
            ?>
        }

        @media(max-width:992px) {
            .hero { min-height: 80vh; padding-top: 80px; }
            .hero-content { padding: 4rem 1.5rem; text-align: center; flex-direction: column; }
            .hero-illust-area { order: -1; margin-bottom: 2rem; }
            .hero-illust-area img { max-width: 300px !important; }
            .hero h1 { font-size: 3rem; margin: 0 auto 1.5rem; }
            .hero p { margin-left: auto; margin-right: auto; }
            .hero-ctas { justify-content: center; }
            .hero-stats { justify-content: center; gap: 2rem; margin-top: 3rem; }
        }
        @media(max-width:576px) {
            .hero h1 { font-size: 2.25rem; }
            .hero-stats { gap: 1.5rem; }
            .hero-stat-value { font-size: 1.5rem; }
        }

        .banner-slider-wrapper { position: relative; width: 100%; overflow: hidden; margin-top: 2rem; margin-bottom: 3rem; }
        .banner-slider { position: relative; width: 100%; height: 70vh; min-height: 400px; overflow: hidden; }
        .banner-slide { position: absolute; inset: 0; opacity: 0; transition: opacity 0.7s ease; }
        .banner-slide.active { opacity: 1; }
        .banner-slide img { width: 100%; height: 100%; object-fit: cover; }
        .slider-arrow { position: absolute; top: 50%; transform: translateY(-50%); width: 2.2rem; height: 2.2rem; background: rgba(0,0,0,0.5); border: 0; color: white; border-radius: 999px; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 5; }
        .slider-prev { left: 1rem; }
        .slider-next { right: 1rem; }
        @media (max-width: 768px) { .banner-slider { height: 50vh; min-height: 300px; } .slider-arrow { width: 1.8rem; height: 1.8rem; font-size: 0.8rem; left: 0.5rem; right: 0.5rem; } }

        /* Features */
        .features-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.5rem; }

        /* Specialties */
        .specialty-card { display: flex; align-items: flex-start; gap: 1.25rem; padding: 1.5rem; background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); transition: var(--transition); }
        .specialty-card:hover { box-shadow: var(--shadow-md); border-color: var(--primary); }
        .specialty-card .s-icon { width: 50px; height: 50px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }

        /* Services 3-col grid — responsive */
        .services-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; }
        @media (max-width: 768px) {
            .services-grid { grid-template-columns: 1fr; }
        }

        /* How it works */
        .step-connector { width: 1px; height: 40px; background: var(--border); margin: 0 auto; }

        /* CTA Banner */
        .cta-banner { background: var(--gradient-primary); border-radius: var(--radius-xl); padding: 4rem 3rem; color: white; text-align: center; position: relative; overflow: hidden; }
        .cta-banner::before { content: ''; position: absolute; top: -50%; left: -20%; width: 300px; height: 300px; background: rgba(255,255,255,0.05); border-radius: 50%; }
        .cta-banner h2 { color: white; font-size: 2.25rem; margin-bottom: 1rem; }
        .cta-banner p { color: rgba(255,255,255,0.8); margin-bottom: 2rem; font-size: 1.05rem; }
    </style>
</head>
<body>
    <!-- SHARED NAVBAR -->
    <?php include 'includes/navbar.php'; ?>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-orb hero-orb-1"></div>
        <div class="hero-orb hero-orb-2"></div>

        <!-- Hero Background Slider -->
        <div class="hero-slider">
            <div class="hero-slides">
                <?php foreach ($home_banner_paths as $banner_path): ?>
                    <div class="hero-slide">
                        <img src="<?php echo htmlspecialchars($banner_path); ?>" alt="Beautiful Hall">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="hero-content container reveal">
            <h1>Find & Book Your <span>Perfect Event</span> Venue</h1>
            <p>Sri Lakshmi Residency & Mahal - Where Comfort Meets Celebration. Experience elegance, convenience, and warm hospitality in the heart of Srivilliputhur.</p>
            <div class="hero-ctas">
                <a href="halls.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search"></i> Find Your Hall
                </a>
                <a href="#how-it-works" class="btn btn-white btn-lg">
                    <i class="fas fa-heart"></i> Why Us
                </a>
            </div>

            <div class="hero-stats glass-card stagger-children" style="margin-top: 4rem; padding: 2rem;">
                <div>
                    <div class="hero-stat-value"><?php echo $total_halls_count; ?>+</div>
                    <div class="hero-stat-label">Premium Halls</div>
                </div>
                <div>
                    <div class="hero-stat-value"><?php echo $total_bookings_count; ?>+</div>
                    <div class="hero-stat-label">Events Hosted</div>
                </div>
                <div>
                    <div class="hero-stat-value">100%</div>
                    <div class="hero-stat-label">Verified Venues</div>
                </div>
                <div>
                    <div class="hero-stat-value">24/7</div>
                    <div class="hero-stat-label">Customer Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- WHY CHOOSE US -->
    <section class="section" id="why-us" style="background:#f8fafc;">
        <div class="container">
            <div class="grid-2" style="align-items:center;">
                <div class="reveal">
                    <div class="section-label"><i class="fas fa-check-circle"></i> Why Choose Us</div>
                    <h2 class="section-heading">Premium Features for <span>Your Special Day</span></h2>
                    <ul style="list-style:none;padding:0;margin:1.5rem 0 0;display:flex;flex-direction:column;gap:1rem;">
                        <?php foreach ([
                            ['fa-map-marker-alt', 'Prime location in Srivilliputhur'],
                            ['fa-snowflake',      'Clean, Premium Air-Conditioned Facilities'],
                            ['fa-glass-cheers',   'Ideal For Stay as Well as Celebrations'],
                            ['fa-hands-helping',  'Friendly Service and Professional Management'],
                        ] as [$icon, $text]): ?>
                            <li style="display:flex;align-items:center;gap:1rem;">
                                <div style="width:40px;height:40px;background:var(--primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--primary);flex-shrink:0;">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <span style="font-size:0.95rem;font-weight:600;color:var(--dark-2);"><?php echo $text; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div style="text-align:center;" class="reveal">
                    <img src="assets/images/wedding_illust.svg" alt="Why Choose Us" style="width:100%;max-width:450px;filter:drop-shadow(0 20px 30px rgba(0,0,0,0.05));">
                </div>
            </div>
        </div>
    </section>

    <!-- WELCOME -->
    <section class="section" style="background:white;padding:1.5rem 0;">
        <div class="container">
            <div class="text-center reveal" style="max-width:720px;margin:0 auto;">
                <div class="section-label">WELCOME !</div>
                <p style="color:var(--gray);line-height:1.8;font-size:1.05rem;font-weight:500;margin-top:1rem;">
                    Sri Lakshmi Residency &amp; Mahal Offers a Premium Experience For Both Comfortable Stays and Grand Celebrations. Located in the Heart of Srivilliputhur, our Property is Designed to Provide Elegance, Convenience, and Warm Hospitality For Gamilies, Guests, and Event Hosts.
                </p>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="section" id="how-it-works" style="background:white;">
        <div class="container">
            <div class="text-center reveal" style="margin-bottom:4rem;">
                <div class="section-label"><i class="fas fa-route"></i> Simple Process</div>
                <h2 class="section-heading">Book in <span>3 Easy Steps</span></h2>
                <p class="section-sub" style="margin:0 auto;">From search to confirmation in just minutes. No hidden fees, no complications.</p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:2rem;position:relative;">
                <?php
                $steps = [
                    ['icon' => 'fas fa-search', 'step' => '01', 'title' => 'Browse & Filter', 'desc' => 'Search Halls by Location, Capacity, and Date. View Detailed Photos and Amenities.', 'color' => 'var(--primary)'],
                    ['icon' => 'fas fa-calendar-check', 'step' => '02', 'title' => 'Pick Your Slot', 'desc' => 'Choose Your Event Date and Time Slot   Full Day, Morning, or Evening Sessions.', 'color' => 'var(--secondary)'],
                    ['icon' => 'fas fa-check-circle', 'step' => '03', 'title' => 'Confirm & Celebrate', 'desc' => 'Submit Booking with Advance Payment. Get Instant Confirmation and Enjoy Your Event!', 'color' => 'var(--secondary)'],
                ];
                foreach ($steps as $s): ?>
                    <div class="feature-card text-center">
                        <div style="width:70px;height:70px;border-radius:50%;background:<?php echo $s['color']; ?>15;border:2px solid <?php echo $s['color']; ?>30;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                            <i class="<?php echo $s['icon']; ?>" style="font-size:1.6rem;color:<?php echo $s['color']; ?>;"></i>
                        </div>
                        <div style="font-size:0.7rem;font-weight:800;color:<?php echo $s['color']; ?>;letter-spacing:0.1em;margin-bottom:0.5rem;">STEP <?php echo $s['step']; ?></div>
                        <h4 style="margin-bottom:0.5rem;"><?php echo $s['title']; ?></h4>
                        <p style="color:var(--gray);font-size:0.875rem;line-height:1.6;"><?php echo $s['desc']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FEATURED HALLS -->
    <?php if (!empty($featured_halls)): ?>
    <section class="section" id="halls">
        <div class="container">
            <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:3rem;flex-wrap:wrap;gap:1rem;">
                <div class="reveal">
                    <div class="section-label"><i class="fas fa-building"></i> Our Collection</div>
                    <h2 class="section-heading">Featured <span>Halls & Venues</span></h2>
                </div>
                <?php if(count($featured_halls) > 3): ?>
                    <a href="halls.php" class="btn btn-outline reveal">View All Halls <i class="fas fa-arrow-right"></i></a>
                <?php endif; ?>
            </div>
            <div class="halls-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem;">
                <?php foreach ($featured_halls as $hall): ?>
                    <div class="hall-card animate-fade-in">
                        <div class="hall-card-img">
                            <?php if ($hall['main_image']): ?>
                                <img src="assets/images/halls/<?php echo htmlspecialchars($hall['main_image']); ?>" alt="<?php echo htmlspecialchars($hall['name']); ?>">
                            <?php else: ?>
                                <div style="width:100%;height:100%;background:var(--gradient-hero);display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-building-columns" style="font-size:3rem;color:rgba(255,255,255,0.3);"></i>
                                </div>
                            <?php endif; ?>
                            <div class="hall-price">Rs. <?php echo number_format($hall['price_per_day']); ?>/day</div>
                            <div class="hall-badge">
                                <span class="badge badge-success"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Available</span>
                            </div>
                        </div>
                        <div class="hall-card-body">
                            <h3 class="hall-card-title"><?php echo htmlspecialchars($hall['name']); ?></h3>
                            <div class="hall-card-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hall['location']); ?></span>
                                <span><i class="fas fa-users"></i> <?php echo number_format($hall['capacity']); ?> Guests</span>
                            </div>
                            <a href="halls.php?id=<?php echo $hall['id']; ?>" class="btn btn-primary" style="width:100%;justify-content:center;">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- SPECIALTIES / FEATURES -->
    <section class="section" id="features" style="background:white;">
        <div class="container">
            <div class="text-center reveal" style="margin-bottom:4rem;">
                <div class="section-label"><i class="fas fa-sparkles"></i> Why Sri Lakshmi Residency &amp; Mahal</div>
                <h2 class="section-heading">Our <span>Services &amp; Facilities</span></h2>
            </div>
            <div class="services-grid">

                <!-- Card 1: Mahal & Event Services -->
                <div class="specialty-card reveal" style="flex-direction:column;align-items:flex-start;padding:2.5rem 2rem;min-height:320px;">
                    <div class="s-icon" style="background:#ede9fe;color:#e91e63;margin-bottom:1.25rem;">
                        <i class="fas fa-building-columns"></i>
                    </div>
                    <h4 style="margin-bottom:1rem;font-size:1.05rem;">Mahal &amp; Event Services</h4>
                    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.7rem;">
                        <li style="color:var(--gray);font-size:0.88rem;"><i class="fas fa-check-circle" style="color:#e91e63;margin-right:0.5rem;"></i>Weddings &amp; Receptions</li>
                        <li style="color:var(--gray);font-size:0.88rem;"><i class="fas fa-check-circle" style="color:#e91e63;margin-right:0.5rem;"></i>Engagements</li>
                        <li style="color:var(--gray);font-size:0.88rem;"><i class="fas fa-check-circle" style="color:#e91e63;margin-right:0.5rem;"></i>Birthday &amp; Family Functions</li>
                        <li style="color:var(--gray);font-size:0.88rem;"><i class="fas fa-check-circle" style="color:#e91e63;margin-right:0.5rem;"></i>Corporate Meetings</li>
                    </ul>
                </div>

                <!-- Card 2: Decoration -->
                <div class="specialty-card reveal" style="flex-direction:column;align-items:flex-start;padding:2.5rem 2rem;min-height:320px;">
                    <div class="s-icon" style="background:#ede9fe;color:#e91e63;margin-bottom:1.25rem;">
                        <i class="fas fa-wand-magic-sparkles"></i>
                    </div>
                    <h4 style="margin-bottom:1rem;font-size:1.05rem;">Decoration</h4>
                    <p style="color:var(--gray);font-size:0.88rem;line-height:1.8;margin:0;">Mahal decoration can be Arranged as per Customer Requirement.</p>
                </div>

                <!-- Card 3: Food Arrangements -->
                <div class="specialty-card reveal" style="flex-direction:column;align-items:flex-start;padding:2.5rem 2rem;min-height:320px;">
                    <div class="s-icon" style="background:#ede9fe;color:#e91e63;margin-bottom:1.25rem;">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h4 style="margin-bottom:1rem;font-size:1.05rem;">Food Arrangements</h4>
                    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.7rem;">
                        <li style="color:var(--gray);font-size:0.88rem;"><i class="fas fa-check-circle" style="color:#e91e63;margin-right:0.5rem;"></i>Breakfast, Lunch &amp; Dinner can be arranged on request</li>
                        <li style="color:var(--gray);font-size:0.88rem;"><i class="fas fa-check-circle" style="color:#e91e63;margin-right:0.5rem;"></i>Guests may arrange their own catering</li>
                    </ul>
                </div>

            </div>
        </div>
    </section>

    <!-- DYNAMIC GALLERY PREVIEW -->
    <?php if (!empty($gallery_preview)): ?>
    <section class="section" id="gallery-preview">
        <div class="container">
            <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:3rem;flex-wrap:wrap;gap:1rem;">
                <div class="reveal">
                    <div class="section-label"><i class="fas fa-images"></i> Visual Tour</div>
                    <h2 class="section-heading">Glimpse of <span>Our Gallery</span></h2>
                </div>
                <a href="gallery.php" class="btn btn-outline reveal">View Full Gallery <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1rem;">
                <?php foreach ($gallery_preview as $img): ?>
                    <a href="gallery.php" class="reveal" style="display:block;height:200px;border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow-sm);transition:var(--transition);">
                        <img src="assets/images/gallery/<?php echo htmlspecialchars($img['image_path']); ?>" alt="Gallery" style="width:100%;height:100%;object-fit:cover;transition:0.5s;">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA -->
    <section class="section">
        <div class="container reveal">
            <div class="cta-banner">
                <div style="position:relative;z-index:1;">
                    <h2>Ready to Book Your Dream Venue?</h2>
                    <p>Join hundreds of happy customers who trust Sri Lakshmi Residency & Mahal for their special events.</p>
                    <?php if (!isLoggedIn()): ?>
                        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                            <a href="register.php" class="btn btn-white btn-lg">Get Started Free</a>
                            <a href="halls.php" class="btn btn-lg" style="background:rgba(255,255,255,0.15);color:white;border:2px solid rgba(255,255,255,0.3);">Browse Halls</a>
                        </div>
                    <?php else: ?>
                        <a href="halls.php" class="btn btn-white btn-lg">Browse All Halls</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <?php include 'includes/footer.php'; ?>

    <?php include 'includes/modals.php'; ?>
    <?php include 'includes/chatbot.php'; ?>

    <script>
        // Reveal on scroll
        const reveal = () => {
            const reveals = document.querySelectorAll('.reveal');
            reveals.forEach(el => {
                const windowHeight = window.innerHeight;
                const elementTop = el.getBoundingClientRect().top;
                const elementVisible = 100;
                if (elementTop < windowHeight - elementVisible) {
                    el.classList.add('active');
                }
            });
        };
        window.addEventListener('scroll', reveal);
        reveal();

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                const target = document.querySelector(a.getAttribute('href'));
                if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
            });
        });

        // Home banner slider controls
        (function() {
            const slides = document.querySelectorAll('.banner-slide');
            if (!slides.length) return;

            let currentSlide = 0;
            const totalSlides = slides.length;
            const nextBtn = document.getElementById('bannerNext');
            const prevBtn = document.getElementById('bannerPrev');

            const showSlide = (index) => {
                slides.forEach((slide, i) => {
                    slide.classList.toggle('active', i === index);
                });
            };

            const goNext = () => {
                currentSlide = (currentSlide + 1) % totalSlides;
                showSlide(currentSlide);
            };

            const goPrev = () => {
                currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
                showSlide(currentSlide);
            };

            if (nextBtn) nextBtn.addEventListener('click', goNext);
            if (prevBtn) prevBtn.addEventListener('click', goPrev);

            let autoSlide = setInterval(goNext, 5000);

            const sliderSection = document.querySelector('.banner-slider');
            if (sliderSection) {
                sliderSection.addEventListener('mouseenter', () => clearInterval(autoSlide));
                sliderSection.addEventListener('mouseleave', () => autoSlide = setInterval(goNext, 5000));
            }
        })();
    </script>
</body>
</html>
