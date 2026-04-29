<?php
require_once 'db.php';

$error = '';
$success = false;
$addedPlantName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commonName = trim($_POST['commonName'] ?? '');
    $scientificName = trim($_POST['scientificName'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $difficulty = trim($_POST['difficulty'] ?? '');
    $origin = trim($_POST['origin'] ?? '');
    $watering = trim($_POST['watering'] ?? '');
    $light = trim($_POST['light'] ?? '');
    $soil = trim($_POST['soilType'] ?? '');
    $temperature = trim($_POST['temperature'] ?? '');
    $petSafe = $_POST['petSafe'] ?? '';
    $commonProblems = trim($_POST['commonProblems'] ?? '');
    $tip = trim($_POST['careTip'] ?? '');
    $funFact = trim($_POST['funFact'] ?? '');

    if ($commonName === '' || $scientificName === '' || $category === '' || $watering === '' || $light === '') {
        $error = 'Please fill in all required plant details before submitting.';
    } else {
        $imagePath = 'images/default-plant.png';

        if (!empty($_FILES['plantImage']['name'])) {
            $uploadDir = 'images/';
            $fileName = time() . '_' . basename($_FILES['plantImage']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['plantImage']['tmp_name'], $targetPath)) {
                $imagePath = $targetPath;
            }
        }

        $petSafeValue = ($petSafe === 'Pet safe') ? 1 : 0;

        $stmt = $pdo->prepare("
            INSERT INTO plant 
            (common_name, scientific_name, category, difficulty, origin, watering, light, soil, temperature, humidity, size, pet_safe, about, tip, fun_fact, image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $commonName,
            $scientificName,
            $category,
            $difficulty,
            $origin,
            $watering,
            $light,
            $soil,
            $temperature,
            '',
            '',
            $petSafeValue,
            '',
            $tip,
            $funFact,
            $imagePath
        ]);

        $plantId = $pdo->lastInsertId();

        if ($commonProblems !== '') {
            $problems = explode(',', $commonProblems);

            foreach ($problems as $problem) {
                $problemName = trim($problem);

                if ($problemName !== '') {
                    $stmt = $pdo->prepare("
                        INSERT INTO plant_problem (plant_id, problem_name, problem_desc)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$plantId, $problemName, '']);
                }
            }
        }

        $success = true;
        $addedPlantName = $commonName;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add Plant — Earthly</title>

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
            --danger: #B54A4A;
            --danger-bg: #FDECEC;
            --success: #2E6B43;
            --success-bg: #EAF5EC;
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
        button, input, select, textarea { font-family: inherit; }

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
            padding: 0.72rem 1.3rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
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
            margin-bottom: 0.7rem;
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

        .content-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 1.2rem;
            align-items: start;
        }

        .section-card {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-soft);
            border-radius: 22px;
            box-shadow: var(--shadow-lg);
            padding: 1.4rem;
        }

        .section-head { margin-bottom: 1.2rem; }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.65rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.3rem;
        }

        .section-desc {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            font-weight: 300;
        }

        .message {
            border-radius: 14px;
            padding: 0.95rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.93rem;
            line-height: 1.55;
        }

        .message.error {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid #F3C8C8;
        }

        form {
            display: grid;
            gap: 1rem;
        }

        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.9rem;
        }

        .form-group {
            display: grid;
            gap: 0.42rem;
        }

        label {
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        input, select, textarea {
            width: 100%;
            padding: 0.95rem 1rem;
            border-radius: 10px;
            border: 1px solid rgba(45, 90, 61, 0.15);
            background: rgba(255, 255, 255, 0.92);
            font-size: 0.96rem;
            color: var(--text-primary);
            outline: none;
            transition: all 0.25s ease;
        }

        textarea {
            min-height: 115px;
            resize: vertical;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--green-mid);
            box-shadow: 0 0 0 4px rgba(74, 124, 92, 0.12);
            background: var(--white);
        }

        .form-note {
            margin-top: -0.2rem;
            font-size: 0.84rem;
            color: var(--text-secondary);
            font-weight: 300;
        }

        .actions-row {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 0.2rem;
        }

        .preview-card { display: grid; gap: 1rem; }

        .preview-plant {
            border-radius: 20px;
            overflow: hidden;
            background: var(--white);
            border: 1px solid rgba(45, 90, 61, 0.08);
            box-shadow: var(--shadow-sm);
        }

        .preview-image-wrap {
            height: 210px;
            background: linear-gradient(180deg, #edf4ea, #f8fbf7);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .preview-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .preview-placeholder {
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 300;
        }

        .preview-body { padding: 1rem; }

        .preview-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.15rem;
        }

        .preview-scientific {
            font-size: 0.92rem;
            color: var(--text-secondary);
            font-style: italic;
            font-weight: 300;
            margin-bottom: 0.8rem;
        }

        .preview-meta {
            display: grid;
            gap: 0.45rem;
            font-size: 0.94rem;
            color: var(--text-secondary);
            line-height: 1.55;
            font-weight: 300;
        }

        .preview-meta strong {
            color: var(--green-deep);
            font-weight: 600;
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

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 999;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(6px);
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.visible { display: flex; }

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
            background: var(--success-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            font-size: 2rem;
            color: var(--success);
            font-weight: 700;
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

        .modal-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 2rem;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            background: var(--green-deep);
            color: var(--text-light);
            transition: all 0.25s ease;
            margin-bottom: 1rem;
        }

        .modal-redirect {
            font-size: 0.84rem;
            color: var(--text-secondary);
            font-weight: 300;
        }

        .modal-redirect span {
            font-weight: 600;
            color: var(--green-deep);
        }

        @media (max-width: 760px) {
            nav { padding: 1rem 4%; }
            .page { padding: 7rem 4% 2rem; }
            h1 { font-size: 2rem; }
            .content-grid { grid-template-columns: 1fr; }
            .field-row { grid-template-columns: 1fr; }
            .nav-right { gap: 0.35rem; }
            .nav-link, .btn { padding: 0.6rem 0.85rem; }
        }
    </style>
</head>

<body>

    <nav id="navbar">
        <a href="index.html" class="nav-logo">Earthly</a>

        <div class="nav-right">
            <a href="admin-dashboard.php" class="nav-link active">Admin Dashboard</a>
            <a href="logout.php" class="btn btn-outline">Log out</a>
        </div>
    </nav>

    <div class="modal-overlay <?= $success ? 'visible' : '' ?>" id="successModal">
        <div class="modal-box">
            <div class="modal-icon">✓</div>
            <h2 class="modal-title">Plant Added!</h2>
            <p class="modal-desc">
                <strong id="modalPlantName"><?= htmlspecialchars($addedPlantName ?: 'Your plant') ?></strong>
                has been successfully added to the catalog.
            </p>
            <button class="modal-btn" id="modalGoBtn">Go to Admin Dashboard</button>
            <p class="modal-redirect">Redirecting automatically in <span id="countdown">5</span>s</p>
        </div>
    </div>

    <main class="page">
        <div class="bg-shape-1"></div>
        <div class="bg-shape-2"></div>

        <section class="layout">
            <div class="breadcrumb">
                <a href="admin-dashboard.php">Admin Dashboard</a>
                <span class="sep">›</span>
                <span>Add Plant</span>
            </div>

            <h1>Add a new plant to the catalog</h1>

            <section class="content-grid">
                <section class="section-card">
                    <div class="section-head">
                        <h2 class="section-title">Plant details</h2>
                        <p class="section-desc">
                            Fill in the main catalog fields below. Required fields are enough for the current phase.
                        </p>
                    </div>

                    <?php if ($error): ?>
                        <div class="message error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form id="addPlantForm" method="POST" action="add-plant.php" enctype="multipart/form-data">
                        <div class="field-row">
                            <div class="form-group">
                                <label for="commonName">Common Name</label>
                                <input type="text" id="commonName" name="commonName" placeholder="e.g. Snake Plant">
                            </div>

                            <div class="form-group">
                                <label for="scientificName">Scientific Name</label>
                                <input type="text" id="scientificName" name="scientificName" placeholder="e.g. Dracaena trifasciata">
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category">
                                    <option value="">Select a category</option>
                                    <option>Indoor plant</option>
                                    <option>Tropical plant</option>
                                    <option>Trailing plant</option>
                                    <option>Succulent</option>
                                    <option>Low-maintenance plant</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="difficulty">Difficulty Level</label>
                                <select id="difficulty" name="difficulty">
                                    <option value="">Select difficulty</option>
                                    <option>Beginner-friendly</option>
                                    <option>Intermediate</option>
                                    <option>Advanced</option>
                                </select>
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="form-group">
                                <label for="origin">Origin</label>
                                <input type="text" id="origin" name="origin" placeholder="e.g. West Africa">
                            </div>

                            <div class="form-group">
                                <label for="plantImage">Plant Image</label>
                                <input type="file" id="plantImage" name="plantImage" accept="image/*">
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="form-group">
                                <label for="watering">Watering Frequency</label>
                                <input type="text" id="watering" name="watering" placeholder="e.g. Every 7–10 days">
                            </div>

                            <div class="form-group">
                                <label for="light">Light Requirements</label>
                                <input type="text" id="light" name="light" placeholder="e.g. Bright indirect light">
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="form-group">
                                <label for="soilType">Soil Type</label>
                                <input type="text" id="soilType" name="soilType" placeholder="e.g. Well-draining potting mix">
                            </div>

                            <div class="form-group">
                                <label for="temperature">Temperature</label>
                                <input type="text" id="temperature" name="temperature" placeholder="e.g. 18–27°C">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="petSafe">Pet Safety</label>
                            <select id="petSafe" name="petSafe">
                                <option value="">Select pet safety</option>
                                <option>Pet safe</option>
                                <option>Not pet safe</option>
                                <option>Unknown</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="commonProblems">Common Problems</label>
                            <textarea id="commonProblems" name="commonProblems" placeholder="e.g. Yellow leaves, root rot, brown tips"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="careTip">Short Care Tip</label>
                            <textarea id="careTip" name="careTip" placeholder="Write a short, simple tip for beginner users."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="funFact">Fun Fact</label>
                            <textarea id="funFact" name="funFact" placeholder="e.g. Snake Plants convert CO₂ to oxygen at night."></textarea>
                        </div>

                        <p class="form-note">
                            Keep the wording short, practical, and easy for beginners to follow.
                        </p>

                        <div class="actions-row">
                            <button type="submit" class="btn btn-primary">Add Plant</button>
                            <a href="admin-dashboard.php" class="btn btn-outline">Back to Admin Dashboard</a>
                        </div>
                    </form>
                </section>

                <section class="section-card">
                    <div class="section-head">
                        <h2 class="section-title">Live preview</h2>
                        <p class="section-desc">
                            A simple preview of how the new plant entry could appear in the catalog.
                        </p>
                    </div>

                    <div class="preview-card">
                        <article class="preview-plant">
                            <div class="preview-image-wrap">
                                <img id="previewImage" alt="Plant preview">
                                <span id="previewPlaceholder" class="preview-placeholder">Plant image preview</span>
                            </div>

                            <div class="preview-body">
                                <h3 class="preview-name" id="previewName">New Plant</h3>
                                <p class="preview-scientific" id="previewScientific">Scientific name</p>

                                <div class="preview-meta">
                                    <span><strong>Category:</strong> <span id="previewCategory">—</span></span>
                                    <span><strong>Difficulty:</strong> <span id="previewDifficulty">—</span></span>
                                    <span><strong>Origin:</strong> <span id="previewOrigin">—</span></span>
                                    <span><strong>Watering:</strong> <span id="previewWatering">—</span></span>
                                    <span><strong>Light:</strong> <span id="previewLight">—</span></span>
                                    <span><strong>Soil Type:</strong> <span id="previewSoilType">—</span></span>
                                    <span><strong>Temperature:</strong> <span id="previewTemperature">—</span></span>
                                    <span><strong>Pet Safety:</strong> <span id="previewPetSafe">—</span></span>
                                    <span><strong>Common Problems:</strong> <span id="previewCommonProblems">—</span></span>
                                    <span><strong>Tip:</strong> <span id="previewTip">A short care tip will appear here.</span></span>
                                    <span><strong>Fun Fact:</strong> <span id="previewFunFact">—</span></span>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
            </section>
        </section>

        <p class="page-footer">© 2026 Earthly — Group 6, IT320 Section 77800, King Saud University</p>
    </main>

    <script>
        window.addEventListener('scroll', () => {
            document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
        });

        const commonName = document.getElementById('commonName');
        const scientificName = document.getElementById('scientificName');
        const category = document.getElementById('category');
        const difficulty = document.getElementById('difficulty');
        const origin = document.getElementById('origin');
        const watering = document.getElementById('watering');
        const light = document.getElementById('light');
        const soilType = document.getElementById('soilType');
        const temperature = document.getElementById('temperature');
        const petSafe = document.getElementById('petSafe');
        const plantImage = document.getElementById('plantImage');
        const commonProblems = document.getElementById('commonProblems');
        const careTip = document.getElementById('careTip');
        const funFact = document.getElementById('funFact');

        const previewName = document.getElementById('previewName');
        const previewScientific = document.getElementById('previewScientific');
        const previewCategory = document.getElementById('previewCategory');
        const previewDifficulty = document.getElementById('previewDifficulty');
        const previewOrigin = document.getElementById('previewOrigin');
        const previewWatering = document.getElementById('previewWatering');
        const previewLight = document.getElementById('previewLight');
        const previewSoilType = document.getElementById('previewSoilType');
        const previewTemperature = document.getElementById('previewTemperature');
        const previewPetSafe = document.getElementById('previewPetSafe');
        const previewCommonProblems = document.getElementById('previewCommonProblems');
        const previewTip = document.getElementById('previewTip');
        const previewFunFact = document.getElementById('previewFunFact');
        const previewImage = document.getElementById('previewImage');
        const previewPlaceholder = document.getElementById('previewPlaceholder');

        function updatePreview() {
            previewName.textContent = commonName.value.trim() || 'New Plant';
            previewScientific.textContent = scientificName.value.trim() || 'Scientific name';
            previewCategory.textContent = category.value || '—';
            previewDifficulty.textContent = difficulty.value || '—';
            previewOrigin.textContent = origin.value.trim() || '—';
            previewWatering.textContent = watering.value.trim() || '—';
            previewLight.textContent = light.value.trim() || '—';
            previewSoilType.textContent = soilType.value.trim() || '—';
            previewTemperature.textContent = temperature.value.trim() || '—';
            previewPetSafe.textContent = petSafe.value || '—';
            previewCommonProblems.textContent = commonProblems.value.trim() || '—';
            previewTip.textContent = careTip.value.trim() || '—';
            previewFunFact.textContent = funFact.value.trim() || '—';

            const file = plantImage.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';
                    previewPlaceholder.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                previewImage.removeAttribute('src');
                previewImage.style.display = 'none';
                previewPlaceholder.style.display = 'block';
            }
        }

        [
            commonName,
            scientificName,
            category,
            difficulty,
            origin,
            watering,
            light,
            soilType,
            temperature,
            petSafe,
            commonProblems,
            careTip,
            funFact
        ].forEach(field => field.addEventListener('input', updatePreview));

        plantImage.addEventListener('change', updatePreview);
        updatePreview();

        const successModal = document.getElementById('successModal');
        const modalGoBtn = document.getElementById('modalGoBtn');
        const countdownEl = document.getElementById('countdown');

        if (successModal.classList.contains('visible')) {
            let seconds = 5;
            countdownEl.textContent = seconds;

            const timer = setInterval(() => {
                seconds--;
                countdownEl.textContent = seconds;

                if (seconds <= 0) {
                    clearInterval(timer);
                    window.location.href = 'admin-dashboard.php';
                }
            }, 1000);
        }

        modalGoBtn.addEventListener('click', () => {
            window.location.href = 'admin-dashboard.php';
        });
    </script>

</body>
</html>
