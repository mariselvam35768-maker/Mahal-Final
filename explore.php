<?php
require_once 'includes/auth_functions.php';

// Fetch all explore items
try {
    $stmt = $pdo->query("SELECT * FROM explore ORDER BY created_at DESC");
    $explore_items = $stmt->fetchAll();
} catch (Exception $e) {
    $explore_items = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore | <?php echo $brand_name; ?></title>
    <meta name="description" content="Explore our beautiful venues, decorations, and special features of our marriage halls.">
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .explore-section {
            padding: 4rem 2rem;
            min-height: 100vh;
            background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
            margin-top: 5rem;
        }

        .section-header h1 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .section-header p {
            font-size: 1.1rem;
            color: var(--gray);
            font-weight: 500;
        }

        .explore-container {
            max-width: 1320px;
            margin: 0 auto;
        }

        .explore-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            padding: 0;
        }

        .explore-card {
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .explore-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .explore-image-wrapper {
            width: 100%;
            height: 280px;
            overflow: hidden;
            background: #f3f4f6;
            position: relative;
        }

        .explore-card:hover .explore-image-wrapper img {
            transform: scale(1.08);
        }

        .explore-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .explore-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 12px rgba(233, 30, 99, 0.3);
        }

        .explore-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .explore-description {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 0;
            flex-grow: 1;
        }

        .explore-footer {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .explore-date {
            font-size: 0.8rem;
            color: var(--gray-light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .explore-date i {
            color: var(--primary);
        }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-light);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .empty-state h3 {
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .explore-section {
                padding: 2.5rem 1.5rem;
            }

            .explore-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .section-header h1 {
                font-size: 1.8rem;
            }

            .section-header p {
                font-size: 1rem;
            }
        }

        @media (max-width: 640px) {
            .explore-section {
                padding: 2rem 1rem;
            }

            .section-header h1 {
                font-size: 1.5rem;
            }

            .explore-image-wrapper {
                height: 200px;
            }

            .explore-content {
                padding: 1rem;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .explore-card {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .explore-card:nth-child(1) { animation-delay: 0.1s; }
        .explore-card:nth-child(2) { animation-delay: 0.2s; }
        .explore-card:nth-child(3) { animation-delay: 0.3s; }
        .explore-card:nth-child(n+4) { animation-delay: 0.1s; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main>
        <section class="explore-section">
            <div class="explore-container" style="padding-top: 6rem;">
                <div class="explore-grid" id="exploreGrid">
                    <?php if (empty($explore_items)): ?>
                        <div class="empty-state">
                            <i class="fas fa-image"></i>
                            <h3>Coming Soon</h3>
                            <p>Exciting content is being prepared for you. Check back soon!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($explore_items as $item): ?>
                            <div class="explore-card">
                                <div class="explore-image-wrapper">
                                    <img 
                                        src="assets/images/explore/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                        alt="<?php echo htmlspecialchars($item['description'] ?? 'Explore Image'); ?>"
                                        loading="lazy"
                                    >
                                    <div class="explore-badge">
                                        <i class="fas fa-star"></i> Featured
                                    </div>
                                </div>
                                <div class="explore-content">
                                    <h3 style="font-size:1.15rem; font-weight:800; color:var(--dark); margin-bottom:0.15rem;"><?php echo htmlspecialchars($item['title'] ?: 'Venue Highlight'); ?></h3>
                                    <?php if ($item['subtitle']): ?>
                                        <p style="font-size:0.8rem; font-weight:700; color:var(--primary); text-transform:uppercase; margin-bottom:0.75rem; display:flex; align-items:center; gap:0.4rem;">
                                            <i class="fas fa-location-dot" style="font-size:0.7rem;"></i> <?php echo htmlspecialchars($item['subtitle']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="explore-description">
                                        <?php echo htmlspecialchars($item['description']); ?>
                                    </p>
                                    <div class="explore-footer">
                                        <div class="explore-date">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo date('d M Y', strtotime($item['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/alerts.php'; ?>

    <script src="assets/js/validation.js"></script>
</body>
</html>
