<?php
require_once 'db.php';

session_start();
if (isset($_SESSION['admin_id']) && ($_SESSION['role'] ?? '') === 'admin') {
    $is_admin = true;
} elseif (isset($_SESSION['user_id'])) {
    $is_admin = false;
} else {
    header('Location: login.php');
    exit;
}

$plant_id = $_GET['id'] ?? null;

if (!$plant_id) {
    die("Plant not found.");
}

$stmt = $pdo->prepare("SELECT * FROM plant WHERE plant_id = ?");
$stmt->execute([$plant_id]);
$plant = $stmt->fetch();

if (!$plant) {
    die("Plant not found.");
}

$stmt = $pdo->prepare("SELECT * FROM plant_problem WHERE plant_id = ?");
$stmt->execute([$plant_id]);
$problems = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($plant['common_name']) ?> — Earthly</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --green-deep: #2D5A3D;
            --green-mid: #4A7C5C;
            --green-pale: #D4E8D0;
            --green-wash: #EFF6ED;
            --cream: #FAFAF5;
            --text-primary: #2B2B2B;
            --text-secondary: #5A5A5A;
            --text-light: #FAFAF5;
            --white: #FFFFFF;
            --warning-bg: #FFF8E6;
            --warning-border: #F0DFA0;
            --warning-text: #8B7020;
            --success: #2E6B43;
            --success-bg: #EAF5EC;
            --border-soft: rgba(45, 90, 61, 0.10);
            --shadow-sm: 0 2px 8px rgba(45, 90, 61, 0.08);
            --shadow-md: 0 4px 20px rgba(45, 90, 61, 0.12);
            --shadow-lg: 0 8px 40px rgba(45, 90, 61, 0.16);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Source Sans 3', sans-serif;
            color: var(--text-primary);
            background: var(--cream);
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button {
            font-family: inherit;
        }

        nav {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 100;
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(250, 250, 245, 0.9);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(45, 90, 61, 0.05);
            transition: box-shadow 0.3s ease;
        }

        nav.scrolled {
            box-shadow: var(--shadow-sm);
        }

        .nav-logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--green-deep);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            flex-wrap: wrap;
        }

        .nav-link {
            padding: 0.65rem 1rem;
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-weight: 500;
            transition: 0.25s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--green-wash);
            color: var(--green-deep);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.7rem 1.3rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.25s ease;
        }

        .btn-primary {
            background: var(--green-deep);
            color: var(--text-light);
        }

        .btn-primary:hover {
            background: #1E4A2E;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline {
            background: transparent;
            color: var(--green-deep);
            border: 1.5px solid var(--green-mid);
        }

        .btn-outline:hover {
            background: var(--green-deep);
            color: var(--text-light);
            border-color: var(--green-deep);
        }

        .btn-soft {
            background: var(--green-wash);
            color: var(--green-deep);
        }

        .btn-lg {
            padding: 0.9rem 2.2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
        }

        .page {
            padding: 7.4rem 5% 3rem;
            position: relative;
            min-height: 100vh;
        }

        .bg-shape-1,
        .bg-shape-2 {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            pointer-events: none;
        }

        .bg-shape-1 {
            top: 40px;
            left: -120px;
            width: 360px;
            height: 360px;
            background: var(--green-pale);
            opacity: 0.24;
        }

        .bg-shape-2 {
            bottom: -80px;
            right: -120px;
            width: 420px;
            height: 420px;
            background: #DCECD8;
            opacity: 0.34;
        }

        .layout {
            position: relative;
            z-index: 1;
            max-width: 860px;
            margin: 0 auto;
            display: grid;
            gap: 1.2rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 400;
        }

        .breadcrumb a {
            color: var(--green-deep);
            font-weight: 500;
        }

        .breadcrumb .sep {
            color: var(--text-secondary);
            opacity: 0.5;
        }

        .detail-card {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-soft);
            border-radius: 26px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .detail-image-wrap {
            position: relative;
            overflow: hidden;
            background: linear-gradient(180deg, #edf4ea, #f8fbf7);
        }

        .detail-image-wrap img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            display: block;
        }

        .detail-badges {
            position: absolute;
            bottom: 1rem;
            left: 1.2rem;
            display: flex;
            gap: 0.45rem;
            flex-wrap: wrap;
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            backdrop-filter: blur(8px);
        }

        .badge-category {
            background: rgba(45, 90, 61, 0.88);
            color: var(--text-light);
        }

        .badge-difficulty {
            background: rgba(255, 255, 255, 0.9);
            color: var(--green-deep);
            border: 1px solid rgba(45, 90, 61, 0.12);
        }

        .badge-pet {
            background: rgba(46, 107, 67, 0.88);
            color: var(--text-light);
        }

        .badge-not-pet {
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-secondary);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .detail-body {
            padding: 1.8rem;
            display: grid;
            gap: 1.6rem;
        }

        .detail-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            line-height: 1.1;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .detail-scientific {
            font-size: 1.05rem;
            color: var(--text-secondary);
            font-style: italic;
            font-weight: 300;
            margin-bottom: 0.5rem;
        }

        .detail-about {
            font-size: 0.96rem;
            line-height: 1.7;
            color: var(--text-secondary);
            font-weight: 300;
        }

        .detail-section {
            display: grid;
            gap: 0.85rem;
        }

        .section-label {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            padding-bottom: 0.4rem;
            border-bottom: 1px solid rgba(45, 90, 61, 0.08);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.9rem 1rem;
            background: var(--cream);
            border-radius: 14px;
            border: 1px solid rgba(45, 90, 61, 0.06);
        }

        .info-row.full-width {
            grid-column: 1 / -1;
        }

        .info-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--green-wash);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.2rem;
        }

        .info-label {
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--green-mid);
            font-weight: 600;
            margin-bottom: 0.12rem;
        }

        .info-value {
            font-size: 0.96rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .care-tip-box {
            background: linear-gradient(135deg, #f7faf6, #ffffff);
            border: 1px solid rgba(45, 90, 61, 0.1);
            border-radius: 16px;
            padding: 1.15rem 1.3rem;
        }

        .care-tip-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: var(--green-mid);
            font-weight: 600;
            margin-bottom: 0.45rem;
        }

        .care-tip-text {
            font-size: 0.96rem;
            line-height: 1.65;
            color: var(--text-secondary);
            font-weight: 300;
        }

        .problems-list {
            display: grid;
            gap: 0.6rem;
        }

        .problem-item {
            display: flex;
            gap: 0.8rem;
            align-items: flex-start;
            padding: 0.85rem 1rem;
            background: var(--warning-bg);
            border: 1px solid var(--warning-border);
            border-radius: 14px;
        }

        .problem-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(139, 112, 32, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1rem;
        }

        .problem-name {
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--warning-text);
            margin-bottom: 0.15rem;
        }

        .problem-desc {
            font-size: 0.9rem;
            line-height: 1.55;
            color: var(--text-secondary);
            font-weight: 300;
        }

        .fun-fact-box {
            background: var(--green-wash);
            border: 1px solid rgba(45, 90, 61, 0.1);
            border-radius: 16px;
            padding: 1rem 1.2rem;
            display: flex;
            gap: 0.8rem;
            align-items: flex-start;
        }

        .fun-fact-icon {
            font-size: 1.3rem;
            flex-shrink: 0;
            padding-top: 0.1rem;
        }

        .fun-fact-text {
            font-size: 0.94rem;
            line-height: 1.6;
            color: var(--text-secondary);
            font-weight: 300;
        }

        .fun-fact-text strong {
            color: var(--green-deep);
            font-weight: 600;
        }

        .detail-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            padding-top: 0.6rem;
            border-top: 1px solid rgba(45, 90, 61, 0.08);
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 999;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.visible {
            display: flex;
        }

        .modal-box {
            background: var(--white);
            border-radius: 24px;
            padding: 2.4rem 2.2rem 2rem;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(45, 90, 61, 0.22);
        }

        .modal-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            background: var(--success-bg);
        }

        .modal-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .modal-desc {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            font-weight: 300;
            margin-bottom: 1.5rem;
        }

        .modal-desc strong {
            color: var(--green-deep);
            font-weight: 600;
        }

        .modal-actions {
            display: flex;
            gap: 0.6rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .modal-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.8rem;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .modal-btn.primary {
            background: var(--green-deep);
            color: var(--text-light);
        }

        .modal-btn.ghost {
            background: transparent;
            color: var(--text-secondary);
        }

        .page-footer {
            max-width: 860px;
            margin: 1.3rem auto 0;
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.82rem;
            font-weight: 300;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 760px) {
            nav {
                padding: 1rem 4%;
            }

            .page {
                padding: 7rem 4% 2rem;
            }

            .detail-body {
                padding: 1.2rem;
            }

            .detail-header h1 {
                font-size: 1.8rem;
            }

            .detail-image-wrap img {
                height: 260px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-row.full-width {
                grid-column: auto;
            }

            .nav-right {
                gap: 0.35rem;
            }

            .nav-link,
            .btn {
                padding: 0.6rem 0.85rem;
            }
        }
    </style>
</head>

<body>

    <nav id="navbar">
        <a href="index.html" class="nav-logo">Earthly</a>
        <div class="nav-right">
            <?php if ($is_admin): ?>
                <a href="admin-dashboard.php" class="nav-link">Admin Dashboard</a>
            <?php else: ?>
                <a href="user-dashboard.php" class="nav-link">Dashboard</a>
            <?php endif; ?>
            <a href="plant-catalog.php" class="nav-link active">Catalog</a>
            <a href="logout.php" class="btn btn-outline">Log out</a>
        </div>
    </nav>

    <?php if (!$is_admin): ?>
    <div class="modal-overlay" id="addedModal">
        <div class="modal-box">
            <div class="modal-icon">✓</div>
            <h2 class="modal-title">Added to My Plants!</h2>
            <p class="modal-desc">
                <strong id="modalPlantName"><?= htmlspecialchars($plant['common_name']) ?></strong> has been added to
                your personal collection.
            </p>
            <div class="modal-actions">
                <a href="manage-plants.php" class="modal-btn primary">Go to My Plants</a>
                <button class="modal-btn ghost" id="modalContinue">Continue Browsing</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <main class="page">
        <div class="bg-shape-1"></div>
        <div class="bg-shape-2"></div>

        <section class="layout">
            <div class="breadcrumb">
                <a href="plant-catalog.php">Catalog</a>
                <span class="sep">›</span>
                <span><?= htmlspecialchars($plant['common_name']) ?></span>
            </div>

            <div class="detail-card">
                <div class="detail-image-wrap">
                    <img src="images/<?= htmlspecialchars($plant['image']) ?>"
                        alt="<?= htmlspecialchars($plant['common_name']) ?>">

                    <div class="detail-badges">
                        <span class="badge badge-category"><?= htmlspecialchars($plant['category']) ?></span>
                        <span class="badge badge-difficulty"><?= htmlspecialchars($plant['difficulty']) ?></span>

                        <?php if ($plant['pet_safe']): ?>
                            <span class="badge badge-pet">Pet safe</span>
                        <?php else: ?>
                            <span class="badge badge-not-pet">Not pet safe</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-body">
                    <div class="detail-header">
                        <h1><?= htmlspecialchars($plant['common_name']) ?></h1>
                        <p class="detail-scientific"><?= htmlspecialchars($plant['scientific_name']) ?></p>
                        <p class="detail-about"><?= htmlspecialchars($plant['about']) ?></p>
                    </div>

                    <div class="detail-section">
                        <h2 class="section-label">Quick overview</h2>

                        <div class="info-grid">
                            <div class="info-row">
                                <div class="info-icon">💧</div>
                                <div>
                                    <div class="info-label">Watering</div>
                                    <div class="info-value"><?= htmlspecialchars($plant['watering']) ?></div>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-icon">☀️</div>
                                <div>
                                    <div class="info-label">Light</div>
                                    <div class="info-value"><?= htmlspecialchars($plant['light']) ?></div>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-icon">📊</div>
                                <div>
                                    <div class="info-label">Difficulty</div>
                                    <div class="info-value"><?= htmlspecialchars($plant['difficulty']) ?></div>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-icon">🐾</div>
                                <div>
                                    <div class="info-label">Pet Safety</div>
                                    <div class="info-value"><?= $plant['pet_safe'] ? 'Safe for pets' : 'Not pet safe' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h2 class="section-label">Care guide</h2>

                        <div class="info-grid">
                            <div class="info-row">
                                <div class="info-icon">🪴</div>
                                <div>
                                    <div class="info-label">Soil Type</div>
                                    <div class="info-value"><?= htmlspecialchars($plant['soil']) ?></div>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-icon">🌡️</div>
                                <div>
                                    <div class="info-label">Temperature</div>
                                    <div class="info-value"><?= htmlspecialchars($plant['temperature']) ?></div>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-icon">💨</div>
                                <div>
                                    <div class="info-label">Humidity</div>
                                    <div class="info-value"><?= htmlspecialchars($plant['humidity']) ?></div>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-icon">📏</div>
                                <div>
                                    <div class="info-label">Expected Size</div>
                                    <div class="info-value"><?= htmlspecialchars($plant['size']) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="care-tip-box">
                            <div class="care-tip-label">Care Tip</div>
                            <p class="care-tip-text"><?= htmlspecialchars($plant['tip']) ?></p>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h2 class="section-label">Common problems</h2>

                        <div class="problems-list">
                            <?php if (count($problems) > 0): ?>
                                <?php foreach ($problems as $problem): ?>
                                    <div class="problem-item">
                                        <div class="problem-icon">⚠️</div>
                                        <div>
                                            <div class="problem-name"><?= htmlspecialchars($problem['problem_name']) ?></div>
                                            <div class="problem-desc"><?= htmlspecialchars($problem['problem_desc']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No common problems available.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h2 class="section-label">Good to know</h2>

                        <div class="info-row full-width">
                            <div class="info-icon">🌍</div>
                            <div>
                                <div class="info-label">Origin</div>
                                <div class="info-value"><?= htmlspecialchars($plant['origin']) ?></div>
                            </div>
                        </div>

                        <div class="fun-fact-box">
                            <div class="fun-fact-icon">💡</div>
                            <p class="fun-fact-text">
                                <strong>Fun fact:</strong> <?= htmlspecialchars($plant['fun_fact']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="detail-actions">
                        <?php if (!$is_admin): ?>
                        <button type="button" class="btn btn-primary btn-lg" id="addBtn"
                            data-plant-id="<?= htmlspecialchars($plant['plant_id']) ?>">
                            Add to My Plants
                        </button>
                        <?php endif; ?>

                        <a href="plant-catalog.php" class="btn btn-outline">Back to Catalog</a>
                    </div>
                </div>
            </div>
        </section>

        <p class="page-footer">© 2026 Earthly — Group 6, IT320 Section 77800, King Saud University</p>
    </main>

    <script>
        window.addEventListener('scroll', () => {
            document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
        });

        <?php if (!$is_admin): ?>
        const addBtn = document.getElementById('addBtn');
        const addedModal = document.getElementById('addedModal');
        const modalContinue = document.getElementById('modalContinue');

        addBtn.addEventListener('click', async () => {
            const plantId = addBtn.dataset.plantId;

            addBtn.disabled = true;
            addBtn.textContent = 'Adding...';

            try {
                const response = await fetch('add_to_collection.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'plant_id=' + encodeURIComponent(plantId)
                });

                const result = await response.json();

                if (result.success) {
                    addBtn.textContent = 'Added to My Plants';
                    addBtn.classList.remove('btn-primary');
                    addBtn.classList.add('btn-soft');

                    addedModal.classList.add('visible');
                } else {
                    addBtn.disabled = false;
                    addBtn.textContent = 'Add to My Plants';
                    alert('Could not add plant. Please try again.');
                }
            } catch (error) {
                addBtn.disabled = false;
                addBtn.textContent = 'Add to My Plants';
                alert('Something went wrong. Please try again.');
            }
        });

        modalContinue.addEventListener('click', () => {
            addedModal.classList.remove('visible');
        });
        <?php endif; ?>
    </script>

</body>

</html>