<?php
require_once 'db.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'all';

$sql = "SELECT * FROM plant WHERE 1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (common_name LIKE ? OR scientific_name LIKE ? OR category LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$plants = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Plant Catalog — Earthly</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --green-deep: #2D5A3D;
            --green-mid: #4A7C5C;
            --green-light: #8FBC8F;
            --green-pale: #D4E8D0;
            --green-wash: #EFF6ED;
            --cream: #FAFAF5;
            --text-primary: #2B2B2B;
            --text-secondary: #5A5A5A;
            --text-light: #FAFAF5;
            --white: #FFFFFF;
            --border-soft: rgba(45, 90, 61, 0.10);
            --shadow-sm: 0 2px 8px rgba(45, 90, 61, 0.08);
            --shadow-md: 0 4px 20px rgba(45, 90, 61, 0.12);
            --shadow-lg: 0 8px 40px rgba(45, 90, 61, 0.16);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Source Sans 3', sans-serif;
            color: var(--text-primary);
            background: var(--cream);
            overflow-x: hidden;
        }

        a { text-decoration: none; color: inherit; }
        button, input { font-family: inherit; }

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

        nav.scrolled { box-shadow: var(--shadow-sm); }

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
            max-width: 1220px;
            margin: 0 auto;
            display: grid;
            gap: 1.2rem;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.4rem;
            line-height: 1.1;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .filter-bar {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-soft);
            border-radius: 22px;
            box-shadow: var(--shadow-lg);
            padding: 1.2rem 1.4rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .search-row {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 0.85rem 1rem;
            border-radius: 10px;
            border: 1px solid rgba(45, 90, 61, 0.15);
            background: rgba(255, 255, 255, 0.92);
            font-size: 0.96rem;
            color: var(--text-primary);
            outline: none;
            transition: all 0.25s ease;
        }

        .search-input:focus {
            border-color: var(--green-mid);
            box-shadow: 0 0 0 4px rgba(74, 124, 92, 0.12);
            background: var(--white);
        }

        .category-pills {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .pill {
            padding: 0.5rem 1rem;
            border-radius: 999px;
            font-size: 0.88rem;
            font-weight: 500;
            cursor: pointer;
            border: 1.5px solid rgba(45, 90, 61, 0.15);
            background: var(--white);
            color: var(--text-secondary);
            transition: all 0.25s ease;
        }

        .pill:hover {
            border-color: var(--green-mid);
            color: var(--green-deep);
        }

        .pill.active {
            background: var(--green-deep);
            color: var(--text-light);
            border-color: var(--green-deep);
        }

        .results-info {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 300;
        }

        .plant-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.2rem;
        }

        .plant-card {
            background: var(--white);
            border: 1px solid rgba(45, 90, 61, 0.08);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.28s ease;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            color: inherit;
        }

        .plant-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .card-image-wrap {
            height: 400px;
            background: linear-gradient(180deg, #edf4ea, #f8fbf7);
            overflow: hidden;
            position: relative;
        }

        .card-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }

        .plant-card:hover .card-image-wrap img {
            transform: scale(1.05);
        }

        .card-badges {
            position: absolute;
            top: 0.7rem;
            left: 0.7rem;
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .badge {
            padding: 0.3rem 0.65rem;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 600;
            backdrop-filter: blur(6px);
        }

        .badge-category {
            background: rgba(45, 90, 61, 0.85);
            color: var(--text-light);
        }

        .badge-difficulty {
            background: rgba(255, 255, 255, 0.88);
            color: var(--green-deep);
            border: 1px solid rgba(45, 90, 61, 0.12);
        }

        .badge-pet {
            background: rgba(46, 107, 67, 0.85);
            color: var(--text-light);
        }

        .card-body {
            padding: 1.1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
        }

        .card-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .card-scientific {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-style: italic;
            font-weight: 300;
        }

        .card-details {
            display: grid;
            gap: 0.35rem;
            flex: 1;
        }

        .card-details p {
            font-size: 0.92rem;
            color: var(--text-secondary);
            line-height: 1.55;
            font-weight: 300;
        }

        .card-details strong {
            color: var(--green-deep);
            font-weight: 600;
        }

        .card-tip {
            background: #f7faf6;
            border: 1px solid rgba(45, 90, 61, 0.07);
            border-radius: 12px;
            padding: 0.75rem 0.85rem;
            font-size: 0.88rem;
            line-height: 1.55;
            color: var(--text-secondary);
            font-weight: 300;
        }

        .card-tip strong {
            color: var(--green-deep);
            font-weight: 600;
        }

        .no-results {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--text-secondary);
        }

        .no-results h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.35rem;
        }

        .page-footer {
            max-width: 1220px;
            margin: 1.3rem auto 0;
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.82rem;
            font-weight: 300;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 760px) {
            nav { padding: 1rem 4%; }
            .page { padding: 7rem 4% 2rem; }
            h1 { font-size: 2rem; }
            .filter-bar { padding: 1rem; }
            .search-row { flex-direction: column; }
            .plant-grid { grid-template-columns: 1fr; }
            .nav-right { gap: 0.35rem; }
            .nav-link, .btn { padding: 0.6rem 0.85rem; }
        }
    </style>
</head>

<body>

    <nav id="navbar">
        <a href="index.html" class="nav-logo">Earthly</a>
        <div class="nav-right">
            <a href="user-dashboard.php" class="nav-link">Dashboard</a>
            <a href="plant-catalog.php" class="nav-link active">Catalog</a>
            <a href="logout.php" class="btn btn-outline">Log out</a>
        </div>
    </nav>

    <main class="page">
        <div class="bg-shape-1"></div>
        <div class="bg-shape-2"></div>

        <section class="layout">
            <h1>Plant catalog</h1>

            <form class="filter-bar" method="GET" action="plant-catalog.php">
                <div class="search-row">
                    <input
                        type="text"
                        class="search-input"
                        name="search"
                        placeholder="Search plants by name..."
                        value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>

                <div class="category-pills">
                    <a class="pill <?= $category === 'all' ? 'active' : '' ?>" href="plant-catalog.php?category=all">All Plants</a>
                    <a class="pill <?= $category === 'Indoor' ? 'active' : '' ?>" href="plant-catalog.php?category=Indoor">Indoor</a>
                    <a class="pill <?= $category === 'Tropical' ? 'active' : '' ?>" href="plant-catalog.php?category=Tropical">Tropical</a>
                    <a class="pill <?= $category === 'Trailing' ? 'active' : '' ?>" href="plant-catalog.php?category=Trailing">Trailing</a>
                    <a class="pill <?= $category === 'Succulent' ? 'active' : '' ?>" href="plant-catalog.php?category=Succulent">Succulent</a>
                    <a class="pill <?= $category === 'Low-maintenance' ? 'active' : '' ?>" href="plant-catalog.php?category=Low-maintenance">Low-maintenance</a>
                </div>

                <p class="results-info">
                    <?= count($plants) > 0 ? 'Showing ' . count($plants) . ' plants' : 'No plants found' ?>
                </p>
            </form>

            <?php if (count($plants) > 0): ?>
                <div class="plant-grid">
                    <?php foreach ($plants as $plant): ?>
                        <a href="plant-details.php?id=<?= $plant['plant_id'] ?>" class="plant-card">
                            <div class="card-image-wrap">
                                <img src="images/<?= htmlspecialchars($plant['image']) ?>" alt="<?= htmlspecialchars($plant['common_name']) ?>">
                                <div class="card-badges">
                                    <span class="badge badge-category"><?= htmlspecialchars($plant['category']) ?></span>
                                    <span class="badge badge-difficulty"><?= htmlspecialchars($plant['difficulty']) ?></span>
                                    <?php if ($plant['pet_safe']): ?>
                                        <span class="badge badge-pet">Pet safe</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-body">
                                <div>
                                    <h3 class="card-name"><?= htmlspecialchars($plant['common_name']) ?></h3>
                                    <p class="card-scientific"><?= htmlspecialchars($plant['scientific_name']) ?></p>
                                </div>

                                <div class="card-details">
                                    <p><strong>Watering:</strong> <?= htmlspecialchars($plant['watering']) ?></p>
                                    <p><strong>Light:</strong> <?= htmlspecialchars($plant['light']) ?></p>
                                    <p><strong>Pet Safety:</strong> <?= $plant['pet_safe'] ? 'Safe for pets' : 'Not pet safe' ?></p>
                                </div>

                                <div class="card-tip">
                                    <strong>Tip:</strong> <?= htmlspecialchars($plant['tip']) ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <h3>No plants found</h3>
                    <p>Try a different search term or select another category.</p>
                </div>
            <?php endif; ?>
        </section>

        <p class="page-footer">© 2026 Earthly — Group 6, IT320 Section 77800, King Saud University</p>
    </main>

    <script>
        window.addEventListener('scroll', () => {
            document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
        });
    </script>

</body>
</html>
