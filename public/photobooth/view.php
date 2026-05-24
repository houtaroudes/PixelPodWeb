<?php
// This is what the QR code links to after a photobooth session. It shows the photos taken and allows downloading them.
require_once __DIR__ . '/../../config/database.php';

$code    = trim($_GET['code'] ?? '');
$session = null;

if ($code) {
    $s = getDB()->prepare("SELECT * FROM photo_sessions WHERE session_code = ? AND is_active = 1");
    $s->execute([$code]);
    $session = $s->fetch();
}

$photos = $session ? json_decode($session['photos'], true) : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $code ? "Photos · $code" : 'Not Found' ?> | Pixel Pod Photobooth</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #1a0505, #3d0f0f);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px
        }

        .card {
            background: #fff;
            border-radius: 24px;
            max-width: 420px;
            width: 100%;
            padding: 36px 28px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .4)
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 900;
            color: #6b0f0f;
            margin-bottom: 4px
        }

        .logo em {
            color: #c8973d;
            font-style: italic
        }

        .code-badge {
            display: inline-block;
            background: rgba(107, 15, 15, .08);
            color: #6b0f0f;
            font-size: .78rem;
            font-weight: 700;
            padding: 5px 14px;
            border-radius: 100px;
            margin: 8px 0 20px;
            font-family: monospace
        }

        .strip-wrap {
            display: flex;
            flex-direction: column;
            gap: 3px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .12);
            margin-bottom: 20px
        }

        .strip-wrap img {
            width: 100%;
            display: block
        }

        .grid-wrap {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .12);
            margin-bottom: 20px;
            background: #eee
        }

        .grid-wrap img {
            width: 100%;
            aspect-ratio: 4/3;
            object-fit: cover;
            display: block
        }

        .btn-dl {
            display: block;
            width: 100%;
            padding: 14px;
            background: #6b0f0f;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: .95rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            margin-bottom: 10px;
            font-family: 'DM Sans', sans-serif
        }

        .btn-dl:hover {
            background: #8a1515
        }

        .btn-ghost {
            display: block;
            width: 100%;
            padding: 12px;
            background: none;
            color: #6b0f0f;
            border: 2px solid #e8d5d5;
            border-radius: 12px;
            font-size: .88rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-family: 'DM Sans', sans-serif
        }

        .meta {
            font-size: .75rem;
            color: #9ca3af;
            margin-top: 16px
        }

        .not-found {
            font-size: 3rem;
            margin-bottom: 16px
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="logo">Pixel <em>Pod</em></div>
        <div style="font-size:.75rem;color:#9ca3af;margin-bottom:4px">Photobooth</div>

        <?php if (!$session): ?>
            <!-- Not found -->
            <div class="not-found">&#128473;</div>
            <h2 style="font-family:'Playfair Display',serif;color:#6b0f0f;margin-bottom:8px">Photos Not Found</h2>
            <p style="color:#6b7280;font-size:.88rem;margin-bottom:20px">
                This QR code may be invalid or expired.<br>
                Code: <strong><?= htmlspecialchars($code ?: 'none') ?></strong>
            </p>
            <a href="http://localhost/PixelPodWeb/public/photobooth/index.php" class="btn-dl">
                &#128248; Take New Photos
            </a>

        <?php else: ?>
            <!-- Found -->
            <div class="code-badge"><?= htmlspecialchars($session['session_code']) ?></div>

            <?php if ($session['layout'] === 'strip'): ?>
                <div class="strip-wrap">
                    <?php foreach ($photos as $f): ?>
                        <img src="http://localhost/PixelPodWeb/uploads/photos/<?= htmlspecialchars($f) ?>"
                            alt="Photo" loading="lazy">
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="grid-wrap">
                    <?php foreach ($photos as $f): ?>
                        <img src="http://localhost/PixelPodWeb/uploads/photos/<?= htmlspecialchars($f) ?>"
                            alt="Photo" loading="lazy">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Download each photo -->
            <?php foreach ($photos as $i => $f): ?>
                <a href="http://localhost/PixelPodWeb/uploads/photos/<?= htmlspecialchars($f) ?>"
                    download="pixelpod_photo_<?= $i + 1 ?>.png" class="btn-dl" style="margin-bottom:8px">
                    &#128229; Download Photo <?= $i + 1 ?>
                </a>
            <?php endforeach; ?>

            <a href="http://localhost/PixelPodWeb/public/photobooth/index.php" class="btn-ghost">
                &#128248; Take New Photos
            </a>

            <div class="meta">
                Taken on <?= date('F j, Y \a\t g:i A', strtotime($session['created_at'])) ?><br>
                Filter: <?= ucfirst(str_replace('_', ' ', $session['filter_name'])) ?> ·
                Layout: <?= ucfirst($session['layout']) ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>