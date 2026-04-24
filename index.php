<?php
require_once 'includes/auth_functions.php';

// Fetch featured halls for homepage showcase
$featured_halls = [];
try {
    $featured_stmt = $pdo->query("SELECT * FROM halls ORDER BY created_at DESC LIMIT 3");
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

    $image_count = count($home_banner_paths);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $brand_name; ?> | Premium Wedding & Event Venues</title>
    <meta name="description" content="Experience elegance and luxury at Sri Lakshmi Residency & Mahal. Book premium marriage halls and stay rooms in Srivilliputhur.">
    <link rel="stylesheet" href="assets/css/style.css?v=premium1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero {
            min-height: 80vh;
            display: flex;
            align-items: center;
            background: var(--gradient-hero);
            position: relative;
            color: white;
            padding-top: 215px;
            padding-bottom: 80px;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            z-index: 1;
        }

        .hero-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.4;
        }

        .hero-content {
            position: relative;
            z-index: 5;
            max-width: 800px;
        }

        .hero-label {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .hero h1 {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            color: white;
            line-height: 1.1;
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease both;
        }

        .hero p {
            font-size: 1.15rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 3rem;
            max-width: 600px;
            animation: fadeInUp 1s ease 0.2s both;
        }

        .hero-btns {
            display: flex;
            gap: 1.5rem;
            animation: fadeInUp 1s ease 0.4s both;
        }

        .stats-section {
            background: white;
            padding: 4rem 0;
        }

        .stats-grid {
            margin-left: auto;
            margin-right: auto;
            width: 90%;
            max-width: 1100px;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            padding: 3rem 2rem;
            position: relative;
            z-index: 10;
            border: 1px solid var(--border);
        }

        .stat-item {
            text-align: center;
            border-right: 1px solid var(--border);
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            font-family: 'Playfair Display', serif;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 2rem;
                padding: 2rem 1.5rem;
                width: 95%;
            }
            .stat-item:nth-child(2) {
                border-right: none;
            }
            .hero {
                padding-bottom: 100px;
                text-align: center;
            }
            .hero-btns {
                justify-content: center;
            }
            .hero-content {
                margin: 0 auto;
            }
        }

        /* Features Section */
        .features-section {
            padding: 8rem 0 8rem;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2.5rem;
            margin-top: 4rem;
        }

        .feature-item {
            background: white;
            padding: 3rem 2rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            transition: var(--transition);
        }

        .feature-item:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 2rem;
        }

        .feature-item h3 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .feature-item p {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.7;
        }

        @media (max-width: 992px) {
            .feature-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Booking Steps */
        .steps-section {
            background: var(--primary-deep);
            color: white;
            padding: 8rem 0;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4rem;
            margin-top: 5rem;
        }

        .step-card {
            background: rgba(255,255,255,0.03);
            padding: 2.5rem 2rem;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(255,255,255,0.08);
            transition: var(--transition);
        }

        .step-card:hover {
            background: rgba(255,255,255,0.06);
            transform: translateY(-5px);
        }

        .step-num {
            display: inline-flex;
            width: 45px;
            height: 45px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(195, 128, 91, 0.4);
            font-family: 'Inter', sans-serif;
        }

        .step-card h3 {
            color: white;
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }

        .step-card p {
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.7;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-bg">
            <img src="<?php echo htmlspecialchars($home_banner_paths[0]); ?>" alt="Grand Mahal">
        </div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-label">
                    <span>Exclusive Event Spaces</span>
                </div>
                <h1>Where Every Moment Becomes a <span>Masterpiece</span></h1>
                <p>Discover the finest venues in Srivilliputhur for your weddings, celebrations, and premium stays. Elegance redefined for your special day.</p>
                <div class="hero-btns">
                    <a href="halls.php" class="btn btn-primary btn-lg">Explore Venues</a>
                    <a href="about.php" class="btn btn-outline btn-lg" style="border-color:white; color:white !important; background: transparent;">Our Story</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Bar Section -->
    <section class="stats-section">
        <div class="stats-grid stagger-children">
            <div class="stat-item reveal">
                <div class="stat-number"><?php echo $total_halls_count; ?>+</div>
                <div class="stat-label">Venues</div>
            </div>
            <div class="stat-item reveal">
                <div class="stat-number"><?php echo $total_bookings_count; ?>+</div>
                <div class="stat-label">Events Hosted</div>
            </div>
            <div class="stat-item reveal">
                <div class="stat-number">100%</div>
                <div class="stat-label">AC Facilities</div>
            </div>
            <div class="stat-item reveal" style="border:none;">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Support</div>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="features-section">
        <div class="container">
            <div class="text-center reveal">
                <div class="section-label">Luxury Amenities</div>
                <h2 class="section-heading">Designed for <span>Excellence</span></h2>
                <p class="section-sub" style="margin: 0 auto;">We offer more than just a space; we provide a full-service experience tailored to your needs.</p>
            </div>

            <div class="feature-grid">
                <div class="feature-item reveal delay-100">
                    <div class="feature-icon"><i class="fas fa-snowflake"></i></div>
                    <h3>Fully Air-Conditioned</h3>
                    <p>Both our residency rooms and the grand mahal are equipped with high-end climate control for your comfort.</p>
                </div>
                <div class="feature-item reveal delay-200">
                    <div class="feature-icon"><i class="fas fa-utensils"></i></div>
                    <h3>Catering Options</h3>
                    <p>Choose our premium catering services or bring your own preferred team. We accommodate your choice.</p>
                </div>
                <div class="feature-item reveal delay-300">
                    <div class="feature-icon"><i class="fas fa-car"></i></div>
                    <h3>Ample Parking</h3>
                    <p>Generous parking space for your guests, ensuring a hassle-free arrival for even the largest gatherings.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS (STEPS) -->
    <section class="steps-section">
        <div class="container">
            <div class="text-center reveal">
                <h2 class="section-heading" style="color:white;">Your Journey to <span>Celebration</span></h2>
                <p class="section-sub" style="margin: 0 auto; color: rgba(255,255,255,0.6);">A seamless process to secure your perfect date.</p>
            </div>

            <div class="steps-grid">
                <div class="step-card reveal">
                    <div class="step-num">01</div>
                    <h3>Find Your Space</h3>
                    <p>Browse through our collection of premium halls and luxury rooms based on your event capacity.</p>
                </div>
                <div class="step-card reveal">
                    <div class="step-num">02</div>
                    <h3>Pick Your Date</h3>
                    <p>Check real-time availability and select the date that fits your special occasion.</p>
                </div>
                <div class="step-card reveal">
                    <div class="step-num">03</div>
                    <h3>Secure Booking</h3>
                    <p>Confirm your booking with a simple advance payment and receive instant confirmation.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURED HALLS -->
    <?php if (!empty($featured_halls)): ?>
    <section class="section" id="featured-halls">
        <div class="container">
            <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:4rem;flex-wrap:wrap;gap:1rem;">
                <div class="reveal">
                    <div class="section-label">Our Venues</div>
                    <h2 class="section-heading">Featured <span>Spaces</span></h2>
                </div>
                <a href="halls.php" class="btn btn-outline reveal">View All Halls <i class="fas fa-arrow-right"></i></a>
            </div>

            <div class="halls-grid">
                <?php foreach ($featured_halls as $hall): ?>
                    <div class="hall-card reveal">
                        <div class="hall-card-img">
                            <?php if ($hall['main_image']): ?>
                                <img src="assets/images/halls/<?php echo htmlspecialchars($hall['main_image']); ?>" alt="<?php echo htmlspecialchars($hall['name']); ?>">
                            <?php else: ?>
                                <div style="width:100%;height:100%;background:var(--primary-deep);display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-building" style="font-size:3rem;color:rgba(255,255,255,0.1);"></i>
                                </div>
                            <?php endif; ?>
                            <div class="hall-price">Rs. <?php echo number_format($hall['price_per_day']); ?>/day</div>
                        </div>
                        <div class="hall-card-body">
                            <h3 class="hall-card-title"><?php echo htmlspecialchars($hall['name']); ?></h3>
                            <div class="hall-card-meta">
                                <span><i class="fas fa-users"></i> <?php echo number_format($hall['capacity']); ?> Guests</span>
                                <span><i class="fas fa-snowflake"></i> Fully AC</span>
                            </div>
                            <p><?php echo htmlspecialchars(substr($hall['description'], 0, 100)) . '...'; ?></p>
                            <a href="halls.php?id=<?php echo $hall['id']; ?>" class="btn btn-primary" style="width:100%;justify-content:center;">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- GALLERY TEASER -->
    <section class="section" style="background: var(--bg);">
        <div class="container">
            <div class="grid-2" style="align-items: center; gap: 5rem;">
                <div class="reveal">
                    <div class="section-label">Experience</div>
                    <h2 class="section-heading">Glimpses of <span>Elegance</span></h2>
                    <p style="color: var(--gray); margin-bottom: 2.5rem; line-height: 1.8;">Take a virtual tour through our property. From grand wedding setups to cozy residency rooms, see how we bring celebrations to life.</p>
                    <a href="gallery.php" class="btn btn-primary">Open Gallery</a>
                </div>
                <div class="reveal" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="height: 200px; border-radius: var(--radius-sm); overflow: hidden; background: #ddd;">
                            <img src="assets/images/rooms/room_1775375551_2977.jpg" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="height: 280px; border-radius: var(--radius-sm); overflow: hidden; background: #ddd;">
                            <img src="assets/images/rooms/room_1775567533_1280.jpg" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 1rem; padding-top: 2rem;">
                        <div style="height: 280px; border-radius: var(--radius-sm); overflow: hidden; background: #ddd;">
                            <img src="assets/images/rooms/room_1775574377_4479.jpg" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="height: 200px; border-radius: var(--radius-sm); overflow: hidden; background: #ddd;">
                            <img src="assets/images/rooms/room_1775576029_1117.jpeg" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CALL TO ACTION -->
    <section class="section">
        <div class="container reveal">
            <div style="background: var(--gradient-deep); padding: 5rem; border-radius: var(--radius-lg); text-align: center; color: white;">
                <h2 style="color: white; font-size: 2.5rem; margin-bottom: 1.5rem;">Start Planning Your Event Today</h2>
                <p style="color: rgba(255,255,255,0.7); max-width: 600px; margin: 0 auto 3rem;">Ready to host an unforgettable celebration? Our team is here to assist you at every step.</p>
                <div style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap;">
                    <a href="contact.php" class="btn btn-primary btn-lg">Contact Us</a>
                    <a href="register.php" class="btn btn-primary btn-lg" style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.3); color:white;">Create Account</a>
                </div>
            </div>
        </div>
    </section>

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
    </script>
</body>
</html>
