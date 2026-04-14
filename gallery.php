<?php
require_once 'includes/auth_functions.php';

// Fetch all gallery images
try {
    $stmt = $pdo->query("SELECT * FROM gallery ORDER BY created_at ASC");
    $gallery_images = $stmt->fetchAll();
} catch (Exception $e) {
    $gallery_images = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Gallery | <?php echo $brand_name; ?></title>
    <meta name="description" content="Explore our premium marriage halls and event venues through our high-quality photo gallery.">
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gallery-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 2rem 0;
        }
        @media (max-width: 640px) {
            .gallery-container {
                grid-template-columns: 1fr;
                justify-items: center;
            }
            .gallery-card {
                width: 100%;
            }
            .highlights-grid {
                grid-template-columns: 1fr !important;
            }
        }
        .gallery-card {
            position: relative;
            height: 280px;
            border-radius: var(--radius-lg);
            overflow: hidden;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 3px solid var(--primary-light);
        }
        .gallery-card:hover { 
            transform: scale(1.02) translateY(-5px); 
            box-shadow: var(--shadow-lg); 
            border-color: var(--primary);
        }
        .gallery-card img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .gallery-card:hover img { transform: scale(1.1); }
        
        .gallery-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(136,14,79,0.7) 0%, transparent 60%);
            display: flex; align-items: flex-end; padding: 1.5rem;
            opacity: 0; transition: var(--transition);
        }
        .gallery-card:hover .gallery-overlay { opacity: 1; }
        .gallery-title { color: white; font-weight: 700; font-size: 1rem; transform: translateY(10px); transition: var(--transition); }
        .gallery-card:hover .gallery-title { transform: translateY(0); }

        /* Lightbox Model */
        #galleryModel {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.95); z-index: 10000;
            align-items: center; justify-content: center;
            backdrop-filter: blur(15px);
            padding: 2rem;
        }
        #galleryModel.active { display: flex; }
        .model-content { 
            position: relative; 
            max-width: 1000px; 
            width: 100%; 
            display: flex; 
            flex-direction: column;
            align-items: center; 
            gap: 1.5rem;
        }
        .model-img-wrapper {
            position: relative;
            width: 100%;
            display: flex;
            justify-content: center;
        }
        .model-img { 
            max-width: 100%; 
            max-height: 70vh; 
            object-fit: contain; 
            border-radius: var(--radius-lg); 
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            border: 4px solid rgba(255,255,255,0.1);
        }
        .model-info {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            border-radius: var(--radius-lg);
            text-align: center;
            color: white;
            max-width: 800px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeInModal 0.4s ease-out;
        }
        @keyframes fadeInModal {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .model-title { 
            font-size: 1.5rem; 
            font-weight: 700; 
            margin-bottom: 0.5rem;
            color: var(--primary-light);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .model-desc { 
            font-size: 1rem; 
            line-height: 1.6; 
            color: rgba(255,255,255,0.8);
        }
        .model-close { 
            position: absolute; 
            top: -20px; 
            right: -20px; 
            color: white; 
            font-size: 1.25rem; 
            cursor: pointer; 
            background: var(--primary); 
            backdrop-filter: blur(5px);
            border-radius: 50%; 
            width: 45px; 
            height: 45px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            transition: var(--transition);
            border: 3px solid rgba(255,255,255,0.8);
            z-index: 10005;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3), 0 0 0 5px rgba(233,30,99,0.1);
        }
        .model-close:hover {
            background: var(--dark);
            transform: rotate(90deg) scale(1.1);
            color: white;
            border-color: white;
        }
        @media (max-width: 768px) {
            #galleryModel { padding: 1.5rem; }
            .model-img { max-height: 55vh; }
            .model-title { font-size: 1.25rem; }
            .model-desc { font-size: 0.9rem; }
            .model-close { top: -15px; right: -15px; width: 40px; height: 40px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- <header class="page-header">
        <div class="container text-center">
            <div class="section-label"><i class="fas fa-images"></i> Visual Experience</div>
            <h1>Our Photo <span>Gallery</span></h1>
            <p>A glimpse into the stunning events and premium venues at Sri Lakshmi Residency & Mahal.</p>
        </div>
    </header> -->


    <section class="section">
        <div class="container text-center" style="margin-bottom: 3rem;">
            <div class="gallery-filter-wrap" style="display:inline-flex; background:rgba(233,30,99,0.05); padding:0.5rem; border-radius:var(--radius-full); gap:0.5rem;">
                <button class="btn btn-primary" onclick="filterGallery('all')" id="btn-all">All Photos</button>
                <button class="btn" onclick="filterGallery('room')" id="btn-room" style="color:var(--dark-2);">Rooms</button>
                <button class="btn" onclick="filterGallery('mahal')" id="btn-mahal" style="color:var(--dark-2);">Mahal</button>
            </div>
        </div>

        <div class="container">
            <?php if (empty($gallery_images)): ?>
                <div class="text-center reveal" style="padding:5rem 0;">
                    <div style="margin-bottom: 2rem;">
                        <img src="assets/images/wedding_illust.svg" alt="Curating Gallery" style="width: 100%; max-width: 300px; opacity: 0.8;">
                    </div>
                    <h3>Gallery is being curated</h3>
                    <p style="color:var(--gray);">We're currently uploading beautiful photos of our halls and rooms. Please check back soon!</p>
                    <a href="index.php" class="btn btn-primary" style="margin-top:2rem;">Back to Home</a>
                </div>
            <?php else: ?>
                <div class="gallery-container stagger-children">
                    <?php foreach ($gallery_images as $img): ?>
                        <div class="gallery-card gallery-item-wrap glass-card reveal" data-category="<?php echo $img['category']; ?>" 
                             onclick="openGalleryModel('assets/images/gallery/<?php echo $img['image_path']; ?>', '<?php echo htmlspecialchars($img['title']); ?>', '<?php echo htmlspecialchars($img['description_en']); ?>')">
                            <img src="assets/images/gallery/<?php echo htmlspecialchars($img['image_path']); ?>" alt="Gallery Image">
                            <div class="gallery-overlay">
                                <div class="gallery-title">
                                    <div style="font-weight:700;"><?php echo htmlspecialchars($img['title'] ?: 'Venue Photo'); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Venue Highlights Section -->
    <section class="section" style="background: white; border-top: 1px solid var(--border);">
        <div class="container">
            <div class="text-center" style="margin-bottom: 3rem;">
                <div class="section-label"><i class="fas fa-star"></i> Venue Highlights</div>
                <h2 class="section-heading">Why Choose <span>Sri Lakshmi Residency?</span></h2>
                <p style="color:var(--gray);max-width:600px;margin:0 auto;">Beyond stunning venues, we offer premium amenities to make your stay and events perfect.</p>
            </div>

            <div class="highlights-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                <?php
                $milestones = [
                    ['fas fa-bed',       '43 Air-Conditioned Rooms',    'With complimentary breakfast for all guests'],
                    ['fas fa-utensils',  'Fully AC Dining Hall',         'Centrally air-conditioned and spacious dining area'],
                    ['fas fa-users',     'Mahal for 300 Guests',         'Fully air-conditioned Mahal for grand celebrations'],
                    ['fas fa-parking',   'Spacious Parking Facility',    'Ample parking for guests and visitors'],
                    ['fas fa-wifi',      'Free Wi-Fi',                   'High-speed internet throughout the property'],
                    ['fas fa-tint',      '24/7 Water Supply',            'Uninterrupted water supply round the clock'],
                    ['fas fa-clock',     'Complimentary Breakfast',      'Available daily from 9:00 AM to 11:00 AM'],
                    ['fas fa-hotel',     'Mini Hall for 100 Guests',    'Perfect for intimate gatherings and small functions'],
                ];
                foreach ($milestones as [$icon,$title,$desc]): ?>
                    <div class="reveal" style="background:#f8fafc; border-radius:var(--radius-lg); padding:1.75rem; border:1px solid var(--border); display: flex; align-items: flex-start; gap: 1.25rem; transition: var(--transition);" onmouseover="this.style.borderColor='var(--primary)';this.style.background='white';this.style.transform='translateY(-5px)';this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc';this.style.transform='translateY(0)';this.style.boxShadow='none'">
                        <div style="width:52px; height:52px; border-radius:var(--radius); background:var(--primary-light); display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:var(--transition);">
                            <i class="<?php echo $icon; ?>" style="color:var(--primary); font-size:1.25rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700; font-size:1rem; margin-bottom:0.4rem; color: var(--dark);"><?php echo $title; ?></div>
                            <div style="font-size:0.85rem; color:var(--gray); line-height:1.6;"><?php echo $desc; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center" style="margin-top: 3rem;">
                <a href="about.php" class="btn btn-outline"><i class="fas fa-info-circle"></i> Learn More About Our Story</a>
            </div>
        </div>
    </section>

    <!-- Gallery Model (Modal) -->
    <div id="galleryModel" onclick="closeGalleryModel()">
        <div class="model-content" onclick="event.stopPropagation()">
            <div class="model-img-wrapper">
                <button class="model-close" onclick="closeGalleryModel()"><i class="fas fa-times"></i></button>
                <img id="modelImg" src="" alt="Full Preview" class="model-img">
            </div>
            <div class="model-info">
                <div id="modelTitle" class="model-title"></div>
                <div id="modelDesc" class="model-desc"></div>
            </div>
        </div>
    </div>

    <?php include 'includes/modals.php'; ?>


    <?php include 'includes/chatbot.php'; ?>
    <?php include 'includes/footer.php'; ?>

    <script>
        function openGalleryModel(src, titleEn, descEn) {
            document.getElementById('modelImg').src = src;
            document.getElementById('modelTitle').textContent = titleEn || 'Venue Photo';
            document.getElementById('modelDesc').textContent = descEn || 'No description available for this image.';
            
            document.getElementById('galleryModel').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeGalleryModel() {
            document.getElementById('galleryModel').classList.remove('active');
            document.body.style.overflow = '';
        }

        function filterGallery(category) {
            const items = document.querySelectorAll('.gallery-item-wrap');
            items.forEach(item => {
                if (category === 'all' || item.getAttribute('data-category') === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });

            // Update buttons
            ['all', 'room', 'mahal'].forEach(cat => {
                const btn = document.getElementById('btn-' + cat);
                if (cat === category) {
                    btn.classList.add('btn-primary');
                    btn.style.color = 'white';
                } else {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn');
                    btn.style.color = 'var(--dark-2)';
                }
            });
            
            // Re-trigger reveal for filtered visible items
            reveal();
        }

        // Reveal effect
        const reveal = () => {
            const reveals = document.querySelectorAll('.reveal');
            reveals.forEach(el => {
                if (el.style.display !== 'none') {
                    const windowHeight = window.innerHeight;
                    const elementTop = el.getBoundingClientRect().top;
                    if (elementTop < windowHeight - 100) el.classList.add('active');
                }
            });
        };
        window.addEventListener('scroll', reveal);
        reveal();
    </script>
</body>
</html>
