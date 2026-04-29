<?php
require_once 'admin_auth.php';
require_once 'db.php';

$error = '';
$success = false;
$updatedPlantName = '';

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function plantImageSrc($image) {
    $image = trim((string) $image);

    if ($image === '') {
        return 'images/default-plant.png';
    }

    if (str_starts_with($image, 'images/')) {
        return $image;
    }

    return 'images/' . $image;
}

function buildProblemText($problems) {
    return implode(', ', array_map(fn($p) => $p['problem_name'], $problems));
}

function buildProblemDescriptionText($problems) {
    return implode("\n", array_map(fn($p) => $p['problem_desc'], $problems));
}

/* ── Load all plants for selector ── */
$stmt = $pdo->prepare("SELECT plant_id, common_name, scientific_name FROM plant ORDER BY common_name");
$stmt->execute();
$plants = $stmt->fetchAll();

$selectedPlant = null;
$selectedProblems = [];

/* ── Update plant ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['plant_id'] ?? '';

    $common_name = trim($_POST['common_name'] ?? '');
    $scientific_name = trim($_POST['scientific_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $difficulty = trim($_POST['difficulty'] ?? '');
    $origin = trim($_POST['origin'] ?? '');
    $watering = trim($_POST['watering'] ?? '');
    $light = trim($_POST['light'] ?? '');
    $soil = trim($_POST['soil'] ?? '');
    $temperature = trim($_POST['temperature'] ?? '');
    $humidity = trim($_POST['humidity'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $pet_safe = ($_POST['pet_safe'] ?? '0') === '1' ? 1 : 0;
    $about = trim($_POST['about'] ?? '');
    $tip = trim($_POST['tip'] ?? '');
    $fun_fact = trim($_POST['fun_fact'] ?? '');
    $commonProblems = trim($_POST['common_problems'] ?? '');
    $problemDescriptionsText = trim($_POST['problem_descriptions'] ?? '');

    if ($id === '' || $common_name === '' || $scientific_name === '' || $category === '' || $watering === '' || $light === '') {
        $error = 'Please fill in all required plant details before saving.';
    } else {
        try {
            $pdo->beginTransaction();

            if (!empty($_FILES['image']['name'])) {
                $uploadDir = 'images/';
                $imageName = time() . '_' . basename($_FILES['image']['name']);
                $targetPath = $uploadDir . $imageName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $stmt = $pdo->prepare("
                        UPDATE plant SET
                            common_name = ?,
                            scientific_name = ?,
                            category = ?,
                            difficulty = ?,
                            origin = ?,
                            watering = ?,
                            light = ?,
                            soil = ?,
                            temperature = ?,
                            humidity = ?,
                            size = ?,
                            pet_safe = ?,
                            about = ?,
                            tip = ?,
                            fun_fact = ?,
                            image = ?
                        WHERE plant_id = ?
                    ");

                    $stmt->execute([
                        $common_name,
                        $scientific_name,
                        $category,
                        $difficulty,
                        $origin,
                        $watering,
                        $light,
                        $soil,
                        $temperature,
                        $humidity,
                        $size,
                        $pet_safe,
                        $about,
                        $tip,
                        $fun_fact,
                        $imageName,
                        $id
                    ]);
                } else {
                    throw new Exception('Image upload failed. Please try again.');
                }
            } else {
                $stmt = $pdo->prepare("
                    UPDATE plant SET
                        common_name = ?,
                        scientific_name = ?,
                        category = ?,
                        difficulty = ?,
                        origin = ?,
                        watering = ?,
                        light = ?,
                        soil = ?,
                        temperature = ?,
                        humidity = ?,
                        size = ?,
                        pet_safe = ?,
                        about = ?,
                        tip = ?,
                        fun_fact = ?
                    WHERE plant_id = ?
                ");

                $stmt->execute([
                    $common_name,
                    $scientific_name,
                    $category,
                    $difficulty,
                    $origin,
                    $watering,
                    $light,
                    $soil,
                    $temperature,
                    $humidity,
                    $size,
                    $pet_safe,
                    $about,
                    $tip,
                    $fun_fact,
                    $id
                ]);
            }

            /* Replace problem records for this plant */
            $stmt = $pdo->prepare("DELETE FROM plant_problem WHERE plant_id = ?");
            $stmt->execute([$id]);

            if ($commonProblems !== '') {
                $problems = array_map('trim', explode(',', $commonProblems));
                $problemDescriptions = preg_split("/\r\n|\n|\r/", $problemDescriptionsText);

                $insertProblem = $pdo->prepare("
                    INSERT INTO plant_problem (plant_id, problem_name, problem_desc)
                    VALUES (?, ?, ?)
                ");

                foreach ($problems as $index => $problemName) {
                    if ($problemName === '') {
                        continue;
                    }

                    $problemDesc = trim($problemDescriptions[$index] ?? '');
                    $insertProblem->execute([$id, $problemName, $problemDesc]);
                }
            }

            $pdo->commit();

            $success = true;
            $updatedPlantName = $common_name;

            /* Refresh dropdown after update */
            $stmt = $pdo->prepare("SELECT plant_id, common_name, scientific_name FROM plant ORDER BY common_name");
            $stmt->execute();
            $plants = $stmt->fetchAll();

            $_GET['id'] = $id;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'Could not update the plant. Please try again.';
        }
    }
}

/* ── Load selected plant ── */
if (isset($_GET['id']) && $_GET['id'] !== '') {
    $stmt = $pdo->prepare("SELECT * FROM plant WHERE plant_id = ?");
    $stmt->execute([$_GET['id']]);
    $selectedPlant = $stmt->fetch();

    if ($selectedPlant) {
        $stmt = $pdo->prepare("SELECT * FROM plant_problem WHERE plant_id = ? ORDER BY problem_id");
        $stmt->execute([$selectedPlant['plant_id']]);
        $selectedProblems = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Plant — Earthly</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap"
        rel="stylesheet">

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
            --danger: #B54A4A;
            --danger-bg: #FDECEC;
            --success: #2E6B43;
            --success-bg: #EAF5EC;
            --border-soft: rgba(45, 90, 61, 0.10);
            --shadow-sm: 0 2px 8px rgba(45, 90, 61, 0.08);
            --shadow-md: 0 4px 20px rgba(45, 90, 61, 0.12);
            --shadow-lg: 0 8px 40px rgba(45, 90, 61, 0.16);
            --radius: 12px;
            --radius-lg: 20px;
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

        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }

        /* ── Breadcrumb ── */
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
            transition: opacity 0.2s;
        }

        .breadcrumb a:hover {
            opacity: 0.7;
        }

        .breadcrumb .sep {
            color: var(--text-secondary);
            opacity: 0.5;
        }

        /* ── Nav ── */
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

        /* ── Buttons ── */
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

        .btn-danger {
            background: transparent;
            color: var(--danger);
            border: 1.5px solid var(--danger);
        }

        .btn-danger:hover {
            background: var(--danger);
            color: var(--white);
        }

        .btn-soft {
            background: var(--green-wash);
            color: var(--green-deep);
        }

        .btn-soft:hover {
            background: #e3f0df;
        }

        /* ── Page ── */
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

        .content-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 1.2rem;
            align-items: start;
        }

        /* ── Cards ── */
        .section-card {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-soft);
            border-radius: 22px;
            box-shadow: var(--shadow-lg);
            padding: 1.4rem;
        }

        .section-head {
            margin-bottom: 1.2rem;
        }

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

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.4rem;
            line-height: 1.1;
            font-weight: 700;
            margin-bottom: 0.7rem;
        }

        /* ── Messages ── */
        .message {
            display: none;
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

        /* ── Form ── */
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

        input,
        select,
        textarea {
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

        input:focus,
        select:focus,
        textarea:focus {
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

        /* ── Plant Selector ── */
        .selector-card {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-soft);
            border-radius: 22px;
            box-shadow: var(--shadow-lg);
            padding: 1.4rem;
        }

        .plant-selector-wrap {
            display: flex;
            gap: 0.9rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .plant-selector-wrap .form-group {
            flex: 1;
            min-width: 220px;
        }

        .plant-selector-wrap .btn {
            margin-bottom: 1px;
        }

        /* ── Edit Form Area (hidden until plant selected) ── */
        .edit-area {
            display: none;
        }

        .edit-area.visible {
            display: grid;
            gap: 1.2rem;
        }

        /* ── Empty State ── */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: var(--green-wash);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-state-icon svg {
            width: 32px;
            height: 32px;
            stroke: var(--green-mid);
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .empty-state h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.35rem;
        }

        .empty-state p {
            font-size: 0.93rem;
            font-weight: 300;
            line-height: 1.6;
        }

        /* ── Preview ── */
        .preview-card {
            display: grid;
            gap: 1rem;
        }

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

        .preview-body {
            padding: 1rem;
        }

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

        /* ── Change Indicator Badge ── */
        .change-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            background: #FFF8E6;
            border: 1px solid #F0DFA0;
            font-size: 0.8rem;
            font-weight: 500;
            color: #8B7020;
            margin-bottom: 0.8rem;
        }

        .change-badge svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* ── Popup Modals ── */
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
            animation: overlayIn 0.3s ease;
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
            animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
        }

        .modal-icon.success-icon {
            background: var(--success-bg);
        }

        .modal-icon.danger-icon {
            background: var(--danger-bg);
        }

        .modal-icon svg {
            width: 36px;
            height: 36px;
            stroke-width: 2.5;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .modal-icon.success-icon svg {
            stroke: var(--success);
        }

        .modal-icon.danger-icon svg {
            stroke: var(--danger);
        }

        .modal-icon svg .check-path {
            stroke-dasharray: 40;
            stroke-dashoffset: 40;
            animation: drawCheck 0.5s ease 0.3s forwards;
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

        .modal-redirect {
            font-size: 0.84rem;
            color: var(--text-secondary);
            font-weight: 300;
        }

        .modal-redirect span {
            font-weight: 600;
            color: var(--green-deep);
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
            transition: all 0.25s ease;
            margin-bottom: 1rem;
        }

        .modal-btn.primary {
            background: var(--green-deep);
            color: var(--text-light);
        }

        .modal-btn.primary:hover {
            background: #1E4A2E;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .modal-btn.danger {
            background: var(--danger);
            color: var(--white);
        }

        .modal-btn.danger:hover {
            background: #993D3D;
            transform: translateY(-1px);
        }

        .modal-btn.ghost {
            background: transparent;
            color: var(--text-secondary);
        }

        .modal-btn.ghost:hover {
            background: var(--green-wash);
            color: var(--green-deep);
        }

        .modal-actions {
            display: flex;
            gap: 0.6rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }

        /* ── Footer ── */
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

        @keyframes overlayIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes drawCheck {
            to {
                stroke-dashoffset: 0;
            }
        }
    
        .message {
            display: block;
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

    <!-- Success Modal -->
    <div class="modal-overlay <?= $success ? 'visible' : '' ?>" id="successModal">
        <div class="modal-box">
            <div class="modal-icon success-icon">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.3" />
                    <polyline points="7 13 10 16 17 9" class="check-path" />
                </svg>
            </div>
            <h2 class="modal-title">Changes Saved!</h2>
            <p class="modal-desc">
                <strong id="modalPlantName"><?= h($updatedPlantName ?: 'Plant') ?></strong>
                has been updated successfully in the catalog.
            </p>
            <button class="modal-btn primary" id="modalGoBtn">Go to Admin Dashboard</button>
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
                <span><?= $selectedPlant ? 'Edit — ' . h($selectedPlant['common_name']) : 'Edit Plant' ?></span>
            </div>

            <h1>Edit plant information</h1>

            <!-- Plant Selector -->
            <div class="selector-card">
                <div class="section-head">
                    <h2 class="section-title">Select a plant</h2>
                    <p class="section-desc">Choose a plant from the catalog to view and update its information.</p>
                </div>

                <form method="GET" action="edit-plant.php" class="plant-selector-wrap">
                    <div class="form-group">
                        <label for="plantSelect">Plant</label>
                        <select id="plantSelect" name="id" required>
                            <option value="">— Choose a plant —</option>
                            <?php foreach ($plants as $p): ?>
                                <option value="<?= h($p['plant_id']) ?>" <?= ($selectedPlant && $selectedPlant['plant_id'] == $p['plant_id']) ? 'selected' : '' ?>>
                                    <?= h($p['common_name']) ?> — <?= h($p['scientific_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">Load Plant</button>
                </form>
            </div>

            <?php if (!$selectedPlant): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"
                                stroke-opacity="0.3" />
                            <path d="M12 8c-2.2 0-4 1.8-4 4m4-4c2.2 0 4 1.8 4 4m-4-4v0m-4 4h8" />
                            <path d="M9 16c.8.8 1.8 1.2 3 1.2s2.2-.4 3-1.2" />
                        </svg>
                    </div>
                    <h3>No plant selected</h3>
                    <p>Select a plant from the dropdown above to start editing its details.</p>
                </div>
            <?php else: ?>
                <!-- Edit Area -->
                <div class="edit-area visible" id="editArea">
                    <section class="content-grid">
                        <section class="section-card">
                            <div class="section-head">
                                <h2 class="section-title">Plant details</h2>
                                <p class="section-desc">
                                    Update the fields below. Changes will be saved when you click "Save Changes".
                                </p>
                            </div>

                            <div id="changeBadge" class="change-badge" style="display:none;">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="12" y1="8" x2="12" y2="12" />
                                    <line x1="12" y1="16" x2="12.01" y2="16" />
                                </svg>
                                <span>You have unsaved changes</span>
                            </div>

                            <?php if ($error): ?>
                                <div class="message error"><?= h($error) ?></div>
                            <?php endif; ?>

                            <form id="editPlantForm" method="POST" action="edit-plant.php?id=<?= h($selectedPlant['plant_id']) ?>" enctype="multipart/form-data">
                                <input type="hidden" name="plant_id" value="<?= h($selectedPlant['plant_id']) ?>">

                                <div class="field-row">
                                    <div class="form-group">
                                        <label for="editCommonName">Common Name</label>
                                        <input type="text" id="editCommonName" name="common_name" value="<?= h($selectedPlant['common_name']) ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="editScientificName">Scientific Name</label>
                                        <input type="text" id="editScientificName" name="scientific_name" value="<?= h($selectedPlant['scientific_name']) ?>" required>
                                    </div>
                                </div>

                                <div class="field-row">
                                    <div class="form-group">
                                        <label for="editCategory">Category</label>
                                        <select id="editCategory" name="category" required>
                                            <?php
                                            $categories = ['Indoor', 'Succulent', 'Trailing', 'Tropical', 'Low-maintenance'];
                                            $currentCategory = $selectedPlant['category'] ?? '';
                                            foreach ($categories as $cat):
                                            ?>
                                                <option value="<?= h($cat) ?>" <?= $currentCategory === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="editDifficulty">Difficulty Level</label>
                                        <select id="editDifficulty" name="difficulty">
                                            <?php
                                            $difficulties = ['Easy', 'Medium', 'Hard', 'Beginner-friendly', 'Intermediate', 'Advanced'];
                                            $currentDifficulty = $selectedPlant['difficulty'] ?? '';
                                            foreach ($difficulties as $level):
                                            ?>
                                                <option value="<?= h($level) ?>" <?= $currentDifficulty === $level ? 'selected' : '' ?>><?= h($level) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="field-row">
                                    <div class="form-group">
                                        <label for="editOrigin">Origin</label>
                                        <input type="text" id="editOrigin" name="origin" value="<?= h($selectedPlant['origin']) ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="editPlantImage">Replace Plant Image</label>
                                        <input type="file" id="editPlantImage" name="image" accept="image/*">
                                        <p class="form-note">Leave empty to keep the current image.</p>
                                    </div>
                                </div>

                                <div class="field-row">
                                    <div class="form-group">
                                        <label for="editWatering">Watering Frequency</label>
                                        <input type="text" id="editWatering" name="watering" value="<?= h($selectedPlant['watering']) ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="editLight">Light Requirements</label>
                                        <input type="text" id="editLight" name="light" value="<?= h($selectedPlant['light']) ?>" required>
                                    </div>
                                </div>

                                <div class="field-row">
                                    <div class="form-group">
                                        <label for="editSoilType">Soil Type</label>
                                        <input type="text" id="editSoilType" name="soil" value="<?= h($selectedPlant['soil']) ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="editTemperature">Temperature</label>
                                        <input type="text" id="editTemperature" name="temperature" value="<?= h($selectedPlant['temperature']) ?>">
                                    </div>
                                </div>

                                <div class="field-row">
                                    <div class="form-group">
                                        <label for="editHumidity">Humidity</label>
                                        <input type="text" id="editHumidity" name="humidity" value="<?= h($selectedPlant['humidity']) ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="editSize">Size</label>
                                        <input type="text" id="editSize" name="size" value="<?= h($selectedPlant['size']) ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="editPetSafe">Pet Safety</label>
                                    <select id="editPetSafe" name="pet_safe">
                                        <option value="0" <?= (int)$selectedPlant['pet_safe'] === 0 ? 'selected' : '' ?>>Not pet safe</option>
                                        <option value="1" <?= (int)$selectedPlant['pet_safe'] === 1 ? 'selected' : '' ?>>Pet safe</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="editAbout">About</label>
                                    <textarea id="editAbout" name="about"><?= h($selectedPlant['about']) ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="editCommonProblems">Common Problems</label>
                                    <textarea id="editCommonProblems" name="common_problems"><?= h(buildProblemText($selectedProblems)) ?></textarea>
                                    <p class="form-note">Separate problem names with commas.</p>
                                </div>

                                <div class="form-group">
                                    <label for="editProblemDescriptions">Problem Descriptions</label>
                                    <textarea id="editProblemDescriptions" name="problem_descriptions"><?= h(buildProblemDescriptionText($selectedProblems)) ?></textarea>
                                    <p class="form-note">Write one description per line, in the same order as the problem names.</p>
                                </div>

                                <div class="form-group">
                                    <label for="editCareTip">Short Care Tip</label>
                                    <textarea id="editCareTip" name="tip"><?= h($selectedPlant['tip']) ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="editFunFact">Fun Fact</label>
                                    <textarea id="editFunFact" name="fun_fact"><?= h($selectedPlant['fun_fact']) ?></textarea>
                                </div>

                                <div class="actions-row">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                    <a href="admin-dashboard.php" class="btn btn-outline">Back to Admin Dashboard</a>
                                </div>
                            </form>
                        </section>

                        <section class="section-card">
                            <div class="section-head">
                                <h2 class="section-title">Live preview</h2>
                                <p class="section-desc">
                                    See how the updated plant entry will look in the catalog.
                                </p>
                            </div>

                            <div class="preview-card">
                                <article class="preview-plant">
                                    <div class="preview-image-wrap">
                                        <img id="previewImage" src="<?= h(plantImageSrc($selectedPlant['image'])) ?>" alt="Plant preview" style="display:block;">
                                        <span id="previewPlaceholder" class="preview-placeholder" style="display:none;">Plant image preview</span>
                                    </div>
                                    <div class="preview-body">
                                        <h3 class="preview-name" id="previewName"><?= h($selectedPlant['common_name']) ?></h3>
                                        <p class="preview-scientific" id="previewScientific"><?= h($selectedPlant['scientific_name']) ?></p>
                                        <div class="preview-meta">
                                            <span><strong>Category:</strong> <span id="previewCategory"><?= h($selectedPlant['category']) ?></span></span>
                                            <span><strong>Difficulty:</strong> <span id="previewDifficulty"><?= h($selectedPlant['difficulty']) ?></span></span>
                                            <span><strong>Origin:</strong> <span id="previewOrigin"><?= h($selectedPlant['origin']) ?></span></span>
                                            <span><strong>Watering:</strong> <span id="previewWatering"><?= h($selectedPlant['watering']) ?></span></span>
                                            <span><strong>Light:</strong> <span id="previewLight"><?= h($selectedPlant['light']) ?></span></span>
                                            <span><strong>Soil Type:</strong> <span id="previewSoilType"><?= h($selectedPlant['soil']) ?></span></span>
                                            <span><strong>Temperature:</strong> <span id="previewTemperature"><?= h($selectedPlant['temperature']) ?></span></span>
                                            <span><strong>Humidity:</strong> <span id="previewHumidity"><?= h($selectedPlant['humidity']) ?></span></span>
                                            <span><strong>Size:</strong> <span id="previewSize"><?= h($selectedPlant['size']) ?></span></span>
                                            <span><strong>Pet Safety:</strong> <span id="previewPetSafe"><?= (int)$selectedPlant['pet_safe'] === 1 ? 'Pet safe' : 'Not pet safe' ?></span></span>
                                            <span><strong>Common Problems:</strong> <span id="previewCommonProblems"><?= h(buildProblemText($selectedProblems)) ?></span></span>
                                            <span><strong>Tip:</strong> <span id="previewTip"><?= h($selectedPlant['tip']) ?></span></span>
                                            <span><strong>Fun Fact:</strong> <span id="previewFunFact"><?= h($selectedPlant['fun_fact']) ?></span></span>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </section>
                </div>
            <?php endif; ?>
        </section>

        <p class="page-footer">© 2026 Earthly — Group 6, IT320 Section 77800, King Saud University</p>
    </main>

    <script>
        window.addEventListener('scroll', () => {
            document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
        });

        const successModal = document.getElementById('successModal');
        const modalGoBtn = document.getElementById('modalGoBtn');
        const countdownEl = document.getElementById('countdown');
        let countdownTimer = null;

        if (successModal && successModal.classList.contains('visible')) {
            let seconds = 5;
            countdownEl.textContent = seconds;

            countdownTimer = setInterval(() => {
                seconds--;
                countdownEl.textContent = seconds;

                if (seconds <= 0) {
                    clearInterval(countdownTimer);
                    window.location.href = 'admin-dashboard.php';
                }
            }, 1000);
        }

        if (modalGoBtn) {
            modalGoBtn.addEventListener('click', () => {
                if (countdownTimer) clearInterval(countdownTimer);
                window.location.href = 'admin-dashboard.php';
            });
        }

        <?php if ($selectedPlant): ?>
        const changeBadge = document.getElementById('changeBadge');
        const form = document.getElementById('editPlantForm');

        const fields = {
            commonName: document.getElementById('editCommonName'),
            scientificName: document.getElementById('editScientificName'),
            category: document.getElementById('editCategory'),
            difficulty: document.getElementById('editDifficulty'),
            origin: document.getElementById('editOrigin'),
            watering: document.getElementById('editWatering'),
            light: document.getElementById('editLight'),
            soilType: document.getElementById('editSoilType'),
            temperature: document.getElementById('editTemperature'),
            humidity: document.getElementById('editHumidity'),
            size: document.getElementById('editSize'),
            petSafe: document.getElementById('editPetSafe'),
            about: document.getElementById('editAbout'),
            commonProblems: document.getElementById('editCommonProblems'),
            problemDescriptions: document.getElementById('editProblemDescriptions'),
            careTip: document.getElementById('editCareTip'),
            funFact: document.getElementById('editFunFact'),
            image: document.getElementById('editPlantImage')
        };

        const preview = {
            name: document.getElementById('previewName'),
            scientific: document.getElementById('previewScientific'),
            category: document.getElementById('previewCategory'),
            difficulty: document.getElementById('previewDifficulty'),
            origin: document.getElementById('previewOrigin'),
            watering: document.getElementById('previewWatering'),
            light: document.getElementById('previewLight'),
            soilType: document.getElementById('previewSoilType'),
            temperature: document.getElementById('previewTemperature'),
            humidity: document.getElementById('previewHumidity'),
            size: document.getElementById('previewSize'),
            petSafe: document.getElementById('previewPetSafe'),
            commonProblems: document.getElementById('previewCommonProblems'),
            tip: document.getElementById('previewTip'),
            funFact: document.getElementById('previewFunFact'),
            image: document.getElementById('previewImage'),
            placeholder: document.getElementById('previewPlaceholder')
        };

        const originalValues = {};
        Object.keys(fields).forEach(key => {
            if (key !== 'image') {
                originalValues[key] = fields[key].value;
            }
        });

        function fieldText(field, fallback = '—') {
            return field.value.trim() || fallback;
        }

        function updatePreview() {
            preview.name.textContent = fieldText(fields.commonName, 'Plant Name');
            preview.scientific.textContent = fieldText(fields.scientificName, 'Scientific name');
            preview.category.textContent = fieldText(fields.category);
            preview.difficulty.textContent = fieldText(fields.difficulty);
            preview.origin.textContent = fieldText(fields.origin);
            preview.watering.textContent = fieldText(fields.watering);
            preview.light.textContent = fieldText(fields.light);
            preview.soilType.textContent = fieldText(fields.soilType);
            preview.temperature.textContent = fieldText(fields.temperature);
            preview.humidity.textContent = fieldText(fields.humidity);
            preview.size.textContent = fieldText(fields.size);
            preview.petSafe.textContent = fields.petSafe.value === '1' ? 'Pet safe' : 'Not pet safe';
            preview.commonProblems.textContent = fieldText(fields.commonProblems);
            preview.tip.textContent = fieldText(fields.careTip);
            preview.funFact.textContent = fieldText(fields.funFact);

            const file = fields.image.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.image.src = e.target.result;
                    preview.image.style.display = 'block';
                    preview.placeholder.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        }

        function checkChanges() {
            let changed = fields.image.files.length > 0;

            Object.keys(originalValues).forEach(key => {
                if (fields[key].value !== originalValues[key]) {
                    changed = true;
                }
            });

            changeBadge.style.display = changed ? 'inline-flex' : 'none';
        }

        Object.keys(fields).forEach(key => {
            fields[key].addEventListener(key === 'image' ? 'change' : 'input', () => {
                updatePreview();
                checkChanges();
            });
        });
        <?php endif; ?>
    </script>
</body>

</html>
